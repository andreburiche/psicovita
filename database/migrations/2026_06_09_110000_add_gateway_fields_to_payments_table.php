<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->string('gateway', 32)->default('manual')->after('paid_at');
            $table->string('external_id')->nullable()->after('gateway');
            $table->decimal('platform_fee', 12, 2)->nullable()->after('external_id');
            $table->decimal('professional_amount', 12, 2)->nullable()->after('platform_fee');
            $table->timestamp('refunded_at')->nullable()->after('professional_amount');

            $table->index('paid_at');
            $table->index('gateway');
            $table->index('external_id');
        });

        DB::table('payments')
            ->where('status', 'paid')
            ->whereNull('paid_at')
            ->update(['paid_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['gateway']);
            $table->dropIndex(['external_id']);
            $table->dropColumn([
                'paid_at',
                'gateway',
                'external_id',
                'platform_fee',
                'professional_amount',
                'refunded_at',
            ]);
        });
    }
};
