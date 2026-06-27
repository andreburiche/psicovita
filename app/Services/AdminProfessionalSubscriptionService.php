<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\ProfessionalSubscription;
use App\Models\SubscriptionPlan;
use App\Support\ContactHasher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AdminProfessionalSubscriptionService
{
    /**
     * @param  array{q?: string, status?: string, plan_id?: string}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = ProfessionalSubscription::query()
            ->with(['user', 'plan'])
            ->whereHas('user', function ($builder) {
                $builder
                    ->where('role', UserRole::Professional)
                    ->whereNull('clinic_owner_id');
            });

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->whereHas('user', function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%');

                if (str_contains($search, '@')) {
                    $builder->orWhere(
                        'email_hash',
                        ContactHasher::emailHash(Str::lower(trim($search))),
                    );
                }
            });
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $planId = trim((string) ($filters['plan_id'] ?? ''));
        if ($planId !== '') {
            $query->where('subscription_plan_id', (int) $planId);
        }

        return $query
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statusSummary(): array
    {
        $counts = ProfessionalSubscription::query()
            ->whereHas('user', function ($builder) {
                $builder
                    ->where('role', UserRole::Professional)
                    ->whereNull('clinic_owner_id');
            })
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $summary = [];
        foreach (SubscriptionStatus::cases() as $status) {
            $summary[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $summary;
    }

    /**
     * @return \Illuminate\Support\Collection<int, SubscriptionPlan>
     */
    public function planOptions()
    {
        return SubscriptionPlan::query()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);
    }
}
