<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un document du dossier élève.
 */
class EleveDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'eleve_id' => $this->eleve_id,
            'type' => $this->type,
            'libelle' => $this->libelle,
            'fichier_path' => $this->fichier_path,
            'notes' => $this->notes,
        ];
    }
}