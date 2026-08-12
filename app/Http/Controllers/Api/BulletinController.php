<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AnneeAcademiqueResource;
use App\Http\Resources\BulletinResource;
use App\Http\Resources\ClasseResource;
use App\Models\AnneeAcademique;
use App\Models\Bulletin;
use App\Models\Classe;
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
}