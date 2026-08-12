<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EvaluationResource;
use App\Http\Resources\NoteResource;
use App\Models\Evaluation;
use App\Models\Note;
use App\Services\AuditService;
use App\Services\CalculMoyennesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Module Notes : évaluations (interrogation, devoir, composition, examen)
 * et saisie des notes par matière/classe/trimestre.
 */
class NoteController extends ApiController
{
    use UtiliseAnneeCourante;

    public const TYPES_EVALUATION = ['interrogation', 'devoir', 'composition', 'examen'];

    public function __construct(
        private readonly AuditService $audit,
        private readonly CalculMoyennesService $moyennes,
    ) {
    }

    /**
     * Liste des évaluations (filtres : classe, matière, trimestre, type).
     */
    public function index(Request $request): JsonResponse
    {
        $evaluations = Evaluation::with(['classe', 'matiere', 'trimestre'])
            ->when($request->input('classe_id'), fn ($q, $v) => $q->where('classe_id', $v))
            ->when($request->input('matiere_id'), fn ($q, $v) => $q->where('matiere_id', $v))
            ->when($request->input('trimestre_id'), fn ($q, $v) => $q->where('trimestre_id', $v))
            ->when($request->input('type'), fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc('date_evaluation')
            ->paginate($request->integer('per_page', 15));

        return $this->success(EvaluationResource::collection($evaluations), 'Évaluations récupérées.');
    }

    /**
     * Détail d'une évaluation avec les notes par élève.
     */
    public function show(Evaluation $evaluation): JsonResponse
    {
        $evaluation->load(['classe.eleves', 'matiere', 'notes.eleve']);

        return $this->success([
            'evaluation' => new EvaluationResource($evaluation),
            'eleves' => $evaluation->classe->eleves->map(fn ($eleve) => [
                'eleve_id' => $eleve->id,
                'nom' => $eleve->nom_complet,
                'matricule' => $eleve->matricule,
                'note' => $evaluation->notes->firstWhere('eleve_id', $eleve->id)?->note,
                'appreciation' => $evaluation->notes->firstWhere('eleve_id', $eleve->id)?->appreciation,
            ]),
        ], 'Évaluation récupérée.');
    }

    /**
     * Création d'une évaluation (par l'enseignant).
     */
    public function creerEvaluation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'matiere_id' => ['required', 'exists:matieres,id'],
            'trimestre_id' => ['required', 'exists:trimestres,id'],
            'type' => ['required', 'in:'.implode(',', self::TYPES_EVALUATION)],
            'libelle' => ['required', 'string', 'max:255'],
            'date_evaluation' => ['required', 'date'],
            'note_sur' => ['required', 'numeric', 'min:1', 'max:100'],
        ]);

        $evaluation = Evaluation::create([
            'school_id' => $this->schoolId(),
            ...$data,
        ]);

        $this->audit->log('notes', 'creation_evaluation', "Création d'évaluation {$evaluation->libelle}");

        return $this->success(new EvaluationResource($evaluation->load('classe', 'matiere', 'trimestre')), 'Évaluation créée.', 201);
    }

    /**
     * Saisie en masse des notes d'une évaluation.
     * Body : { "notes": [ { "eleve_id": 1, "note": 14.5, "appreciation": "..." }, ... ] }
     */
    public function saisirNotes(Evaluation $evaluation, Request $request): JsonResponse
    {
        if ($evaluation->publiee) {
            return $this->error('Une évaluation publiée ne peut plus être modifiée.', 409);
        }

        $data = $request->validate([
            'notes' => ['required', 'array'],
            'notes.*.eleve_id' => ['required', 'exists:eleves,id'],
            'notes.*.note' => ['required', 'numeric', 'min:0', "max:{$evaluation->note_sur}"],
            'notes.*.appreciation' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($evaluation, $data) {
            foreach ($data['notes'] as $ligne) {
                Note::updateOrCreate(
                    ['evaluation_id' => $evaluation->id, 'eleve_id' => $ligne['eleve_id']],
                    ['note' => $ligne['note'], 'appreciation' => $ligne['appreciation'] ?? null]
                );
            }
        });

        $this->audit->log('notes', 'saisie_notes', sprintf('Saisie de %d notes pour "%s"', count($data['notes']), $evaluation->libelle));

        return $this->success(['saisies' => count($data['notes'])], 'Notes enregistrées.');
    }

    /**
     * Publication d'une évaluation (débloquée) après validation.
     * La publication ferme la saisie puis notifie les parents.
     */
    public function publier(Evaluation $evaluation): JsonResponse
    {
        $evaluation->update(['publiee' => true]);
        $this->audit->log('notes', 'publication', "Publication de l'évaluation {$evaluation->libelle}");

        return $this->success(new EvaluationResource($evaluation), 'Évaluation publiée.');
    }

    /**
     * Déverrouillage d'une évaluation publiée (admin).
     */
    public function deverrouiller(Evaluation $evaluation): JsonResponse
    {
        $evaluation->update(['publiee' => false]);
        $this->audit->log('notes', 'deverrouillage', "Réouverture de l'évaluation {$evaluation->libelle}");

        return $this->success(new EvaluationResource($evaluation), 'Évaluation rouverte.');
    }

    /**
     * Moyennes d'un élève par matière sur un trimestre, avec moyenne générale et rang.
     */
    public function moyennesEleve(int $eleveId, Request $request): JsonResponse
    {
        $trimestreId = $request->integer('trimestre_id') ?: null;
        $classeId = $request->integer('classe_id');

        return $this->success([
            'moyenne_generale' => $classeId ? $this->moyennes->moyenneGenerale($eleveId, $classeId, $trimestreId) : null,
            'rang' => $classeId ? $this->moyennes->classementClasse($classeId, $trimestreId)
                ->first(fn ($r) => $r['eleve_id'] === $eleveId)?->get('rang') : null,
        ], 'Moyennes calculées.');
    }

    /**
     * Notes d'un élève (pour l'espace parent).
     */
    public function notesEleve(int $eleveId): JsonResponse
    {
        $notes = Note::with(['evaluation.matiere', 'evaluation.trimestre'])
            ->where('eleve_id', $eleveId)
            ->orderByDesc('created_at')
            ->get();

        return $this->success(NoteResource::collection($notes), 'Notes de l\'élève.');
    }
}