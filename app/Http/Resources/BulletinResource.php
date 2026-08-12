<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un bulletin d'élève.
 */
class BulletinResource extends JsonResource
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
                'matricule' => $this->eleve->matricule,
            ]),
            'classe' => $this->whenLoaded('classe', $this->classe?->libelle),
            'trimestre' => $this->whenLoaded('trimestre', $this->trimestre?->libelle),
            'moyenne_generale' => $this->moyenne_generale,
            'rang' => $this->rang,
            'mention' => $this->mention,
            'appreciation' => $this->appreciation,
            'pdf_path' => $this->pdf_path,
            'statut' => $this->statut,
        ];
    }
}