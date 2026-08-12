<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un niveau d'enseignement.
 */
class NiveauResource extends JsonResource
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
            'cycle_id' => $this->cycle_id,
        ];
    }
}