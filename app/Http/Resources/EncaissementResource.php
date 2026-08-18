<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'un encaissement (paiement parent).
 */
class EncaissementResource extends JsonResource
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
            'frais' => $this->whenLoaded('frais', $this->frais?->libelle),
            'montant' => (float) $this->montant,
            'mode' => $this->mode,
            'reference' => $this->reference,
            'statut' => $this->statut,
            'date_encaissement' => $this->date_encaissement?->format('d/m/Y'),
            'numero_recu' => $this->numero_recu,
            'notes' => $this->notes,
        ];
    }
}