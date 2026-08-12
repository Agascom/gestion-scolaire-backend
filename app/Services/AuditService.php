<?php

namespace App\Services;

use App\Models\AuditLog;

/**
 * Journal d'audit centralisé pour les actions sensibles.
 */
class AuditService
{
    /**
     * Consigne une action sensible dans le journal d'audit.
     */
    public function log(string $module, string $action, string $description = null, int $schoolId = null): AuditLog
    {
        $user = auth()->user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'school_id' => $schoolId ?? $user?->school_id,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}