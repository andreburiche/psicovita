<?php

namespace App\Support;

use App\Models\TherapySession;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class TherapySessionSchedule
{
    public static function formatTime(TherapySession|string|null $sessionOrTime): string
    {
        $raw = $sessionOrTime instanceof TherapySession
            ? $sessionOrTime->session_time
            : $sessionOrTime;

        if ($raw === null || $raw === '') {
            return '';
        }

        if (is_string($raw) && strlen($raw) >= 5) {
            return substr($raw, 0, 5);
        }

        return (string) $raw;
    }

    public static function startsAt(TherapySession $session, ?CarbonInterface $date = null): Carbon
    {
        $day = $date ?? $session->session_date ?? Carbon::today();

        $time = self::formatTime($session);
        if ($time === '') {
            return Carbon::parse($day->toDateString())->startOfDay();
        }

        return Carbon::parse($day->toDateString().' '.$time);
    }
}
