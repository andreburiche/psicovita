<?php

namespace App\Services;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Models\AiRequest;
use App\Models\DataSubjectRequest;
use Illuminate\Support\Facades\DB;

class LgpdMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(): array
    {
        $now = now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        $statusCounts = DataSubjectRequest::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byType = DataSubjectRequest::query()
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $completedLast30 = DataSubjectRequest::query()
            ->where('status', DataSubjectRequestStatus::Completed)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $pending = (int) ($statusCounts[DataSubjectRequestStatus::Pending->value] ?? 0);
        $inProgress = (int) ($statusCounts[DataSubjectRequestStatus::InProgress->value] ?? 0);
        $completed = (int) ($statusCounts[DataSubjectRequestStatus::Completed->value] ?? 0);
        $rejected = (int) ($statusCounts[DataSubjectRequestStatus::Rejected->value] ?? 0);

        $resolved = DataSubjectRequest::query()
            ->whereNotNull('completed_at')
            ->get(['created_at', 'completed_at']);

        $avgResolutionDays = $resolved->isEmpty()
            ? null
            : $resolved->avg(fn ($r) => $r->created_at->diffInDays($r->completed_at));

        $monthly = $this->requestsPerMonth(6);
        $aiConsents = AiRequest::query()->whereNotNull('lgpd_consent_at')->count();
        $patientsWithCpf = DB::table('patients')->whereNotNull('cpf_hash')->count();

        return [
            'totals' => [
                'all' => DataSubjectRequest::query()->count(),
                'pending' => $pending,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'rejected' => $rejected,
                'completed_last_30_days' => $completedLast30,
                'ai_consents' => $aiConsents,
                'patients_with_cpf_encrypted' => $patientsWithCpf,
            ],
            'avg_resolution_days' => $avgResolutionDays !== null ? round((float) $avgResolutionDays, 1) : null,
            'by_type' => collect(DataSubjectRequestType::cases())->mapWithKeys(fn ($type) => [
                $type->value => [
                    'label' => $type->label(),
                    'count' => (int) ($byType[$type->value] ?? 0),
                ],
            ])->all(),
            'monthly' => $monthly,
            'sla_days' => (int) config('compliance.lgpd.response_sla_days', 15),
            'retention_days' => (int) config('compliance.retention.data_subject_requests_days', 730),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function requestsPerMonth(int $months): array
    {
        $labels = [];
        $values = [];
        $start = now()->startOfMonth()->subMonths($months - 1);

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->translatedFormat('M Y');
            $values[] = DataSubjectRequest::query()
                ->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
