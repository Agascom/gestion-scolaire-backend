<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Scope Eloquent global pour l'isolation multi-écoles.
 *
 * PRINCIPE :
 * Chaque modèle "tenant" possède une colonne `school_id`. Ce scope ajoute
 * automatiquement un filtre `WHERE school_id = <école du user connecté>`
 * sur toutes les requêtes (select, update, delete) du modèle.
 *
 * - Le `school_id` est résolu depuis l'utilisateur authentifié (Sanctum)
 *   via le helper `auth()->user()?->school_id`.
 * - En console (seeders, jobs...) aucun utilisateur n'est connecté :
 *   le scope est inactif, ce qui permet d'agir sur toutes les écoles.
 * - Le scope est inactif — par nature — pour la table `schools` (racine),
 *   dont la filtration est gérée dans les controllers selon le rôle.
 */
class TenantScope implements Scope
{
    /**
     * Applique la contrainte d'isolation par école.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($model instanceof \App\Models\School) {
            return;
        }

        $schoolId = $model->school_id ?? auth()->user()?->school_id;

        if (empty($schoolId)) {
            return;
        }

        $builder->where($model->qualifyColumn('school_id'), $schoolId);
    }
}