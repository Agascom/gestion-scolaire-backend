<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Salaire mensuel d'un enseignant (base + primes - avances - retenues).
 */
class Salaire extends Model
{
    use HasTenant, SoftDeletes;

    public const STATUT_PAYE = 'paye';
    public const STATUT_EN_ATTENTE = 'en_attente';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'annee_academique_id',
        'enseignant_id',
        'mois',
        'salaire_base',
        'primes',
        'avances',
        'retenues',
        'net_a_payer',
        'statut',
        'date_paiement',
    ];

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salaire_base' => 'decimal:2',
            'primes' => 'decimal:2',
            'avances' => 'decimal:2',
            'retenues' => 'decimal:2',
            'net_a_payer' => 'decimal:2',
            'date_paiement' => 'date',
        ];
    }
}