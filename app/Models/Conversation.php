<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'professional_id',
        'patient_user_id',
        'patient_id',
        'last_message_at',
        'professional_last_read_at',
        'patient_last_read_at',
        'whatsapp_enabled',
        'whatsapp_phone_hash',
        'patient_whatsapp_consent_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'professional_last_read_at' => 'datetime',
            'patient_last_read_at' => 'datetime',
            'whatsapp_enabled' => 'boolean',
            'patient_whatsapp_consent_at' => 'datetime',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function patientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_user_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function involvesUser(User $user): bool
    {
        return in_array($user->id, [$this->professional_id, $this->patient_user_id], true);
    }

    public function peerFor(User $user): ?User
    {
        if ($user->id === $this->professional_id) {
            return $this->patientUser;
        }

        if ($user->id === $this->patient_user_id) {
            return $this->professional;
        }

        return null;
    }

    public function lastReadAtFor(User $user): ?\Illuminate\Support\Carbon
    {
        if ($user->id === $this->professional_id) {
            return $this->professional_last_read_at;
        }

        if ($user->id === $this->patient_user_id) {
            return $this->patient_last_read_at;
        }

        return null;
    }

    public function unreadCountFor(User $user): int
    {
        if (! $this->involvesUser($user)) {
            return 0;
        }

        $lastRead = $this->lastReadAtFor($user);

        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }

    public function hasWhatsappConsent(): bool
    {
        return $this->patient_whatsapp_consent_at !== null;
    }

    public function canSyncWhatsApp(): bool
    {
        return $this->whatsapp_enabled && $this->hasWhatsappConsent();
    }
}
