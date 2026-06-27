<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perfis (admin, professional, patient), vínculo do paciente ao profissional (tenant)
     * e campos opcionais para futuras integrações (ex.: WhatsApp).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('professional')->index();
            $table->foreignId('professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 32)->nullable();
            $table->boolean('whatsapp_notifications')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['professional_id']);
            $table->dropColumn([
                'role',
                'professional_id',
                'phone',
                'whatsapp_notifications',
            ]);
        });
    }
};
