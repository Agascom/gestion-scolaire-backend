<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'une matière du référentiel.
 */
class MatiereResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'libelle' => $this->libelle,
            'abreviation' => $this->abreviation,
            'coefficient_par_defaut' => $this->coefficient_par_defaut,
        ];
    }
}