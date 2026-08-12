<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'une décision de passage en classe supérieure.
 */
class PassageClasseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'eleve' => $this->whenLoaded('eleve', fn () => [
                'id' => $this->eleve->id,
                'nom' => $this->eleve->nom_complet,
            ]),
            'annee_academique' => $this->whenLoaded('anneeAcademique', $this->anneeAcademique?->libelle),
            'classe_source' => $this->whenLoaded('classeSource', $this->classeSource?->libelle),
            'classe_cible' => $this->whenLoaded('classeCible', $this->classeCible?->libelle),
            'moyenne_generale' => $this->moyenne_generale,
            'decision' => $this->decision,
            'appreciation' => $this->appreciation,
        ];
    }
}