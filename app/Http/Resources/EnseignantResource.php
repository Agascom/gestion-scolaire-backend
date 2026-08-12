<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un enseignant.
 */
class EnseignantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_complet' => $this->nom_complet,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'diplome' => $this->diplome,
            'specialite' => $this->specialite,
            'statut' => $this->statut,
            'matieres' => $this->whenLoaded('matiereClasses', $this->matiereClasses->map(fn ($m) => [
                'matiere' => $m->matiere?->libelle,
                'classe' => $m->classe?->libelle,
                'coefficient' => $m->coefficient,
            ])),
        ];
    }
}