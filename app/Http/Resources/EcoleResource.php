<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'une école.
 */
class EcoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'sigle' => $this->sigle,
            'adresse' => $this->adresse,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'numero_agrement' => $this->numero_agrement,
            'statut' => $this->statut,
        ];
    }
}