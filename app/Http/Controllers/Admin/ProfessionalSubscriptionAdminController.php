<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingCycle;
use App\Http\Controllers\Controller;
use App\Models\ProfessionalSubscription;
use App\Models\SubscriptionPlan;
use App\Services\AdminProfessionalSubscriptionService;
use App\Services\SubscriptionService;
use App\Enums\SubscriptionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfessionalSubscriptionAdminController extends Controller
{
    public function __construct(
        private readonly AdminProfessionalSubscriptionService $registry,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $filters = $request->only(['q', 'status', 'plan_id']);

        return view('admin.subscriptions.index', [
            'subscriptions' => $this->registry->paginate($filters),
            'filters' => $filters,
            'statuses' => SubscriptionStatus::options(),
            'plans' => $this->registry->planOptions(),
            'summary' => $this->registry->statusSummary(),
            'manualActivationEnabled' => (bool) config('subscription.manual_activation_enabled', true),
        ]);
    }

    public function edit(Request $request, ProfessionalSubscription $subscription): View
    {
        $this->ensureAdmin($request);
        abort_unless((bool) config('subscription.manual_activation_enabled', true), 404);

        $subscription->loadMissing(['user', 'plan']);

        return view('admin.subscriptions.validate', [
            'subscription' => $subscription,
            'plans' => $this->subscriptions->purchasablePlans(),
            'billingCycles' => BillingCycle::cases(),
        ]);
    }

    public function update(Request $request, ProfessionalSubscription $subscription): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_unless((bool) config('subscription.manual_activation_enabled', true), 404);

        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail((int) $validated['subscription_plan_id']);
        $billingCycle = BillingCycle::from($validated['billing_cycle']);
        $validUntil = isset($validated['valid_until'])
            ? \Illuminate\Support\Carbon::parse($validated['valid_until'])->endOfDay()
            : null;

        try {
            $this->subscriptions->manualConfirmByAdmin(
                $request->user(),
                $subscription,
                $plan,
                $billingCycle,
                $validUntil,
                $validated['note'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['manual' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('status', __('Pagamento validado manualmente para :name.', [
                'name' => $subscription->user?->name ?? __('profissional'),
            ]));
    }

    public function grantComplimentary(Request $request, ProfessionalSubscription $subscription): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'subscription_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'valid_until' => ['nullable', 'date', 'after:today'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $plan = isset($validated['subscription_plan_id'])
            ? SubscriptionPlan::query()->findOrFail((int) $validated['subscription_plan_id'])
            : null;
        $validUntil = isset($validated['valid_until'])
            ? \Illuminate\Support\Carbon::parse($validated['valid_until'])->endOfDay()
            : null;

        try {
            $this->subscriptions->grantComplimentaryAccess(
                $request->user(),
                $subscription,
                $plan,
                $validUntil,
                $validated['note'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['complimentary' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('status', __('Acesso por cortesia activado para :name. O profissional pode usar a aplicação sem pagamento.', [
                'name' => $subscription->user?->name ?? __('profissional'),
            ]));
    }

    public function revokeComplimentary(Request $request, ProfessionalSubscription $subscription): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->subscriptions->revokeComplimentaryAccess(
                $request->user(),
                $subscription,
                $validated['note'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['complimentary' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('status', __('Acesso por cortesia desactivado para :name.', [
                'name' => $subscription->user?->name ?? __('profissional'),
            ]));
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
