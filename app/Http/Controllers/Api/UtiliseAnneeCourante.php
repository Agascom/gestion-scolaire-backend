<?php

namespace App\Http\Controllers\Api;

use App\Models\AnneeAcademique;

/**
 * Helpers partagés par les controllers de l'API.
 */
trait UtiliseAnneeCourante
{
    /**
     * Année académique courante de l'utilisateur (non archivée).
     */
    protected function anneeCourante(): ?AnneeAcademique
    {
        $schoolId = $this->schoolId();

        if (! $schoolId) {
            return null;
        }

        return AnneeAcademique::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('school_id', $schoolId)
            ->where('archivee', false)
            ->latest('date_debut')
            ->first();
    }

    /**
     * École de l'utilisateur connecté.
     * Le superadmin (school_id null) doit fournir `school_id` dans le corps de la requête.
     */
    protected function schoolId(): ?int
    {
        $user = auth()->user();

        return $user?->school_id ?? request()->integer('school_id') ?: null;
    }
}