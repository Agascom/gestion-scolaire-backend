<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'une note d'élève.
 */
class NoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'eleve_id' => $this->eleve_id,
            'eleve' => $this->whenLoaded('eleve', $this->eleve?->nom_complet),
            'note' => $this->note,
            'appreciation' => $this->appreciation,
        ];
    }
}