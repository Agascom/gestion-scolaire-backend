<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Encaissement : paiement (espèces, mobile money, virement, chèque) d'un parent.
 */
class Encaissement extends Model
{
    use HasTenant, SoftDeletes;

    public const MODE_ESPECES = 'especes';
    public const MODE_MOBILE_MONEY = 'mobile_money';
    public const MODE_VIREMENT = 'virement';
    public const MODE_CHEQUE = 'cheque';

    public const STATUT_PAYE = 'paye';
    public const STATUT_PARTIEL = 'partiel';
    public const STATUT_EN_ATTENTE = 'en_attente';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'annee_academique_id',
        'eleve_id',
        'frais_id',
        'montant',
        'mode',
        'reference',
        'statut',
        'date_encaissement',
        'numero_recu',
        'notes',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function frais(): BelongsTo
    {
        return $this->belongsTo(Frais::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'montant' => 'decimal:2',
            'date_encaissement' => 'date',
        ];
    }
}