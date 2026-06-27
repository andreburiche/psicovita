<?php

namespace App\Models;

use App\Enums\SessionParticipantRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionParticipant extends Model
{
    protected $fillable = [
        'therapy_session_id',
        'role',
        'user_id',
        'patient_id',
        'display_name',
        'email',
        'guest_token',
        'join_consent_at',
        'recording_consent_at',
        'recording_consent_ip',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => SessionParticipantRole::class,
            'join_consent_at' => 'datetime',
            'recording_consent_at' => 'datetime',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function therapySession(): BelongsTo
    {
        return $this->belongsTo(TherapySession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function hasRecordingConsent(): bool
    {
        return $this->recording_consent_at !== null;
    }

    public function joinUrl(): ?string
    {
        if ($this->guest_token === null || $this->guest_token === '') {
            return null;
        }

        return route('session-video.guest', ['token' => $this->guest_token]);
    }
}
