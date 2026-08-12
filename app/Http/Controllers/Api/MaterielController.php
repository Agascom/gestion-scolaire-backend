<?php

namespace App\Http\Controllers\Api;

use App\Models\Materiel;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Module Matériel (inventaire) : entrées, sorties, état, maintenance.
 */
class MaterielController extends ApiController
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $materiels = Materiel::query()
            ->when($request->input('categorie'), fn ($q, $v) => $q->where('categorie', $v))
            ->when($request->input('etat'), fn ($q, $v) => $q->where('etat', $v))
            ->orderBy('libelle')
            ->get();

        return $this->success($materiels, 'Matériel récupéré.');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'categorie' => ['required', 'in:salle,mobilier,equipement_info,livre,fourniture'],
            'libelle' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string'],
            'etat' => ['sometimes', 'string', 'max:50'],
            'valeur' => ['sometimes', 'numeric', 'min:0'],
            'emplacement' => ['nullable', 'string'],
            'date_acquisition' => ['sometimes', 'date'],
            'duree_amortissement_mois' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        $materiel = Materiel::create([...$data, 'school_id' => auth()->user()?->school_id]);
        $this->audit->log('materiel', 'ajout', "Ajout de matériel : {$materiel->libelle}");

        return $this->success($materiel, 'Matériel ajouté.', 201);
    }

    /**
     * Sortie / changement d'état (panne, maintenance, réforme).
     */
    public function miseAJourEtat(Request $request, int $id): JsonResponse
    {
        $materiel = Materiel::findOrFail($id);

        $data = $request->validate([
            'etat' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $materiel->update($data);
        $this->audit->log('materiel', 'etat', "Matériel {$materiel->libelle} -> {$data['etat']}");

        return $this->success($materiel, 'État du matériel mis à jour.');
    }

    /**
     * Amortissement simplifié : valeur résiduelle = valeur - (valeur/durée × mois écoulés).
     */
    public function valeurResiduelle(Materiel $materiel): JsonResponse
    {
        if (! $materiel->duree_amortissement_mois || ! $materiel->date_acquisition) {
            return $this->success(['valeur_nette' => $materiel->valeur], 'Sans amortissement.');
        }

        $moisEcoules = $materiel->date_acquisition->diffInMonths(now());
        $mensualite = (float) $materiel->valeur / $materiel->duree_amortissement_mois;
        $valeurNette = max(0, (float) $materiel->valeur - ($mensualite * $moisEcoules));

        return $this->success([
            'valeur_achat' => $materiel->valeur,
            'mois_ecoules' => $moisEcoules,
            'valeur_nette' => round($valeurNette, 2),
        ], 'Valeur nette.');
    }
}