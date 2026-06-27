<?php

namespace App\Models;

use App\Enums\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    /**
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $query = static::query()->where($field, $value);

        $user = auth()->user();
        if ($user?->isProfessional()) {
            $query->whereHas('patient', fn ($q) => $q->where('professional_id', $user->clinicalPracticeId()));
        } elseif ($user?->usesPatientPortalExperience()) {
            $ficha = app(\App\Services\PaymentService::class)->resolvePatientFichaForUser($user);
            if ($ficha === null) {
                abort(404);
            }
            $query->where('patient_id', $ficha->id);
        }

        return $query->firstOrFail();
    }

    protected $fillable = [
        'patient_id',
        'therapy_session_id',
        'amount',
        'status',
        'paid_at',
        'gateway',
        'external_id',
        'platform_fee',
        'professional_amount',
        'refunded_at',
        'gateway_meta',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'professional_amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'gateway' => PaymentGateway::class,
            'payment_method' => PaymentMethod::class,
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'gateway_meta' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function therapySession(): BelongsTo
    {
        return $this->belongsTo(TherapySession::class);
    }

    public function gatewayTransactions(): HasMany
    {
        return $this->hasMany(PaymentGatewayTransaction::class);
    }
}
