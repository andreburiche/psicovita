<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('asaas_customer_id')->nullable()->after('notes');
            $table->index('asaas_customer_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->json('gateway_meta')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('gateway_meta');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['asaas_customer_id']);
            $table->dropColumn('asaas_customer_id');
        });
    }
};
