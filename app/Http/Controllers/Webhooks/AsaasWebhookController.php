<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AsaasWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('asaas.webhook_token');
        $incoming = (string) $request->header('asaas-access-token', '');

        if ($token === '' || ! hash_equals($token, $incoming)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $payment = $this->payments->confirmFromWebhook($payload);
        $subscription = $this->subscriptions->confirmFromWebhook($payload);

        return response()->json([
            'received' => true,
            'payment_id' => $payment?->id,
            'subscription_id' => $subscription?->id,
        ]);
    }
}
