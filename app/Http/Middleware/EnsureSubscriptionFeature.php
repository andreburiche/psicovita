<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionFeature
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if ($this->subscriptions->canUseFeature($user, $feature)) {
            return $next($request);
        }

        $message = __('A sua assinatura expirou ou não inclui esta funcionalidade. Consulte o seu plano em Configurações.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()
            ->back()
            ->withInput($request->except('password', 'password_confirmation', '_token'))
            ->with('subscription_blocked', $message);
    }
}
