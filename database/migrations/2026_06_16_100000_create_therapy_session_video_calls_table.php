<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('therapy_session_video_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('therapy_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('room_name', 120);
            $table->string('guest_token', 64)->unique();
            $table->string('status', 32)->default('pending');
            $table->string('recording_status', 32)->default('none');
            $table->string('recording_disk', 32)->nullable();
            $table->string('recording_path')->nullable();
            $table->unsignedInteger('recording_size_bytes')->nullable();
            $table->string('approach', 32)->default('tcc');
            $table->text('transcription_text')->nullable();
            $table->text('clinical_summary_text')->nullable();
            $table->text('devolutiva_patient_text')->nullable();
            $table->foreignId('transcription_ai_request_id')->nullable()->constrained('ai_requests')->nullOnDelete();
            $table->foreignId('devolutiva_ai_request_id')->nullable()->constrained('ai_requests')->nullOnDelete();
            $table->timestamp('recording_consent_at')->nullable();
            $table->string('recording_consent_ip', 45)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('therapy_session_video_calls');
    }
};
