<?php

namespace App\Services;

use App\Enums\TherapySessionStatus;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    /**
     * @return array{
     *   patients_count:int,
     *   sessions_today:int,
     *   monthly_revenue:string,
     *   occupancy_rate:float,
     *   pending_payments_count:int,
     *   pending_payments_total:string,
     *   monthly_professional_amount:string,
     *   monthly_platform_fee:string
     * }
     */
    public function summary(User $user): array
    {
        $professionalId = $user->isProfessional() ? $user->clinicalPracticeId() : null;

        if ($professionalId === null) {
            return [
                'patients_count' => 0,
                'sessions_today' => 0,
                'monthly_revenue' => '0,00',
                'occupancy_rate' => 0.0,
                'pending_payments_count' => 0,
                'pending_payments_total' => '0,00',
                'monthly_professional_amount' => '0,00',
                'monthly_platform_fee' => '0,00',
            ];
        }

        $today = Carbon::today();

        $patientsCount = Patient::query()
            ->where('professional_id', $professionalId)
            ->count();

        $sessionsToday = TherapySession::query()
            ->where('professional_id', $professionalId)
            ->whereDate('session_date', $today)
            ->whereNot('status', TherapySessionStatus::Cancelled)
            ->count();

        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        $monthlyRevenue = $this->payments->sumPaidRevenueBreakdownBetween($professionalId, $startOfMonth, $endOfMonth);

        $occupancyRate = $this->occupancyRate($professionalId, $startOfMonth, $endOfMonth);
        $pending = $this->payments->pendingPortalSummary($professionalId);

        return [
            'patients_count' => $patientsCount,
            'sessions_today' => $sessionsToday,
            'monthly_revenue' => number_format($monthlyRevenue['gross'], 2, ',', '.'),
            'occupancy_rate' => round($occupancyRate, 1),
            'pending_payments_count' => $pending['count'],
            'pending_payments_total' => $pending['total_formatted'],
            'monthly_professional_amount' => number_format($monthlyRevenue['professional_amount'], 2, ',', '.'),
            'monthly_platform_fee' => number_format($monthlyRevenue['platform_fee'], 2, ',', '.'),
        ];
    }

    /**
     * Taxa de ocupação: sessões concluídas ou agendadas no mês / slots úteis (dias úteis × 8 vagas ilustrativas).
     * Ajustável quando houver regra de disponibilidade real.
     */
    private function occupancyRate(int $professionalId, Carbon $start, Carbon $end): float
    {
        $sessionsInMonth = TherapySession::query()
            ->where('professional_id', $professionalId)
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->whereNot('status', TherapySessionStatus::Cancelled)
            ->count();

        $workingDays = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $workingDays++;
            }
            $cursor->addDay();
        }

        $illustrativeSlots = max(1, $workingDays * 8);

        return min(100, ($sessionsInMonth / $illustrativeSlots) * 100);
    }

    /**
     * Sessões de hoje para a agenda lateral (horário ascendente).
     *
     * @return Collection<int, TherapySession>
     */
    public function todayAgenda(User $user): Collection
    {
        if (! $user->isProfessional()) {
            return collect();
        }

        return TherapySession::query()
            ->with('patient:id,name')
            ->where('professional_id', $user->clinicalPracticeId())
            ->whereDate('session_date', Carbon::today())
            ->whereNot('status', TherapySessionStatus::Cancelled)
            ->orderBy('session_time')
            ->get();
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    public function sessionTrendLast14Days(User $user): array
    {
        if (! $user->isProfessional()) {
            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];

        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('d/m');
            $values[] = (int) TherapySession::query()
                ->where('professional_id', $user->clinicalPracticeId())
                ->whereDate('session_date', $day)
                ->whereNot('status', TherapySessionStatus::Cancelled)
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Receita paga por dia (últimos 7 dias) para gráfico de barras.
     *
     * @return array{labels: list<string>, values: list<float>}
     */
    public function paidRevenueLast7Days(User $user): array
    {
        if (! $user->isProfessional()) {
            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $labels[] = $day->format('d/m');
            $sum = $this->payments->sumPaidRevenueOnDate($user->clinicalPracticeId(), $day);
            $values[] = round((float) $sum, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
