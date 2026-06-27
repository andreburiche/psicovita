<?php

namespace App\Services;

use App\Enums\TherapySessionStatus;
use App\Models\ScheduleBlock;
use App\Models\TherapySession;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class ScheduleConflictService
{
    /** Duração padrão estimada da sessão para detecção de sobreposição (minutos). */
    public const DEFAULT_SESSION_DURATION_MINUTES = 50;

    public function hasConflict(
        int $professionalId,
        string $sessionDate,
        string $sessionTime,
        ?int $excludeTherapySessionId = null,
        ?TherapySessionStatus $status = null,
    ): bool {
        if ($status === TherapySessionStatus::Cancelled) {
            return false;
        }

        $start = $this->parseSlotStart($sessionDate, $sessionTime);
        $end = (clone $start)->addMinutes(self::DEFAULT_SESSION_DURATION_MINUTES);

        if ($this->overlapsBlockedSlot($professionalId, $start, $end)) {
            return true;
        }

        return $this->overlapsAnotherSession($professionalId, $start, $end, $excludeTherapySessionId);
    }

    private function parseSlotStart(string $date, string $time): CarbonInterface
    {
        $time = strlen($time) === 5 ? $time.':00' : $time;

        return Carbon::parse($date.' '.$time);
    }

    private function overlapsBlockedSlot(int $professionalId, CarbonInterface $start, CarbonInterface $end): bool
    {
        $blocks = ScheduleBlock::query()
            ->where('professional_id', $professionalId)
            ->whereDate('block_date', $start->toDateString())
            ->get();

        foreach ($blocks as $block) {
            $bStart = $this->parseSlotStart($block->block_date->format('Y-m-d'), (string) $block->start_time);
            $bEnd = $this->parseSlotStart($block->block_date->format('Y-m-d'), (string) $block->end_time);

            if ($start < $bEnd && $end > $bStart) {
                return true;
            }
        }

        return false;
    }

    private function overlapsAnotherSession(
        int $professionalId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $excludeTherapySessionId,
    ): bool {
        $sessions = TherapySession::query()
            ->where('professional_id', $professionalId)
            ->whereDate('session_date', $start->toDateString())
            ->whereNot('status', TherapySessionStatus::Cancelled)
            ->when($excludeTherapySessionId, fn ($q) => $q->whereKeyNot($excludeTherapySessionId))
            ->get(['id', 'session_date', 'session_time']);

        foreach ($sessions as $session) {
            $sStart = $this->parseSlotStart(
                $session->session_date->format('Y-m-d'),
                (string) $session->session_time
            );
            $sEnd = (clone $sStart)->addMinutes(self::DEFAULT_SESSION_DURATION_MINUTES);

            if ($start < $sEnd && $end > $sStart) {
                return true;
            }
        }

        return false;
    }
}
