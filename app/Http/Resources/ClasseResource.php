<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'une classe.
 */
class ClasseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'section' => $this->section,
            'niveau' => $this->whenLoaded('niveau', new NiveauResource($this->niveau)),
            'annee_academique' => $this->whenLoaded('anneeAcademique', $this->anneeAcademique?->libelle),
            'effectif' => $this->when($request->boolean('avec_effectif'), fn () => $this->eleves()->count()),
        ];
    }
}