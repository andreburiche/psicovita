<?php

namespace App\Services;

use App\Models\DocumentRequest;
use App\Models\DocumentRequestAccessLog;
use App\Models\User;
use Illuminate\Http\Request;

class DocumentRequestAccessLogService
{
    public function record(DocumentRequest $documentRequest, string $action, ?User $user = null, ?Request $request = null): void
    {
        $user ??= auth()->user();
        $request ??= request();

        DocumentRequestAccessLog::query()->create([
            'document_request_id' => $documentRequest->id,
            'user_id' => $user?->id ?? 0,
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
