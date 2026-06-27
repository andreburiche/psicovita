<?php

namespace App\Support;

use App\Models\ScheduleBlock;
use App\Models\TherapySession;
use App\Services\TherapySessionReportService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class MonthGridCalendar
{
    /**
     * @param  array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}  $filters
     * @return array{month: Carbon, weeks: list<list<array{date: Carbon, in_month: bool, sessions: Collection<int, TherapySession>, blocks: Collection<int, ScheduleBlock>}>>, blocksInMonth: Collection<int, ScheduleBlock>}
     */
    public static function forProfessional(int $professionalId, ?string $monthQuery, array $filters = []): array
    {
        $month = Carbon::parse($monthQuery ?: now()->format('Y-m'))->startOfMonth();
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $gridStart = $monthStart->copy()->startOfWeek(CarbonInterface::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(CarbonInterface::SUNDAY);

        $sessionsQuery = TherapySession::query()
            ->where('professional_id', $professionalId)
            ->whereBetween('session_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->with('patient')
            ->orderBy('session_time');

        if ($filters !== []) {
            app(TherapySessionReportService::class)->applyFilters($sessionsQuery, $filters);
        }

        $sessions = $sessionsQuery
            ->get()
            ->groupBy(fn (TherapySession $s) => $s->session_date->format('Y-m-d'));

        $blocks = ScheduleBlock::query()
            ->where('professional_id', $professionalId)
            ->whereBetween('block_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->get()
            ->groupBy(fn (ScheduleBlock $b) => $b->block_date->format('Y-m-d'));

        $weeks = self::buildWeeks($gridStart, $gridEnd, $monthStart, $monthEnd, $sessions, $blocks);

        $blocksInMonth = ScheduleBlock::query()
            ->where('professional_id', $professionalId)
            ->whereBetween('block_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('block_date')
            ->orderBy('start_time')
            ->get();

        return [
            'month' => $month,
            'weeks' => $weeks,
            'blocksInMonth' => $blocksInMonth,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, TherapySession>>  $sessionsByDate
     * @param  Collection<string, Collection<int, ScheduleBlock>>  $blocksByDate
     * @return list<list<array{date: Carbon, in_month: bool, sessions: Collection<int, TherapySession>, blocks: Collection<int, ScheduleBlock>}>>
     */
    public static function buildWeeks(
        Carbon $gridStart,
        Carbon $gridEnd,
        Carbon $monthStart,
        Carbon $monthEnd,
        Collection $sessionsByDate,
        Collection $blocksByDate,
    ): array {
        $weeks = [];
        $current = $gridStart->copy();
        $row = [];

        while ($current->lte($gridEnd)) {
            $key = $current->format('Y-m-d');
            $row[] = [
                'date' => $current->copy(),
                'in_month' => $current->between($monthStart, $monthEnd),
                'sessions' => $sessionsByDate->get($key, collect()),
                'blocks' => $blocksByDate->get($key, collect()),
            ];

            if (count($row) === 7) {
                $weeks[] = $row;
                $row = [];
            }

            $current->addDay();
        }

        return $weeks;
    }
}
