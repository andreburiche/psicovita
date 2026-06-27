<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sessões clínicas (tabela therapy_sessions: a tabela `sessions` do Laravel
     * já é usada para sessão HTTP).
     */
    public function up(): void
    {
        Schema::create('therapy_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->date('session_date');
            $table->time('session_time');
            $table->string('status', 32)->default('scheduled')->index();
            $table->string('type', 32)->default('online');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['professional_id', 'session_date', 'session_time'], 'therapy_sess_pro_date_time_idx');
            $table->index(['patient_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_sessions');
    }
};
