<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CycleResource;
use App\Http\Resources\MatiereResource;
use App\Http\Resources\NiveauResource;
use App\Models\Cycle;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Salle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Données de référence (cycles, niveaux, matières).
 */
class ReferenceController extends ApiController
{
    /**
     * Cycles avec leurs niveaux.
     */
    public function cycles(): JsonResponse
    {
        $cycles = Cycle::with('niveaux')->orderBy('ordre')->get();

        return $this->success(CycleResource::collection($cycles), 'Cycles récupérés.');
    }

    /**
     * Niveaux d'enseignement.
     */
    public function niveaux(): JsonResponse
    {
        $niveaux = Niveau::orderBy('ordre')->get();

        return $this->success(NiveauResource::collection($niveaux), 'Niveaux récupérés.');
    }

    /**
     * Matières du référentiel.
     */
    public function matieres(): JsonResponse
    {
        $matieres = Matiere::orderBy('libelle')->get();

        return $this->success(MatiereResource::collection($matieres), 'Matières récupérées.');
    }

    /**
     * Salles de l'école.
     */
    public function salles(): JsonResponse
    {
        $salles = Salle::orderBy('libelle')->get();

        return $this->success($salles, 'Salles récupérées.');
    }

    /**
     * Création d'une salle.
     */
    public function creerSalle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'capacite' => ['sometimes', 'integer', 'min:1'],
        ]);

        $salle = Salle::create([...$data, 'school_id' => auth()->user()?->school_id]);

        return $this->success($salle, 'Salle créée.', 201);
    }
}