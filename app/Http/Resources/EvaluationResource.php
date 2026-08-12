<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'une évaluation (interrogation, devoir, composition, examen).
 */
class EvaluationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'libelle' => $this->libelle,
            'date_evaluation' => $this->date_evaluation?->format('d/m/Y'),
            'note_sur' => $this->note_sur,
            'publiee' => $this->publiee,
            'classe' => $this->whenLoaded('classe', $this->classe?->libelle),
            'matiere' => $this->whenLoaded('matiere', new MatiereResource($this->matiere)),
            'trimestre' => $this->whenLoaded('trimestre', $this->trimestre?->libelle),
            'notes_count' => $this->whenCounted('notes', $this->notes_count),
        ];
    }
}