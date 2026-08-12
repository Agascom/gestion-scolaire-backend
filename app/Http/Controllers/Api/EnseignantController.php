<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EvaluationResource;
use App\Models\Absence;
use App\Models\Classe;
use App\Models\CreneauEdt;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Evaluation;
use App\Models\MatiereClasse;
use App\Models\Trimestre;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Espace Enseignants : profil, classes, effectifs, évaluations,
 * emploi du temps et absences. Réservé aux comptes portant le rôle `enseignant`.
 */
class EnseignantController extends ApiController
{
    use UtiliseAnneeCourante;

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Profil enseignant du compte connecté (404 si aucun profil lié).
     */
    public function me(): JsonResponse
    {
        $profil = auth()->user()?->profilEnseignant;

        if (! $profil) {
            return $this->error('Aucun profil enseignant lié à votre compte.', 404);
        }

        return $this->success($this->profilData($profil), 'Profil enseignant récupéré.');
    }

    /**
     * Classes de l'année courante où l'enseignant intervient, avec matières et effectif.
     */
    public function mesClasses(): JsonResponse
    {
        $profil = $this->profilOuAbort();
        $annee = $this->anneeCourante();

        $affectations = MatiereClasse::with(['classe.niveau', 'classe.anneeAcademique', 'matiere'])
            ->where('enseignant_id', $profil->id)
            ->whereHas('classe', fn ($q) => $q->when($annee, fn ($w) => $w->where('annee_academique_id', $annee->id)))
            ->get();

        return $this->success($affectations->groupBy('classe_id')->map(function ($groupe) {
            $classe = $groupe->first()->classe;

            return [
                'id' => $classe->id,
                'libelle' => $classe->libelle,
                'niveau' => $classe->niveau?->libelle,
                'annee_scolaire' => $classe->anneeAcademique?->libelle,
                'salle' => null,
                'effectif' => $classe->eleves()->count(),
                'matieres' => $groupe->map(fn (MatiereClasse $mc) => [
                    'id' => $mc->matiere_id,
                    'libelle' => $mc->matiere?->libelle,
                    'abreviation' => $mc->matiere?->abreviation,
                    'coefficient' => (float) $mc->coefficient,
                ])->values(),
            ];
        })->values(), 'Classes récupérées.');
    }

    /**
     * Élèves d'une classe où l'enseignant intervient (sinon 403).
     */
    public function effectifs(Request $request, int $classe): JsonResponse
    {
        $profil = $this->profilOuAbort();
        $this->verifieIntervention($profil, $classe);

        $eleves = Classe::findOrFail($classe)->eleves()
            ->get(['eleves.id', 'eleves.matricule', 'eleves.nom', 'eleves.prenom', 'eleves.sexe']);

        return $this->success($eleves->map(fn (Eleve $eleve) => [
            'id' => $eleve->id,
            'matricule' => $eleve->matricule,
            'nom' => $eleve->nom,
            'prenom' => $eleve->prenom,
            'sexe' => $eleve->sexe,
        ])->values(), 'Effectifs récupérés.');
    }

    /**
     * Évaluations des classes de l'enseignant (année courante).
     */
    public function evaluations(Request $request): JsonResponse
    {
        $profil = $this->profilOuAbort();
        $annee = $this->anneeCourante();

        $classeIds = MatiereClasse::where('enseignant_id', $profil->id)
            ->when($annee, fn ($q) => $q->whereHas('classe', fn ($w) => $w->where('annee_academique_id', $annee->id)))
            ->pluck('classe_id');

        $evaluations = Evaluation::with(['classe', 'matiere', 'trimestre'])
            ->whereIn('classe_id', $classeIds)
            ->orderByDesc('date_evaluation')
            ->get();

        return $this->success(EvaluationResource::collection($evaluations), 'Évaluations récupérées.');
    }

