<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait d'isolation multi-écoles pour les modèles disposant d'une colonne `school_id`.
 *
 * Utilisation :
 *   use HasTenant;
 *
 * Au boot, le trait :
 *  1. enregistre le TenantScope (filtre automatique `school_id` à l'écran) ;
 *  2. force `school_id` à l'école de l'utilisateur connecté lors de la
 *     création — sauf pour un superadmin qui peut créer pour n'importe quelle
 *     école. Les valeurs `school_id` venant du client sont donc ignorées
 *     (pas de contournement de l'isolation par écriture directe).
 *
 * Les relations `ecole()` et la portée `pourEcole()` complètent l'outillage.
 */
trait HasTenant
{
    /**
     * Initialise le comportement multi-écoles du modèle.
     */
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            $user = auth()->user();

            // En console (seeders, jobs…) personne n'est connecté : on laisse
            // la valeur fournie par l'appelant.
            if (! $user) {
                return;
            }

            // Un superadmin reste libre de créer pour une autre école.
            if ($user->hasRole('superadmin')) {
                return;
            }

            // Tout autre rôle : l'école est toujours celle de l'utilisateur.
            $model->school_id = $user->school_id;
        });
    }

    /**
     * École à laquelle appartient l'enregistrement.
     */
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Portée explicite : ne conserver que les enregistrements d'une école donnée.
     */
    public function scopePourEcole(Builder $query, int $schoolId): Builder
    {
        return $query->where($this->qualifyColumn('school_id'), $schoolId);
    }
}