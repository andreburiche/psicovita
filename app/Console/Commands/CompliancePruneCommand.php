<?php

namespace App\Console\Commands;

use App\Enums\DataSubjectRequestStatus;
use App\Models\AuditLog;
use App\Models\DataSubjectRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CompliancePruneCommand extends Command
{
    protected $signature = 'psiconecta:compliance-prune {--dry-run : Apenas listar registros elegíveis, sem excluir}';

    protected $description = 'Remove registros de conformidade além do prazo de retenção configurado.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $dsrDeleted = $this->pruneDataSubjectRequests($dryRun);
        $auditDeleted = $this->pruneAuditLogs($dryRun);

        if ($dsrDeleted === 0 && $auditDeleted === 0) {
            $this->info(__('Nenhum registro elegível para remoção.'));
        }

        return self::SUCCESS;
    }

    private function pruneDataSubjectRequests(bool $dryRun): int
    {
        $days = (int) config('compliance.retention.data_subject_requests_days', 730);
        $completedOnly = (bool) config('compliance.retention.data_subject_requests_completed_only', true);

        if ($days <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($days);

        $query = DataSubjectRequest::query()
            ->whereNotNull('completed_at')
            ->where('completed_at', '<', $cutoff);

        if ($completedOnly) {
            $query->whereIn('status', [
                DataSubjectRequestStatus::Completed->value,
                DataSubjectRequestStatus::Rejected->value,
            ]);
        }

        $count = (clone $query)->count();
        if ($count === 0) {
            return 0;
        }

        if ($dryRun) {
            $this->info(__(':count solicitação(ões) LGPD seriam removidas.', ['count' => $count]));

            return $count;
        }

        $deleted = $query->delete();
        $this->info(__(':count solicitação(ões) LGPD removida(s).', ['count' => $deleted]));

        return $deleted;
    }

    private function pruneAuditLogs(bool $dryRun): int
    {
        $days = (int) config('compliance.retention.audit_logs_days', 365);
        if ($days <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($days);
        $query = AuditLog::query()->where('created_at', '<', $cutoff);
        $count = (clone $query)->count();

        if ($count === 0) {
            return 0;
        }

        if ($dryRun) {
            $this->info(__(':count registro(s) de auditoria seriam removidos.', ['count' => $count]));

            return $count;
        }

        $deleted = $query->delete();

        if (! app()->environment('testing')) {
            Log::info('compliance.prune', [
                'entity' => 'audit_logs',
                'deleted' => $deleted,
                'retention_days' => $days,
                'cutoff' => $cutoff->toIso8601String(),
            ]);
        }

        $this->info(__(':count registro(s) de auditoria removido(s).', ['count' => $deleted]));

        return $deleted;
    }
}
