<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'gateway_external_id',
        'gateway_meta',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'gateway_meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function lastRenewalAt(): ?\Illuminate\Support\Carbon
    {
        $raw = $this->gateway_meta['last_renewal_at'] ?? null;
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public function paymentMethodLabel(): ?string
    {
        $method = $this->gateway_meta['payment_method'] ?? null;
        if (! is_string($method) || $method === '') {
            return null;
        }

        try {
            return \App\Enums\PaymentMethod::from($method)->label();
        } catch (\ValueError) {
            return null;
        }
    }

    public function billingCycleLabel(): ?string
    {
        $cycle = $this->gateway_meta['billing_cycle'] ?? null;
        if (! is_string($cycle) || $cycle === '') {
            return null;
        }

        try {
            return \App\Enums\BillingCycle::from($cycle)->label();
        } catch (\ValueError) {
            return null;
        }
    }

    public function expirationDate(): ?\Illuminate\Support\Carbon
    {
        if ($this->status === SubscriptionStatus::Trialing) {
            return $this->trial_ends_at;
        }

        return $this->ends_at;
    }

    public function hasPaymentConfirmation(): bool
    {
        return filled($this->gateway_meta['payment_confirmed_at'] ?? null);
    }

    public function isManuallyValidated(): bool
    {
        return filled($this->gateway_meta['manual_validated_at'] ?? null);
    }

    public function isAwaitingAdminValidation(): bool
    {
        return $this->hasPaymentConfirmation() && ! $this->isManuallyValidated();
    }

    public function manualValidatorLabel(): ?string
    {
        $name = $this->gateway_meta['manual_validated_by_name'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }
}
