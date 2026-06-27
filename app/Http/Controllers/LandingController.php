<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionPlanSlug;
use App\Models\LandingPartner;
use App\Models\SiteSetting;
use App\Models\SubscriptionPlan;
use Illuminate\View\View;

class LandingController
{
    public function __invoke(): View
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->where('slug', '!=', SubscriptionPlanSlug::Trial)
            ->where('price_cents', '>', 0)
            ->orderBy('sort_order')
            ->get();

        return view('landing', [
            'plans' => $plans,
            'partners' => LandingPartner::query()->activeOrdered()->get(),
            'siteContext' => SiteSetting::publicContext(),
            'trialDays' => (int) config('subscription.trial_days', 14),
            'maxAnnualSavingsPercent' => (int) $plans->max(fn (SubscriptionPlan $plan) => $plan->annualSavingsPercent()),
        ]);
    }
}
