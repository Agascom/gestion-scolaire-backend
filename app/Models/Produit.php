<?php

namespace App\Models;

use App\Models\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Produit (cantine, kits, tenues...) — stock de l'école marchande.
 */
class Produit extends Model
{
    use HasTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'libelle',
        'reference',
        'prix_achat',
        'prix_vente',
        'quantite_stock',
        'unite',
        'taux_tva',
        'actif',
    ];

    /**
     * @return HasMany<MouvementStock>
     */
    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prix_achat' => 'decimal:2',
            'prix_vente' => 'decimal:2',
            'quantite_stock' => 'decimal:2',
            'taux_tva' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }
}