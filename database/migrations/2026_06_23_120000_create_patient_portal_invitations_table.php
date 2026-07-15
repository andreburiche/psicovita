<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_portal_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            // DATETIME evita ON UPDATE CURRENT_TIMESTAMP do primeiro TIMESTAMP no MySQL
            $table->dateTime('expires_at');
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_portal_invitations');
    }
};
