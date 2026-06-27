<?php

namespace App\Console\Commands;

use App\Enums\TherapySessionStatus;
use App\Models\TherapySession;
use App\Models\User;
use App\Notifications\TherapySessionTomorrowReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SessionRemindersCommand extends Command
{
    protected $signature = 'psiconecta:session-reminders';

    protected $description = 'Notifica profissionais sobre sessões agendadas para o dia seguinte (18h, uma vez por sessão).';

    public function handle(): int
    {
        $date = now()->addDay()->toDateString();

        $sessions = TherapySession::query()
            ->with(['patient:id,name', 'professional'])
            ->whereDate('session_date', $date)
            ->where('status', TherapySessionStatus::Scheduled)
            ->get();

        $sent = 0;

        foreach ($sessions as $session) {
            $professional = $session->professional;
            if (! $professional instanceof User) {
                continue;
            }

            if ($this->alreadyNotifiedToday($professional, $session)) {
                continue;
            }

            $professional->notify(new TherapySessionTomorrowReminder($session));
            $sent++;

            if (! app()->environment('testing')) {
                Log::info('PsiConecta: lembrete de sessão enviado', [
                    'therapy_session_id' => $session->id,
                    'session_date' => $session->session_date?->toDateString(),
                    'session_time' => $session->session_time,
                    'professional_id' => $session->professional_id,
                    'patient' => $session->patient?->name,
                ]);
            }
        }

        $this->info(sprintf('%d notificação(ões) enviada(s) (%d sessão(ões) para %s).', $sent, $sessions->count(), $date));

        return self::SUCCESS;
    }

    private function alreadyNotifiedToday(User $professional, TherapySession $session): bool
    {
        $type = TherapySessionTomorrowReminder::class;

        return $professional->notifications()
            ->where('type', $type)
            ->whereDate('created_at', now()->toDateString())
            ->get()
            ->contains(fn ($n) => (int) data_get($n->data, 'therapy_session_id') === $session->id);
    }
}
