<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('pix_manual_link')->nullable()->after('asaas_wallet_id');
            $table->string('pix_qrcode_path')->nullable()->after('pix_manual_link');
            $table->string('payment_method_preference', 20)->default('auto')->after('pix_qrcode_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pix_manual_link', 'pix_qrcode_path', 'payment_method_preference']);
        });
    }
};
