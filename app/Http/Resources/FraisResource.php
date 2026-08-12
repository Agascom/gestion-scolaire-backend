<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un frais de scolarité.
 */
class FraisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'montant' => $this->montant,
            'periodicite' => $this->periodicite,
            'cycle' => $this->whenLoaded('cycle', $this->cycle?->libelle),
            'classe' => $this->whenLoaded('classe', $this->classe?->libelle),
            'actif' => $this->actif,
        ];
    }
}