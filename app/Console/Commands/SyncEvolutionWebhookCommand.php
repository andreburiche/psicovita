<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\EvolutionWebhookSetupService;
use Illuminate\Console\Command;

class SyncEvolutionWebhookCommand extends Command
{
    protected $signature = 'psiconecta:evolution-webhook-sync';

    protected $description = 'Configura o webhook da Evolution API para enviar mensagens ao PsiConecta';

    public function handle(EvolutionWebhookSetupService $setup): int
    {
        $result = $setup->sync();

        $this->line('URL: '.($result['url'] ?? '—'));

        if ($result['ok']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
