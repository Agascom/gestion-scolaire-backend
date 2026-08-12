<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bulletin trimestriel ou annuel d'un élève.
 */
class Bulletin extends Model
{
    use HasTenant, SoftDeletes;

    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_PUBLIE = 'publie';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'annee_academique_id',
        'trimestre_id',
        'eleve_id',
        'classe_id',
        'moyenne_generale',
        'rang',
        'mention',
        'appreciation',
        'pdf_path',
        'statut',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function trimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'moyenne_generale' => 'decimal:2',
        ];
    }
}