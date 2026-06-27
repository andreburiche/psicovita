<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('professional_last_read_at')->nullable();
            $table->timestamp('patient_last_read_at')->nullable();
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('whatsapp_phone_hash')->nullable();
            $table->timestamps();

            $table->unique(['professional_id', 'patient_user_id']);
            $table->index(['professional_id', 'last_message_at']);
            $table->index(['patient_user_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
