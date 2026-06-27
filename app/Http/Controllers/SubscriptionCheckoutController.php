<?php

namespace App\Http\Controllers;

use App\Enums\BillingCycle;
use App\Enums\PaymentMethod;
use App\Models\SubscriptionPlan;
use App\Services\ClinicTeamService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionCheckoutController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly ClinicTeamService $clinicTeams,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isProfessional()) {
            abort(403);
        }

        if ($user->isClinicTeamMember()) {
            return redirect()
                ->route('dashboard')
                ->with('status', __('A assinatura é gerida pelo titular da clínica.'));
        }

        $subscription = $this->subscriptions->activeSubscription($user);
        if ($subscription !== null) {
            $subscription = $this->subscriptions->syncCheckoutForDisplay($subscription);
        }

        return view('subscription.checkout', [
            'plans' => $this->subscriptions->purchasablePlans(),
            'subscription' => $subscription,
            'isActive' => $this->subscriptions->isActive($user),
            'showPixCheckout' => $subscription !== null && $this->subscriptions->shouldShowPixCheckout($subscription),
            'canCancel' => $subscription !== null && $this->subscriptions->isCancellable($subscription),
            'patientQuota' => $this->subscriptions->patientQuotaContext($user),
            'maxAnnualSavingsPercent' => (int) $this->subscriptions->purchasablePlans()->max(
                fn (SubscriptionPlan $plan) => $plan->annualSavingsPercent()
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isProfessional()) {
            abort(403);
        }

        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail((int) $validated['subscription_plan_id']);
        $method = PaymentMethod::from($validated['payment_method']);
        $billingCycle = BillingCycle::from($validated['billing_cycle']);

        try {
            $subscription = $this->subscriptions->initiateCheckout($user, $plan, $method, $billingCycle);
            $subscription = $this->subscriptions->syncCheckoutForDisplay($subscription);
            $this->clinicTeams->releaseTeamIfUnavailable($user);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('subscription.checkout')
                ->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()
            ->route('subscription.checkout')
            ->withFragment('pix-checkout')
            ->with('status', $this->subscriptions->checkoutSuccessMessage($subscription));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isProfessional()) {
            abort(403);
        }

        try {
            $subscription = $this->subscriptions->cancelGatewaySubscription($user);
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('subscription.checkout')
                ->withErrors(['checkout' => $e->getMessage()]);
        }

        $message = $subscription->ends_at
            ? __('Renovação cancelada. O acesso mantém-se até :date.', ['date' => $subscription->ends_at->format('d/m/Y')])
            : __('Renovação cancelada no gateway.');

        return redirect()
            ->route('subscription.checkout')
            ->with('status', $message);
    }
}
