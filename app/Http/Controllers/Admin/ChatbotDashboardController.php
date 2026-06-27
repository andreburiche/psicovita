<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Chatbot\ChatbotMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatbotDashboardController extends Controller
{
    public function __construct(
        private readonly ChatbotMetricsService $metrics,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        abort_unless(config('psiconecta.chatbot.enabled', true), 404);

        $days = max(7, min(90, $request->integer('days') ?: 30));
        $snapshot = $this->metrics->snapshot($days);

        return view('admin.chatbot.dashboard', compact('snapshot', 'days'));
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
