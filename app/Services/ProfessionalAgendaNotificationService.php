<?php

namespace App\Services;

use App\Enums\TherapySessionStatus;
use App\Enums\UserRole;
use App\Models\TherapySession;
use App\Models\User;
use App\Notifications\ProfessionalDailyAgendaNotification;
use App\Notifications\ProfessionalUpcomingSessionReminderNotification;
use App\Support\TherapySessionSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProfessionalAgendaNotificationService
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly WhatsAppTransactionalService $whatsApp,
    ) {}

    public function sendDailyBriefings(?Carbon $date = null): int
    {
        if (! config('psiconecta.agenda.daily_briefing_enabled', true)) {
            return 0;
        }

        $agendaDate = ($date ?? now())->copy()->startOfDay();
        $sent = 0;

        foreach ($this->activeProfessionals() as $professional) {
            $sessions = $this->dashboard->todayAgenda($professional)
                ->filter(fn (TherapySession $session) => $session->status === TherapySessionStatus::Scheduled);

            if ($sessions->isEmpty()) {
                continue;
            }

            if ($this->alreadySentDailyBriefing($professional, $agendaDate)) {
                continue;
            }

            $items = $this->mapSessionsForNotification($sessions);
            $summary = $this->buildDailySummary($agendaDate, $sessions);

            $professional->notify(new ProfessionalDailyAgendaNotification($agendaDate, $items, $summary));
            $this->sendDailyBriefingWhatsApp($professional, $agendaDate, $items, $summary);
            $sent++;

            if (! app()->environment('testing')) {
                Log::info('PsiConecta: briefing diário da agenda enviado', [
                    'professional_id' => $professional->id,
                    'agenda_date' => $agendaDate->toDateString(),
                    'sessions_count' => $sessions->count(),
                ]);
            }
        }

        return $sent;
    }

    public function sendUpcomingReminders(?Carbon $now = null): int
    {
        if (! config('psiconecta.agenda.upcoming_reminder_enabled', true)) {
            return 0;
        }

        $now = ($now ?? now())->copy();
        $minutesBefore = max(1, (int) config('psiconecta.agenda.upcoming_reminder_minutes', 10));
        $sent = 0;

        $sessions = TherapySession::query()
            ->with(['patient:id,name', 'professional'])
            ->whereDate('session_date', $now->toDateString())
            ->where('status', TherapySessionStatus::Scheduled)
            ->orderBy('session_time')
            ->get();

        foreach ($sessions as $session) {
            if (! $this->isWithinUpcomingWindow($session, $now, $minutesBefore)) {
                continue;
            }

            $professional = $session->professional;
            if (! $professional instanceof User || ! $professional->isProfessional()) {
                continue;
            }

            if ($this->alreadySentUpcomingReminder($professional, $session, $now)) {
                continue;
            }

            $professional->notify(new ProfessionalUpcomingSessionReminderNotification($session, $minutesBefore));
            $this->sendUpcomingWhatsApp($professional, $session, $minutesBefore);
            $sent++;

            if (! app()->environment('testing')) {
                Log::info('PsiConecta: lembrete de sessão iminente enviado', [
                    'therapy_session_id' => $session->id,
                    'professional_id' => $professional->id,
                    'minutes_before' => $minutesBefore,
                ]);
            }
        }

        return $sent;
    }

    /**
     * @return Collection<int, User>
     */
    private function activeProfessionals(): Collection
    {
        return User::query()
            ->where('role', UserRole::Professional)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, TherapySession>  $sessions
     * @return list<array{time: string, label: string, mode: ?string, status: string}>
     */
    private function mapSessionsForNotification(Collection $sessions): array
    {
        return $sessions
            ->map(fn (TherapySession $session) => [
                'time' => TherapySessionSchedule::formatTime($session),
                'label' => $session->displayLabel(),
                'mode' => $session->session_mode?->label(),
                'status' => $session->status->label(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, TherapySession>  $sessions
     */
    private function buildDailySummary(Carbon $agendaDate, Collection $sessions): string
    {
        $count = $sessions->count();
        $firstTime = TherapySessionSchedule::formatTime($sessions->first());

        if ($count === 1) {
            return __('Hoje (:date) você tem 1 sessão agendada, às :time.', [
                'date' => $agendaDate->translatedFormat('d/m/Y'),
                'time' => $firstTime,
            ]);
        }

        return __('Hoje (:date) você tem :count sessões agendadas. A primeira é às :time.', [
            'date' => $agendaDate->translatedFormat('d/m/Y'),
            'count' => $count,
            'time' => $firstTime,
        ]);
    }

    private function isWithinUpcomingWindow(TherapySession $session, Carbon $now, int $minutesBefore): bool
    {
        $startsAt = TherapySessionSchedule::startsAt($session, $now->copy()->startOfDay());
        $reminderAt = $startsAt->copy()->subMinutes($minutesBefore);

        return $now->betweenIncluded(
            $reminderAt->copy()->startOfMinute(),
            $reminderAt->copy()->endOfMinute(),
        );
    }

    private function alreadySentDailyBriefing(User $professional, Carbon $agendaDate): bool
    {
        return $professional->notifications()
            ->where('type', ProfessionalDailyAgendaNotification::class)
            ->where('data->agenda_date', $agendaDate->toDateString())
            ->exists();
    }

    private function alreadySentUpcomingReminder(User $professional, TherapySession $session, Carbon $now): bool
    {
        return $professional->notifications()
            ->where('type', ProfessionalUpcomingSessionReminderNotification::class)
            ->where('data->therapy_session_id', $session->id)
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->exists();
    }

    /**
     * @param  list<array{time: string, label: string, mode: ?string, status: string}>  $items
     */
    private function sendDailyBriefingWhatsApp(User $professional, Carbon $agendaDate, array $items, string $summary): void
    {
        if (! config('psiconecta.agenda.whatsapp_enabled', true)) {
            return;
        }

        $lines = [
            __('Bom dia, :name!', ['name' => $professional->name]),
            '',
            $summary,
            '',
        ];

        foreach ($items as $item) {
            $line = '• '.$item['time'].' — '.$item['label'];
            if (filled($item['mode'] ?? null)) {
                $line .= ' ('.$item['mode'].')';
            }
            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = route('schedule.index', absolute: true);

        $this->whatsApp->sendProfessionalText($professional, implode("\n", $lines));
    }

    private function sendUpcomingWhatsApp(User $professional, TherapySession $session, int $minutesBefore): void
    {
        if (! config('psiconecta.agenda.whatsapp_enabled', true)) {
            return;
        }

        $time = TherapySessionSchedule::formatTime($session);
        $patient = $session->displayLabel();

        $body = implode("\n", [
            __('Olá, :name!', ['name' => $professional->name]),
            '',
            __('Em :minutes minutos: sessão com :patient às :time.', [
                'minutes' => $minutesBefore,
                'patient' => $patient,
                'time' => $time,
            ]),
            '',
            route('therapy-sessions.show', $session, absolute: true),
        ]);

        $this->whatsApp->sendProfessionalText($professional, $body);
    }
}
