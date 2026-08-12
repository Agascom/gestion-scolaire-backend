<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ClasseResource;
use App\Http\Resources\EleveResource;
use App\Http\Resources\EnseignantResource;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\EleveDocument;
use App\Models\Enseignant;
use App\Models\Frais;
use App\Models\ParentEleve;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Module Élèves : fiches, inscriptions, passage en classe supérieure,
 * documents du dossier, et lectures pour l'école.
 */
class EleveController extends ApiController
{
    use UtiliseAnneeCourante;

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Liste des élèves de l'école (filtres : classe, statut, recherche).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Eleve::query()
            ->with(['parentEleve'])
            ->when($request->input('classe_id'), fn ($q, $classeId) => $q->whereHas('classes', fn ($c) => $c->where('classe_eleve.classe_id', $classeId)))
            ->when($request->input('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->input('recherche'), function ($q, $recherche) {
                $q->where(function ($w) use ($recherche) {
                    $w->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('prenom', 'like', "%{$recherche}%")
                        ->orWhere('matricule', 'like', "%{$recherche}%");
                });
            })
            ->orderBy('nom');

        return $this->success(
            EleveResource::collection($query->paginate($request->integer('per_page', 15))),
            'Élèves récupérés.'
        );
    }

    /**
     * Fiche élève complète (parents, documents, classe actuelle).
     */
    public function show(int $id): JsonResponse
    {
        $eleve = Eleve::with(['parentEleve', 'documents'])->findOrFail($id);

        return $this->success(
            new EleveResource($eleve),
            'Élève récupéré.'
        );
    }

    /**
     * Création d'un élève avec matricule auto ECOLE-ANNEE-SEQ et parent associé.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'sexe' => ['required', 'in:M,F'],
            'date_naissance' => ['required', 'date'],
            'commune_naissance' => ['nullable', 'string', 'max:255'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'parent.nom' => ['required', 'string'],
            'parent.prenom' => ['required', 'string'],
            'parent.telephone' => ['required', 'string'],
            'parent.email' => ['nullable', 'email'],
            'parent.profession' => ['nullable', 'string'],
            'parent.est_tuteur' => ['nullable', 'boolean'],
        ]);

        $eleve = DB::transaction(function () use ($data) {
            $schoolId = $this->schoolId();

            $annee = $this->anneeCourante();
            $seq = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $matricule = sprintf('%s-%s-%s', $schoolId, $annee?->date_debut?->format('Y') ?? 'NOW', $seq);

            $eleve = Eleve::create([...$data, 'school_id' => $schoolId, 'matricule' => $matricule]);

            ParentEleve::create([
                'eleve_id' => $eleve->id,
                'nom' => $data['parent']['nom'],
                'prenom' => $data['parent']['prenom'],
                'telephone' => $data['parent']['telephone'],
                'email' => $data['parent']['email'] ?? null,
                'profession' => $data['parent']['profession'] ?? null,
                'est_tuteur' => $data['parent']['est_tuteur'] ?? true,
            ]);

            return $eleve->load('parentEleve');
        });

        $this->audit->log('eleves', 'creation', "Création de l'élève {$eleve->nom_complet} ({$eleve->matricule})");

        return $this->success(new EleveResource($eleve), 'Élève créé.', 201);
    }

    /**
     * Mise à jour de la fiche élève.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $eleve = Eleve::findOrFail($id);

        $data = $request->validate([
            'nom' => ['sometimes', 'string', 'max:255'],
            'prenom' => ['sometimes', 'string', 'max:255'],
            'sexe' => ['sometimes', 'in:M,F'],
            'date_naissance' => ['sometimes', 'date'],
            'commune_naissance' => ['nullable', 'string', 'max:255'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'statut' => ['sometimes', 'in:inscrit,reinscrit,transfere,radie,diplome'],
        ]);

        $eleve->update($data);
        $this->audit->log('eleves', 'modification', "Modification de l'élève {$eleve->nom_complet}");

        return $this->success(new EleveResource($eleve), 'Élève mis à jour.');
    }

    /**
     * Ajout d'un document au dossier de l'élève.
     */
    public function ajouterDocument(Request $request, int $id): JsonResponse
    {
        $eleve = Eleve::findOrFail($id);

        $data = $request->validate([
            'type' => ['required', 'in:acte_naissance,carnet_sante,certificat,releve'],
            'libelle' => ['nullable', 'string'],
            'fichier' => ['required', 'file', 'max:5120'],
            'notes' => ['nullable', 'string'],
        ]);

        $path = $request->file('fichier')->store("documents/{$eleve->school_id}/{$eleve->id}", 'public');

        $document = EleveDocument::create([
            'school_id' => $eleve->school_id,
            'eleve_id' => $eleve->id,
            'type' => $data['type'],
            'libelle' => $data['libelle'] ?? $data['type'],
            'fichier_path' => $path,
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->success($document, 'Document ajouté.', 201);
    }

    /**
     * Inscription (ou réinscription) d'un élève dans une classe pour l'année courante.
     */
    public function inscrire(Request $request, int $id): JsonResponse
    {
        $eleve = Eleve::findOrFail($id);
        $annee = $this->anneeCourante();

        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'statut' => ['sometimes', 'in:inscrit,reinscrit'],
        ]);

        $classe = Classe::where('annee_academique_id', $annee?->id)
            ->findOrFail($data['classe_id']);

        $classe->eleves()->syncWithoutDetaching([
            $eleve->id => ['school_id' => $this->schoolId(), 'annee_academique_id' => $annee->id],
        ]);

        $eleve->update(['statut' => $data['statut'] ?? Eleve::STATUT_INSCRIT]);
        $this->audit->log('eleves', 'inscription', "Inscription de {$eleve->nom_complet} en {$classe->libelle}");

        return $this->success(new EleveResource($eleve->load('parentEleve')), 'Élève inscrit.');
    }

    /**
     * Transfert d'élève vers une autre classe (au sein du complexe).
     */
    public function transferer(Request $request, int $id): JsonResponse
    {
        $eleve = Eleve::findOrFail($id);
        $annee = $this->anneeCourante();

        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
        ]);

        $classe = Classe::where('annee_academique_id', $annee?->id)->findOrFail($data['classe_id']);

        // Retrait des classes actuelles, puis ajout dans la nouvelle.
        $eleve->classes()->wherePivot('annee_academique_id', $annee->id)->detach();
        $classe->eleves()->syncWithoutDetaching([
            $eleve->id => ['school_id' => $this->schoolId(), 'annee_academique_id' => $annee->id],
        ]);

        $eleve->update(['statut' => Eleve::STATUT_TRANSFERE]);
        $this->audit->log('eleves', 'transfert', "Transfert de {$eleve->nom_complet} vers {$classe->libelle}");

        return $this->success(new EleveResource($eleve), 'Élève transféré.');
    }

    /**
     * Liste des enseignants (avec affectations).
     */
    public function enseignants(Request $request): JsonResponse
    {
        $enseignants = Enseignant::with(['matiereClasses.matiere', 'matiereClasses.classe'])
            ->orderBy('nom')
            ->paginate($request->integer('per_page', 15));

        return $this->success(EnseignantResource::collection($enseignants), 'Enseignants récupérés.');
    }

    /**
     * Récapitulatif global de l'école : effectifs, classes, frais, enseignants.
     */
    public function repertoire(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        return $this->success([
            'effectifs' => Eleve::count(),
            'classes' => ClasseResource::collection(Classe::when($annee, fn ($q) => $q->where('annee_academique_id', $annee->id))->get()),
            'enseignants' => Enseignant::count(),
            'frais' => Frais::where('actif', true)->get(['id', 'libelle', 'montant']),
        ], 'Répertoire de l\'école.');
    }
}
