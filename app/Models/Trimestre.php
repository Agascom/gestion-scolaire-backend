<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Trimestre (période) rattaché à une année académique.
 */
class Trimestre extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'annee_academique_id',
        'numero',
        'libelle',
        'date_debut',
        'date_fin',
        'cloture',
    ];

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    /**
     * @return HasMany<Evaluation>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'cloture' => 'boolean',
        ];
    }
}