<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminAuditLogService
{
    public function list(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->with(['center:id,name', 'actor:id,name,email']);

        if (isset($filters['center_id']) && $filters['center_id'] !== '') {
            $query->where('center_id', (int) $filters['center_id']);
        }

        if (isset($filters['action']) && $filters['action'] !== '') {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['from']) && $filters['from'] !== '') {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (isset($filters['to']) && $filters['to'] !== '') {
            $query->where('created_at', '<=', $filters['to'].' 23:59:59');
        }

        return $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    public function availableActions(): array
    {
        return AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }
}
