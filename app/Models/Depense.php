<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Dépense de l'école (achat, maintenance, facture, fourniture).
 */
class Depense extends Model
{
    use HasTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'annee_academique_id',
        'nature',
        'libelle',
        'fournisseur',
        'montant',
        'date_depense',
        'piece_jointe_path',
        'notes',
    ];

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
            'montant' => 'decimal:2',
            'date_depense' => 'date',
        ];
    }
}