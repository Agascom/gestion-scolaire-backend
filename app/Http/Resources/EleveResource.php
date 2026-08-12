<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un élève (fiche complète).
 */
class EleveResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_complet' => $this->nom_complet,
            'sexe' => $this->sexe,
            'date_naissance' => $this->date_naissance?->format('d/m/Y'),
            'commune_naissance' => $this->commune_naissance,
            'nationalite' => $this->nationalite,
            'adresse' => $this->adresse,
            'photo_path' => $this->photo_path,
            'statut' => $this->statut,
            'classe_actuelle' => optional($this->classeActuelle())?->only(['id', 'libelle']),
            'parent' => $this->whenLoaded('parentEleve', new ParentResource($this->parentEleve)),
            'documents' => $this->whenLoaded('documents', EleveDocumentResource::collection($this->documents)),
            'created_at' => $this->created_at?->format('d/m/Y'),
        ];
    }
}
