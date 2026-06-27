<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Support\ContactHasher;
use Illuminate\Database\Seeder;

/**
 * Conta local para desenvolvimento (idempotente).
 */
class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email_hash' => ContactHasher::emailHash('admin@psiconecta.local')],
            [
                'name' => 'Admin DPO (desenvolvimento)',
                'email' => 'admin@psiconecta.local',
                'password' => 'password',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email_hash' => ContactHasher::emailHash('prof@psiconecta.local')],
            [
                'name' => 'Profissional (desenvolvimento)',
                'email' => 'prof@psiconecta.local',
                'password' => 'password',
                'role' => UserRole::Professional,
                'email_verified_at' => now(),
            ]
        );

        $professional = User::query()
            ->where('email_hash', ContactHasher::emailHash('prof@psiconecta.local'))
            ->first();

        if ($professional !== null) {
            app(SubscriptionService::class)->startTrial($professional);
        }
    }
}
