<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoria de acesso a dados sensíveis do prontuário (LGPD).
     */
    public function up(): void
    {
        Schema::create('record_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_record_id')->constrained()->cascadeOnDelete();
            $table->string('action', 64);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['clinical_record_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_access_logs');
    }
};
