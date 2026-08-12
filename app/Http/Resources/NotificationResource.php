<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API d'une notification.
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'titre' => $this->titre,
            'message' => $this->message,
            'canal' => $this->canal,
            'lue' => $this->lue,
            'date' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}