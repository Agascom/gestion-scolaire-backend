<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Décision de passage en classe supérieure d'un élève pour une année donnée.
 */
class PassageClasse extends Model
{
    use HasTenant, SoftDeletes;

    public const DECISION_ADMIS = 'admis';
    public const DECISION_REDOUBLANT = 'redoublant';
    public const DECISION_SAUT = 'saut_classe';
    public const DECISION_DIPLOME = 'diplome';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'eleve_id',
        'annee_academique_id',
        'classe_source_id',
        'classe_cible_id',
        'moyenne_generale',
        'decision',
        'appreciation',
        'decide_par',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function classeSource(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_source_id');
    }

    public function classeCible(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_cible_id');
    }

    public function decideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decide_par');
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