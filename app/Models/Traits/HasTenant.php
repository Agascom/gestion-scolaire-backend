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
 *  2. auto-remplit `school_id` à la création depuis l'utilisateur connecté.
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
            if (empty($model->school_id)) {
                $model->school_id = auth()->user()?->school_id;
            }
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