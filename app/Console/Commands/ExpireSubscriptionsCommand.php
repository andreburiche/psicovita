<?php

namespace App\Console\Commands;

use App\Services\ClinicTeamService;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'psiconecta:expire-subscriptions';

    protected $description = 'Marca assinaturas expiradas (trial ou período pago terminado).';

    public function handle(SubscriptionService $subscriptions, ClinicTeamService $clinicTeams): int
    {
        $expired = $subscriptions->expireDueSubscriptions();

        $this->info(__('Assinaturas expiradas: :count', ['count' => $expired]));

        $released = $clinicTeams->releaseUnavailableTeams();
        if ($released > 0) {
            $this->info(__('Membros de equipa desligados: :count', ['count' => $released]));
        }

        return self::SUCCESS;
    }
}
