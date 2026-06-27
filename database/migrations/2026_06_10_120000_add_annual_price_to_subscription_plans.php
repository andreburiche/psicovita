<?php

use App\Enums\SubscriptionPlanSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('annual_price_cents')->default(0)->after('price_cents');
        });

        $annualBySlug = [
            SubscriptionPlanSlug::Essencial->value => 99000,
            SubscriptionPlanSlug::Premium->value => 149000,
            SubscriptionPlanSlug::Clinica->value => 299000,
        ];

        foreach ($annualBySlug as $slug => $annualCents) {
            DB::table('subscription_plans')
                ->where('slug', $slug)
                ->update(['annual_price_cents' => $annualCents]);
        }
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('annual_price_cents');
        });
    }
};
