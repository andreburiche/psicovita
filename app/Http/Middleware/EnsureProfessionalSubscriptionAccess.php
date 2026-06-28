<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfessionalSubscriptionAccess
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        if ($this->subscriptions->hasClinicalAccess($user)) {
            return $next($request);
        }

        if ($this->isAllowedWithoutActiveSubscription($request)) {
            return $next($request);
        }

        $subscription = $this->subscriptions->activeSubscription($this->subscriptions->billingUser($user));
        $message = $subscription?->isAwaitingAdminValidation()
            ? __('Pagamento registado. Aguarde a validação do administrador para recuperar o acesso clínico.')
            : __('O período de teste expirou ou a assinatura está inactiva. Efectue o pagamento em Assinatura e aguarde a validação do administrador.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()
            ->route('subscription.checkout')
            ->with('subscription_blocked', $message);
    }

    private function isAllowedWithoutActiveSubscription(Request $request): bool
    {
        return $request->routeIs(
            'subscription.checkout',
            'subscription.checkout.store',
            'subscription.checkout.cancel',
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'profile.professional-files.store',
            'profile.professional-files.download',
            'profile.professional-files.destroy',
            'profile.asaas-wallet.provision',
        );
    }
}
