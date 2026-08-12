<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Absence d'élève (classe_id) ou d'enseignant (enseignant_id).
 */
class Absence extends Model
{
    use HasTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'annee_academique_id',
        'classe_id',
        'eleve_id',
        'enseignant_id',
        'date_absence',
        'motif',
        'justifiee',
        'notes',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_absence' => 'date',
            'justifiee' => 'boolean',
        ];
    }
}