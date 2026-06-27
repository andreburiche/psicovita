<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\ContactHasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogQueryService
{
    public function filteredQuery(Request $request): Builder
    {
        $query = AuditLog::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('entity')) {
            $query->where('entity', $request->string('entity'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($term, $like) {
                $q->where('action', 'like', $like)
                    ->orWhere('entity', 'like', $like);

                if (filter_var($term, FILTER_VALIDATE_EMAIL)) {
                    $hash = ContactHasher::emailHash(Str::lower(trim($term)));
                    $q->orWhereHas('user', fn ($uq) => $uq
                        ->where('email_hash', $hash)
                        ->orWhere('name', 'like', $like));
                } else {
                    $q->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $like));
                }
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        return $query;
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $filename = 'auditoria-'.now()->format('Y-m-d-His').'.csv';
        $includeEmail = (bool) config('compliance.audit_export.include_user_email', false);
        $includeIp = (bool) config('compliance.audit_export.include_ip_address', true);
        $includeChanges = (bool) config('compliance.audit_export.include_changes_json', true);

        return response()->streamDownload(function () use ($request, $includeEmail, $includeIp, $includeChanges) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                __('Data/hora'),
                __('Ação'),
                __('Entidade'),
                __('Tipo sujeito'),
                __('ID sujeito'),
                __('Utilizador'),
                __('E-mail utilizador'),
                __('IP'),
                __('Alterações (JSON)'),
            ], ';');

            $this->filteredQuery($request)
                ->limit(10000)
                ->chunk(200, function ($logs) use ($handle, $includeEmail, $includeIp, $includeChanges) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $log->created_at?->format('Y-m-d H:i:s'),
                            $log->action,
                            $log->entity,
                            class_basename($log->subject_type),
                            $log->subject_id,
                            $log->user?->name,
                            $includeEmail ? $log->user?->email : '',
                            $includeIp ? $log->ip_address : '',
                            $includeChanges && $log->changes
                                ? json_encode($log->changes, JSON_UNESCAPED_UNICODE)
                                : '',
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
