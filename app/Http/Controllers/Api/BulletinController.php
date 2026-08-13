<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AnneeAcademiqueResource;
use App\Http\Resources\BulletinResource;
use App\Http\Resources\ClasseResource;
use App\Models\AnneeAcademique;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Niveau;
use App\Models\Trimestre;
use App\Services\AuditService;
use App\Services\BulletinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Module Trimestres & Bulletins : clôture de périodes, conseils de classe,
 * génération et publication des bulletins.
 */
class BulletinController extends ApiController
{
    use UtiliseAnneeCourante;

    public function __construct(
        private readonly AuditService $audit,
        private readonly BulletinService $bulletins,
    ) {
    }

    /**
     * Liste des années académiques (actives et archivées consultables).
     */
    public function annees(Request $request): JsonResponse
    {
        $annees = AnneeAcademique::with('trimestres')
            ->when($request->boolean('avec_archive'), fn ($q) => $q, fn ($q) => $q->where('archivee', false))
            ->orderByDesc('date_debut')
            ->get();

        return $this->success(AnneeAcademiqueResource::collection($annees), 'Années académiques.');
    }

    /**
     * Génère les bulletins de toute une classe pour un trimestre.
     */
    public function genererClasse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'trimestre_id' => ['required', 'exists:trimestres,id'],
        ]);

        $classe = Classe::findOrFail($data['classe_id']);
        $trimestre = Trimestre::findOrFail($data['trimestre_id']);

        $bulletins = $this->bulletins->genererClasse($classe, $trimestre);

        $this->audit->log('bulletins', 'generation', sprintf('Génération des bulletins de %s (%s)', $classe->libelle, $trimestre->libelle));

        return $this->success(BulletinResource::collection(collect($bulletins)), 'Bulletins générés.');
    }

    /**
     * Publication d'un bulletin (envoi aux parents).
     */
    public function publier(Bulletin $bulletin): JsonResponse
    {
        $bulletin->update(['statut' => Bulletin::STATUT_PUBLIE]);
        $this->audit->log('bulletins', 'publication', "Publication du bulletin de {$bulletin->eleve?->nom_complet}");

        return $this->success(new BulletinResource($bulletin->load('eleve', 'classe', 'trimestre')), 'Bulletin publié.');
    }

    /**
     * Clôture d'un trimestre : gel des saisies.
     */
    public function cloturerTrimestre(Trimestre $trimestre, Request $request): JsonResponse
    {
        $valider = $request->validate(['cloture' => ['sometimes', 'boolean']]);
        $trimestre->update(['cloture' => (bool) ($valider['cloture'] ?? true)]);

        $this->audit->log('trimestres', 'cloture', "{$trimestre->libelle} clôturé");

        return $this->success($trimestre, 'Trimestre mis à jour.');
    }

    /**
     * Liste des classes (avec effectifs) pour la navigation.
     */
    public function classes(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        $classes = Classe::with('niveau')
            ->when($annee, fn ($q) => $q->where('annee_academique_id', $annee->id))
            ->get()
            ->map(fn (Classe $classe) => [
                'id' => $classe->id,
                'libelle' => $classe->libelle,
                'effectif' => $classe->eleves()->count(),
            ]);

        return $this->success($classes, 'Classes récupérées.');
    }

    /**
     * Création d'une classe pour l'année académique courante.
     */
    public function creerClasse(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();
        if (! $annee) {
            return $this->error('Aucune année académique courante. Veuillez en créer une.', 422);
        }

        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'niveau_id' => ['required', 'exists:niveaux,id'],
            'section' => ['nullable', 'string', 'max:100'],
        ]);

        $existe = Classe::where('annee_academique_id', $annee->id)
            ->where('libelle', $data['libelle'])
            ->exists();

        if ($existe) {
            return $this->error("La classe « {$data['libelle']} » existe déjà pour l'année courante.", 422);
        }

        $classe = Classe::create([
            'school_id' => $this->schoolId(),
            'annee_academique_id' => $annee->id,
            'niveau_id' => $data['niveau_id'],
            'section' => $data['section'] ?? null,
            'libelle' => $data['libelle'],
        ]);

        $this->audit->log('classes', 'creation', "Création de la classe {$classe->libelle}");

        return $this->success(new ClasseResource($classe->load('niveau')), 'Classe créée.', 201);
    }

    /**
     * Modification d'une classe (libellé, section, niveau).
     */
    public function modifierClasse(Request $request, int $id): JsonResponse
    {
        $classe = Classe::findOrFail($id);

        $data = $request->validate([
            'libelle' => ['sometimes', 'string', 'max:255'],
            'niveau_id' => ['sometimes', 'exists:niveaux,id'],
            'section' => ['nullable', 'string', 'max:100'],
        ]);

        if (isset($data['libelle'])) {
            $conflit = Classe::where('annee_academique_id', $classe->annee_academique_id)
                ->where('libelle', $data['libelle'])
                ->where('id', '!=', $classe->id)
                ->exists();
            if ($conflit) {
                return $this->error("La classe « {$data['libelle']} » existe déjà.", 422);
            }
        }

        $classe->update($data);
        $this->audit->log('classes', 'modification', "Modification de la classe {$classe->libelle}");

        return $this->success(new ClasseResource($classe->load('niveau')), 'Classe mise à jour.');
    }
}