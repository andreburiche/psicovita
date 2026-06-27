<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $subscriptionBanner = $this->subscriptions->bannerContext($user);

        return view('dashboard', [
            'stats' => $this->dashboardService->summary($user),
            'todayAgenda' => $this->dashboardService->todayAgenda($user),
            'sessionTrend' => $this->dashboardService->sessionTrendLast14Days($user),
            'revenueTrend' => $this->dashboardService->paidRevenueLast7Days($user),
            'subscriptionBanner' => $subscriptionBanner,
            'patientQuota' => $subscriptionBanner['patient_quota'],
            'notifications' => $user->notifications()->latest()->limit(20)->get(),
        ]);
    }
}
