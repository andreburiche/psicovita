<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Services\AuditLogQueryService;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly AuditLogQueryService $auditLogs,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DataSubjectRequest::class);

        return view('admin.lgpd.audit.index', [
            'logs' => $this->auditLogs->filteredQuery($request)->paginate(30)->withQueryString(),
            'filters' => $request->only(['entity', 'action', 'q', 'from', 'to']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', DataSubjectRequest::class);

        AuditTrail::entity('export', 'audit_logs', $request->user(), [
            'filters' => $request->only(['entity', 'action', 'q', 'from', 'to']),
        ]);

        return $this->auditLogs->exportCsv($request);
    }
}
