<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppConversationService $whatsApp,
    ) {}

    /**
     * Verificação GET (Meta / WhatsApp Cloud API).
     */
    public function verify(Request $request): Response|JsonResponse
    {
        $expected = config('psiconecta.whatsapp.webhook_verify_token');
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if ($mode !== 'subscribe' || ! is_string($token) || ! is_string($challenge)) {
            return response()->json(['message' => 'Parâmetros inválidos.'], 400);
        }

        if (empty($expected) || ! hash_equals((string) $expected, $token)) {
            return response()->json(['message' => 'Token de verificação incorreto.'], 403);
        }

        return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Eventos recebidos (mensagens sincronizadas com conversas).
     */
    public function handle(Request $request): JsonResponse
    {
        if (! config('psiconecta.whatsapp.enabled', false)) {
            return response()->json(['message' => 'Integração desativada.'], 503);
        }

        if ((string) config('psiconecta.whatsapp.driver', 'meta') !== 'meta') {
            return response()->json(['message' => 'Driver WhatsApp não é Meta Cloud API.'], 503);
        }

        $ingested = $this->whatsApp->ingestWebhookPayload($request->all());

        return response()->json([
            'received' => true,
            'ingested' => $ingested,
        ]);
    }
}
