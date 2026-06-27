<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('asaas_customer_id')->nullable()->after('whatsapp_notifications');
        });

        Schema::table('professional_subscriptions', function (Blueprint $table) {
            $table->json('gateway_meta')->nullable()->after('gateway_external_id');
        });
    }

    public function down(): void
    {
        Schema::table('professional_subscriptions', function (Blueprint $table) {
            $table->dropColumn('gateway_meta');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('asaas_customer_id');
        });
    }
};
