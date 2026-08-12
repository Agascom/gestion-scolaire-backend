<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un trimestre.
 */
class TrimestreResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'annee_academique_id' => $this->annee_academique_id,
            'numero' => $this->numero,
            'libelle' => $this->libelle,
            'date_debut' => $this->date_debut?->format('d/m/Y'),
            'date_fin' => $this->date_fin?->format('d/m/Y'),
            'cloture' => $this->cloture,
        ];
    }
}