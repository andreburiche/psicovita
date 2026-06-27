<?php

namespace App\Services;

use App\Enums\TherapySessionStatus;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use Carbon\Carbon;

class ReportService
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    /**
     * @return array{
     *   labels: list<string>,
     *   sessions_per_month: list<int>,
     *   revenue_per_month: list<float>,
     *   active_patients:int
     * }
     */
    public function dashboardCharts(User $professional, int $months = 6): array
    {
        $end = Carbon::now()->endOfMonth();
        $start = Carbon::now()->subMonths($months - 1)->startOfMonth();

        $labels = [];
        $sessionsPerMonth = [];
        $revenuePerMonth = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->translatedFormat('M/y');

            $sessionsPerMonth[] = (int) TherapySession::query()
                ->where('professional_id', $professional->id)
                ->whereYear('session_date', $cursor->year)
                ->whereMonth('session_date', $cursor->month)
                ->whereNot('status', TherapySessionStatus::Cancelled)
                ->count();

            $revenuePerMonth[] = $this->payments->sumPaidRevenueBetween(
                $professional->id,
                $cursor->copy()->startOfMonth(),
                $cursor->copy()->endOfMonth(),
            );

            $cursor->addMonth();
        }

        $since = Carbon::now()->subDays(90);

        $activePatients = Patient::query()
            ->where('professional_id', $professional->id)
            ->whereHas('therapySessions', function ($q) use ($since) {
                $q->where('session_date', '>=', $since->toDateString())
                    ->whereNot('status', TherapySessionStatus::Cancelled);
            })
            ->count();

        return [
            'labels' => $labels,
            'sessions_per_month' => $sessionsPerMonth,
            'revenue_per_month' => $revenuePerMonth,
            'active_patients' => $activePatients,
        ];
    }
}
