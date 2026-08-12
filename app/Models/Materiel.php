<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Entrée de matériel (inventaire) : salle, mobilier, équipement, livre, fourniture.
 */
class Materiel extends Model
{
    use HasTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'categorie',
        'libelle',
        'reference',
        'etat',
        'valeur',
        'emplacement',
        'date_acquisition',
        'duree_amortissement_mois',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valeur' => 'decimal:2',
            'date_acquisition' => 'date',
        ];
    }
}