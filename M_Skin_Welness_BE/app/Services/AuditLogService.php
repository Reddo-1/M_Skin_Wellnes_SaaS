<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public function record(
        string $action,
        ?int $actorUserId = null,
        ?int $centerId = null,
        ?int $planId = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_user_id' => $actorUserId,
            'center_id' => $centerId,
            'plan_id' => $planId,
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }
}
