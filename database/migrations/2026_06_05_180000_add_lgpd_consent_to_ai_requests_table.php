<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_requests', function (Blueprint $table) {
            $table->timestamp('lgpd_consent_at')->nullable()->after('tokens_used');
            $table->string('lgpd_consent_ip', 45)->nullable()->after('lgpd_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_requests', function (Blueprint $table) {
            $table->dropColumn(['lgpd_consent_at', 'lgpd_consent_ip']);
        });
    }
};
