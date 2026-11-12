<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Records audit trail events for compliance, billing, and practice actions.
 */
class ActivityLogger
{
    public function log(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'module' => $module,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => empty($properties) ? null : $properties,
            'ip_address' => request()?->ip(),
        ]);
    }
}

