<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EvolutionWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppConversationService $whatsApp,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        if (! config('psiconecta.whatsapp.enabled', false)) {
            return response()->json(['message' => 'Integração desativada.'], 503);
        }

        if ((string) config('psiconecta.whatsapp.driver', 'meta') !== 'evolution') {
            return response()->json(['message' => 'Driver WhatsApp não é Evolution.'], 503);
        }

        $expectedToken = config('psiconecta.whatsapp.evolution.webhook_token');
        if (filled($expectedToken)) {
            $token = $request->header('X-Webhook-Token', $request->query('token'));
            if (! is_string($token) || ! hash_equals((string) $expectedToken, $token)) {
                return response()->json(['message' => 'Token inválido.'], 403);
            }
        }

        $ingested = $this->whatsApp->ingestWebhookPayload($request->all());

        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::info('Evolution webhook received', [
                'event' => $request->input('event'),
                'ingested' => $ingested,
                'instance' => $request->input('instance'),
            ]);
        }

        return response()->json([
            'received' => true,
            'ingested' => $ingested,
        ]);
    }
}
