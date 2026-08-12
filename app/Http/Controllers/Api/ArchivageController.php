<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PassageClasseResource;
use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Eleve;
use App\Services\AuditService;
use App\Services\ClotureAnneeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Module Archivage & clôture d'année : passage en classe supérieure
 * automatique, archivage en lecture seule des années précédentes.
 */
class ArchivageController extends ApiController
{
    use UtiliseAnneeCourante;

    public function __construct(
        private readonly AuditService $audit,
        private readonly ClotureAnneeService $cloture,
    ) {
    }

    /**
     * Clôture de l'année académique courante :
     * calcule les moyennes, génère les passages et inscrit dans l'année suivante,
     * puis archive l'année (lecture seule).
     */
    public function cloturerAnnee(Request $request): JsonResponse
    {
        $annee = $this->anneeCourante();

        if (! $annee) {
            return $this->error('Aucune année académique active.', 404);
        }

        if ($annee->cloturee) {
            return $this->error("L'année {$annee->libelle} est déjà clôturée.", 409);
        }

        $data = $request->validate(['seuil_admission' => ['sometimes', 'numeric', 'min:0', 'max:20']]);

        $rapport = $this->cloture->cloturer($annee, (float) ($data['seuil_admission'] ?? 10));

        $this->audit->log('annees', 'cloture', "Clôture de l'année {$annee->libelle}");

        return $this->success([
            'annee' => $annee->libelle,
            'archivee' => true,
            'admis' => $rapport['admis'],
            'redoublants' => $rapport['redoublants'],
        ], 'Année clôturée et archivée.');
    }

    /**
     * Liste des années archivées (consultation en lecture seule).
     */
    public function listeAnnexesArchives(): JsonResponse
    {
        $annees = AnneeAcademique::withCount(['classes', 'bulletins'])
            ->where('archivee', true)
            ->orderByDesc('date_debut')
            ->get();

        return $this->success($annees, 'Archives.');
    }

    /**
     * Consultations d'une année archive : effectifs, classes, bulletins.
     */
    public function consulterArchive(int $anneeId, Request $request): JsonResponse
    {
        $annee = AnneeAcademique::with(['trimestres', 'classes.niveau'])->findOrFail($anneeId);

        if (! $annee->archivee && ! $annee->cloturee) {
            return $this->error('L\'année n\'est pas encore archivée.', 409);
        }

        $stats = [
            'classes' => $annee->classes->count(),
            'bulletins' => $annee->bulletins()->count(),
            'eleves_inscrits' => \App\Models\TableClasse::where('annee_academique_id', $annee->id)->count(),
        ];

        return $this->success([
            'annee' => $annee,
            'classe_ids' => $annee->classes->pluck('id'),
            'statistiques' => $stats,
        ], 'Archive consultée.');
    }

    /**
     * Historique des passages d'un élève.
     */
    public function passagesEleve(int $eleveId): JsonResponse
    {
        $passages = \App\Models\PassageClasse::where('eleve_id', $eleveId)
            ->with(['anneeAcademique', 'classeSource', 'classeCible'])
            ->orderByDesc('created_at')
            ->get();

        return $this->success(PassageClasseResource::collection($passages), 'Parcours de l\'élève.');
    }
}