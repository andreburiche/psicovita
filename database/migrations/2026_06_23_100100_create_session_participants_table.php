<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_session_id')->constrained('therapy_sessions')->cascadeOnDelete();
            $table->string('role', 32);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('display_name');
            $table->string('email')->nullable();
            $table->string('guest_token', 64)->nullable()->unique();
            $table->timestamp('join_consent_at')->nullable();
            $table->timestamp('recording_consent_at')->nullable();
            $table->string('recording_consent_ip', 45)->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->index(['therapy_session_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_participants');
    }
};
