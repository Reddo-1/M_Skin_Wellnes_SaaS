<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Services\AdminAuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private readonly AdminAuditLogService $auditLogs)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['center_id', 'action', 'from', 'to']);
        $logs = $this->auditLogs->list($filters);
        $centers = Center::query()->orderBy('name')->get(['id', 'name']);
        $actions = $this->auditLogs->availableActions();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'centers' => $centers,
            'actions' => $actions,
            'filters' => $filters,
        ]);
    }
}
