<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'une année académique (avec possibilité d'archive).
 */
class AnneeAcademiqueResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'date_debut' => $this->date_debut?->format('d/m/Y'),
            'date_fin' => $this->date_fin?->format('d/m/Y'),
            'trimestre_en_cours' => $this->trimestre_en_cours,
            'cloturee' => $this->cloturee,
            'archivee' => $this->archivee,
            'trimestres' => $this->whenLoaded('trimestres', TrimestreResource::collection($this->trimestres)),
        ];
    }
}