<?php

namespace App\Models;

use App\Enums\SessionMode;
use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TherapySession extends Model
{
    /** @use HasFactory<\Database\Factories\TherapySessionFactory> */
    use HasFactory;

    public bool $skipAutoPayment = false;

    public bool $forceAutoPayment = false;

    public ?float $paymentAmountOverride = null;

    /**
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $query = static::query()->where($field, $value);

        $user = auth()->user();
        if ($user?->isProfessional()) {
            $query->where('professional_id', $user->id);
        }

        return $query->firstOrFail();
    }

    protected $fillable = [
        'patient_id',
        'professional_id',
        'session_date',
        'session_time',
        'status',
        'type',
        'session_mode',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'status' => TherapySessionStatus::class,
            'type' => TherapySessionType::class,
            'session_mode' => SessionMode::class,
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function videoCall(): HasOne
    {
        return $this->hasOne(TherapySessionVideoCall::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class);
    }

    public function displayLabel(): string
    {
        if ($this->patient?->name) {
            return $this->patient->name;
        }

        return match ($this->session_mode ?? SessionMode::Individual) {
            SessionMode::WithObserver => __('Escuta'),
            SessionMode::Family => __('Família'),
            SessionMode::Group => __('Grupo'),
            default => __('Sessão'),
        };
    }
}
