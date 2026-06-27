<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionPlanSlug;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPlanAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $plans = SubscriptionPlan::query()
            ->where('slug', '!=', SubscriptionPlanSlug::Trial)
            ->orderBy('sort_order')
            ->get();

        return view('admin.site.plans', compact('plans'));
    }

    public function update(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $this->ensureAdmin($request);

        abort_if($plan->slug === SubscriptionPlanSlug::Trial, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_annual' => ['required', 'numeric', 'min:0'],
            'max_patients' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $plan->update([
            'name' => $validated['name'],
            'price_cents' => (int) round((float) $validated['price_monthly'] * 100),
            'annual_price_cents' => (int) round((float) $validated['price_annual'] * 100),
            'max_patients' => $validated['max_patients'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $validated['sort_order'],
        ]);

        return redirect()
            ->route('admin.site.plans')
            ->with('status', __('Plano :name atualizado.', ['name' => $plan->name]));
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