    /**
     * Création d'une évaluation par l'enseignant (classe/matière qui lui sont affectées).
     */
    public function creerEvaluation(Request $request): JsonResponse
    {
        $profil = $this->profilOuAbort();

        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'type' => ['required', 'in:'.implode(',', NoteController::TYPES_EVALUATION)],
            'libelle' => ['required', 'string', 'max:255'],
            'date_evaluation' => ['required', 'date'],
            'note_sur' => ['required', 'numeric', 'min:1', 'max:100'],
            'trimestre_id' => ['sometimes', 'exists:trimestres,id'],
        ]);

        $affecte = MatiereClasse::where('classe_id', $data['classe_id'])
            ->where('matiere_id', $data['matiere_id'])
            ->where('enseignant_id', $profil->id)
            ->exists();

        abort_unless($affecte, 403, 'Vous n\'intervenez pas sur cette classe / matière.');

        $trimestreId = $data['trimestre_id'] ?? $this->trimestreEnCours()?->id;
        abort_unless($trimestreId, 422, 'Aucun trimestre en cours pour cette année.');

        $evaluation = Evaluation::create([
            'school_id' => $this->schoolId(),
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'],
            'trimestre_id' => $trimestreId,
            'type' => $data['type'],
            'libelle' => $data['libelle'],
            'date_evaluation' => $data['date_evaluation'],
            'note_sur' => $data['note_sur'],
        ]);

        $this->audit->log('notes', 'creation_evaluation', "Création de l'évaluation {$evaluation->libelle} par l'enseignant");

        return $this->success(new EvaluationResource($evaluation->load('classe', 'matiere', 'trimestre')), 'Évaluation créée.', 201);
    }

    /**
     * Emploi du temps de l'enseignant connecté.
     */
    public function emploiDuTemps(): JsonResponse
    {
        $profil = $this->profilOuAbort();

        $creneaux = CreneauEdt::with(['classe', 'matiere', 'salle'])
            ->where('enseignant_id', $profil->id)
            ->orderBy('jour')
            ->orderBy('heure_debut')
            ->get();

        return $this->success($creneaux->map(fn (CreneauEdt $creneau) => [
            'id' => $creneau->id,
            'jour' => $creneau->jour,
            'heure_debut' => $this->heureCourte($creneau->heure_debut),
            'heure_fin' => $this->heureCourte($creneau->heure_fin),
            'classe' => $creneau->classe?->libelle,
            'matiere' => $creneau->matiere?->libelle,
            'salle' => $creneau->salle?->libelle,
        ])->values(), 'Emploi du temps récupéré.');
    }

    /**
     * Absences d'une classe dont l'enseignant est responsable, pour un jour donné.
     */
    public function absences(Request $request): JsonResponse
    {
        $profil = $this->profilOuAbort();

        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'date' => ['required', 'date'],
        ]);

        $this->verifieIntervention($profil, $data['classe_id']);

        $absences = Absence::with('eleve')
            ->where('classe_id', $data['classe_id'])
            ->where('date_absence', $data['date'])
            ->get();

        return $this->success($absences->map(fn (Absence $absence) => [
            'eleve_id' => $absence->eleve_id,
            'nom_complet' => $absence->eleve?->nom_complet,
            'date_absence' => $absence->date_absence?->format('d/m/Y'),
            'justifiee' => (bool) $absence->justifiee,
            'motif' => $absence->motif,
        ])->values(), 'Absences récupérées.');
    }

    /**
     * Enregistrement en masse des absences d'une classe pour un jour donné.
     * Body : { classe_id, date, absences: [{ eleve_id, justifiee, motif }] }.
     */
    public function enregistrerAbsences(Request $request): JsonResponse
    {
        $profil = $this->profilOuAbort();

        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'date' => ['required', 'date'],
            'absences' => ['required', 'array'],
            'absences.*.eleve_id' => ['required', 'integer', 'exists:eleves,id'],
            'absences.*.justifiee' => ['sometimes', 'boolean'],
            'absences.*.motif' => ['nullable', 'string', 'max:255'],
        ]);

        $this->verifieIntervention($profil, $data['classe_id']);

        $annee = $this->anneeCourante();
        $crees = 0;

        DB::transaction(function () use ($data, $annee, $profil, &$crees) {
            foreach ($data['absences'] as $ligne) {
                Absence::updateOrCreate(
                    [
                        'classe_id' => $data['classe_id'],
                        'eleve_id' => $ligne['eleve_id'],
                        'date_absence' => $data['date'],
                    ],
                    [
                        'school_id' => $this->schoolId(),
                        'annee_academique_id' => $annee?->id,
                        'enseignant_id' => $profil->id,
                        'justifiee' => $ligne['justifiee'] ?? false,
                        'motif' => $ligne['motif'] ?? null,
                    ]
                );
                $crees++;
            }
        });

        $this->audit->log('absences', 'creation', "Enregistrement de {$crees} absences par l'enseignant");

        return $this->success(['crees' => $crees], 'Absences enregistrées.');
    }

    /**
     * Trimestre en cours, non clos, de l'année courante.
     */
    private function trimestreEnCours(): ?Trimestre
    {
        $annee = $this->anneeCourante();

        if (! $annee) {
            return null;
        }

        return Trimestre::where('annee_academique_id', $annee->id)
            ->where('cloture', false)
            ->orderBy('numero')
            ->first();
    }

    /**
     * Vérifie que l'enseignant intervient bien dans la classe donnée, sinon 403.
     */
    private function verifieIntervention(Enseignant $profil, int $classeId): void
    {
        $intervient = MatiereClasse::where('enseignant_id', $profil->id)
            ->where('classe_id', $classeId)
            ->exists();

        abort_unless($intervient, 403, 'Vous n\'intervenez pas dans cette classe.');
    }

    /**
     * Profil enseignant du compte connecté, sinon 403.
     */
    private function profilOuAbort(): Enseignant
    {
        $profil = auth()->user()?->profilEnseignant;

        abort_unless($profil, 403, 'Aucun profil enseignant lié à votre compte.');

        return $profil;
    }

    /**
     * Données exposées du profil enseignant.
     *
     * @return array<string, mixed>
     */
    private function profilData(Enseignant $profil): array
    {
        return [
            'id' => $profil->id,
            'nom' => $profil->nom,
            'prenom' => $profil->prenom,
            'nom_complet' => $profil->nom_complet,
            'specialite' => $profil->specialite,
            'email' => $profil->email,
            'telephone' => $profil->telephone,
        ];
    }

    /**
     * Formate une heure (chaine DBTIME) en HH:MM.
     */
    private function heureCourte(mixed $heure): ?string
    {
        if (! $heure) {
            return null;
        }

        return strlen((string) $heure) >= 5 ? substr((string) $heure, 0, 5) : (string) $heure;
    }
}
