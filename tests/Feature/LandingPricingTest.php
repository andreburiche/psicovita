<?php

namespace Tests\Feature;

use App\Enums\SubscriptionPlanSlug;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_shows_pricing_plans(): void
    {
        $premium = SubscriptionPlan::query()->where('slug', SubscriptionPlanSlug::Premium)->firstOrFail();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(__('Planos transparentes'), false)
            ->assertSee($premium->name, false)
            ->assertSee('#precos', false)
            ->assertSee(__('Anual'), false)
            ->assertSee($premium->formattedAnnualPrice(), false);
    }
}
