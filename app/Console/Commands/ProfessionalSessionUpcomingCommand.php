<?php

namespace App\Console\Commands;

use App\Services\ProfessionalAgendaNotificationService;
use Illuminate\Console\Command;

class ProfessionalSessionUpcomingCommand extends Command
{
    protected $signature = 'psiconecta:professional-session-upcoming';

    protected $description = 'Lembra o profissional alguns minutos antes de cada sessão agendada.';

    public function handle(ProfessionalAgendaNotificationService $agenda): int
    {
        $sent = $agenda->sendUpcomingReminders();

        $this->info(__('Lembretes iminentes enviados: :count', ['count' => $sent]));

        return self::SUCCESS;
    }
}
