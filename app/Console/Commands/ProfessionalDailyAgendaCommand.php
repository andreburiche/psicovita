<?php

namespace App\Console\Commands;

use App\Services\ProfessionalAgendaNotificationService;
use Illuminate\Console\Command;

class ProfessionalDailyAgendaCommand extends Command
{
    protected $signature = 'psiconecta:professional-daily-agenda';

    protected $description = 'Envia às 7h o resumo da agenda do dia para cada profissional com sessões agendadas.';

    public function handle(ProfessionalAgendaNotificationService $agenda): int
    {
        $sent = $agenda->sendDailyBriefings();

        $this->info(__('Briefings diários enviados: :count', ['count' => $sent]));

        return self::SUCCESS;
    }
}
