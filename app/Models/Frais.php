<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Frais de scolarité (inscription, scolarité, transport, tenues...).
 */
class Frais extends Model
{
    use HasTenant, SoftDeletes;

    public const PERIODICITE_ANNEE = 'annee';
    public const PERIODICITE_TRIMESTRE = 'trimestre';
    public const PERIODICITE_MENSUEL = 'mensuel';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'libelle',
        'montant',
        'periodicite',
        'cycle_id',
        'classe_id',
        'actif',
    ];

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
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
            'montant' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }
}