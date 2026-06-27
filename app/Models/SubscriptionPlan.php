<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\SubscriptionPlanSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'price_cents',
        'annual_price_cents',
        'trial_days',
        'max_patients',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'slug' => SubscriptionPlanSlug::class,
            'features' => 'array',
            'is_active' => 'boolean',
            'price_cents' => 'integer',
            'annual_price_cents' => 'integer',
            'trial_days' => 'integer',
            'max_patients' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProfessionalSubscription::class);
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? [], true);
    }

    public function formattedPrice(): string
    {
        return $this->formatCents($this->price_cents);
    }

    public function resolvedAnnualPriceCents(): int
    {
        if ($this->annual_price_cents > 0) {
            return $this->annual_price_cents;
        }

        if ($this->price_cents <= 0) {
            return 0;
        }

        $discount = max(0, min(100, (int) config('subscription.annual_discount_percent', 17)));

        return (int) round($this->price_cents * 12 * (1 - ($discount / 100)));
    }

    public function formattedAnnualPrice(): string
    {
        return $this->formatCents($this->resolvedAnnualPriceCents());
    }

    public function formattedAnnualMonthlyEquivalent(): string
    {
        $annual = $this->resolvedAnnualPriceCents();
        if ($annual <= 0) {
            return $this->formattedPrice();
        }

        return $this->formatCents((int) round($annual / 12));
    }

    public function annualSavingsPercent(): int
    {
        if ($this->price_cents <= 0) {
            return 0;
        }

        $fullYear = $this->price_cents * 12;
        $annual = $this->resolvedAnnualPriceCents();

        if ($fullYear <= 0 || $annual <= 0 || $annual >= $fullYear) {
            return 0;
        }

        return (int) round((1 - ($annual / $fullYear)) * 100);
    }

    public function chargeAmountCents(BillingCycle $cycle): int
    {
        return $cycle === BillingCycle::Yearly
            ? $this->resolvedAnnualPriceCents()
            : $this->price_cents;
    }

    private function formatCents(int $cents): string
    {
        if ($cents <= 0) {
            return __('Grátis');
        }

        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }
}
