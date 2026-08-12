<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un cycle d'enseignement.
 */
class CycleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'ordre' => $this->ordre,
            'niveaux' => NiveauResource::collection($this->whenLoaded('niveaux')),
        ];
    }
}