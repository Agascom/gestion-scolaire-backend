<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Évaluation (interrogation, devoir, composition, examen) d'une classe/matière sur un trimestre.
 */
class Evaluation extends Model
{
    use HasFactory, HasTenant;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'classe_id',
        'matiere_id',
        'trimestre_id',
        'type',
        'libelle',
        'date_evaluation',
        'note_sur',
        'publiee',
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function trimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class);
    }

    /**
     * @return HasMany<Note>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_evaluation' => 'date',
            'publiee' => 'boolean',
        ];
    }
}