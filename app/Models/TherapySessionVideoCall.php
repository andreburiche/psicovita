<?php

namespace App\Models;

use App\Enums\VideoCallStatus;
use App\Enums\VideoRecordingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TherapySessionVideoCall extends Model
{
    protected $fillable = [
        'therapy_session_id',
        'room_name',
        'guest_token',
        'status',
        'recording_status',
        'recording_disk',
        'recording_path',
        'recording_size_bytes',
        'approach',
        'transcription_text',
        'clinical_summary_text',
        'devolutiva_patient_text',
        'transcription_ai_request_id',
        'devolutiva_ai_request_id',
        'recording_consent_at',
        'recording_consent_ip',
        'started_at',
        'ended_at',
        'processing_error',
    ];

    protected function casts(): array
    {
        return [
            'status' => VideoCallStatus::class,
            'recording_status' => VideoRecordingStatus::class,
            'transcription_text' => 'encrypted',
            'clinical_summary_text' => 'encrypted',
            'devolutiva_patient_text' => 'encrypted',
            'recording_consent_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function therapySession(): BelongsTo
    {
        return $this->belongsTo(TherapySession::class);
    }

    public function transcriptionAiRequest(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class, 'transcription_ai_request_id');
    }

    public function devolutivaAiRequest(): BelongsTo
    {
        return $this->belongsTo(AiRequest::class, 'devolutiva_ai_request_id');
    }

    public function isProcessing(): bool
    {
        return in_array($this->recording_status, [
            VideoRecordingStatus::Uploaded,
            VideoRecordingStatus::Processing,
        ], true);
    }

    public function isReadyForReview(): bool
    {
        return $this->recording_status === VideoRecordingStatus::Completed;
    }
}
