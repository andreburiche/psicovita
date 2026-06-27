<?php

use App\Enums\SubscriptionPlanSlug;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $plans = [
            [
                'slug' => SubscriptionPlanSlug::Trial->value,
                'name' => 'Trial',
                'price_cents' => 0,
                'trial_days' => 14,
                'max_patients' => 10,
                'features' => json_encode([
                    'create_patient',
                    'create_session',
                    'create_clinical_record',
                    'use_ai',
                ]),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => SubscriptionPlanSlug::Essencial->value,
                'name' => 'Essencial',
                'price_cents' => 9900,
                'trial_days' => 0,
                'max_patients' => 50,
                'features' => json_encode([
                    'create_patient',
                    'create_session',
                    'create_clinical_record',
                ]),
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => SubscriptionPlanSlug::Premium->value,
                'name' => 'Premium',
                'price_cents' => 14900,
                'trial_days' => 0,
                'max_patients' => null,
                'features' => json_encode([
                    'create_patient',
                    'create_session',
                    'create_clinical_record',
                    'use_ai',
                ]),
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => SubscriptionPlanSlug::Clinica->value,
                'name' => 'Clínica',
                'price_cents' => 29900,
                'trial_days' => 0,
                'max_patients' => null,
                'features' => json_encode([
                    'create_patient',
                    'create_session',
                    'create_clinical_record',
                    'use_ai',
                    'multi_user',
                ]),
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('subscription_plans')->insert($plans);

        $trialPlanId = DB::table('subscription_plans')
            ->where('slug', SubscriptionPlanSlug::Trial->value)
            ->value('id');

        if ($trialPlanId === null) {
            return;
        }

        $trialDays = (int) config('subscription.trial_days', 14);
        $startsAt = $now;
        $trialEndsAt = $now->copy()->addDays($trialDays);

        $existingUserIds = DB::table('professional_subscriptions')->pluck('user_id');

        $professionalIds = DB::table('users')
            ->where('role', UserRole::Professional->value)
            ->when($existingUserIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $existingUserIds))
            ->pluck('id');

        $rows = $professionalIds->map(fn (int $userId) => [
            'user_id' => $userId,
            'subscription_plan_id' => $trialPlanId,
            'status' => SubscriptionStatus::Trialing->value,
            'starts_at' => $startsAt,
            'ends_at' => null,
            'trial_ends_at' => $trialEndsAt,
            'gateway_external_id' => null,
            'cancelled_at' => null,
            'created_at' => $startsAt,
            'updated_at' => $startsAt,
        ])->all();

        if ($rows !== []) {
            DB::table('professional_subscriptions')->insert($rows);
        }
    }

    public function down(): void
    {
        DB::table('professional_subscriptions')->truncate();
        DB::table('subscription_plans')->truncate();
    }
};
