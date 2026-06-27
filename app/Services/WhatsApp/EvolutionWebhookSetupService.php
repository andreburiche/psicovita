<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookSetupService
{
    /**
     * @return array{ok: bool, message: string, url?: string, details?: array<string, mixed>}
     */
    public function sync(): array
    {
        if ((string) config('psiconecta.whatsapp.driver', 'meta') !== 'evolution') {
            return ['ok' => false, 'message' => __('Driver não é Evolution.')];
        }

        if (! config('psiconecta.whatsapp.enabled', false)) {
            return ['ok' => false, 'message' => __('WhatsApp desativado.')];
        }

        $instance = (string) config('psiconecta.whatsapp.evolution.instance');
        $baseUrl = rtrim((string) config('psiconecta.whatsapp.evolution.api_url'), '/');
        $webhookUrl = $this->resolveDeliveryUrl();

        $payload = [
            'webhook' => [
                'enabled' => true,
                'url' => $webhookUrl,
                'webhookByEvents' => false,
                'webhookBase64' => false,
                'events' => ['MESSAGES_UPSERT'],
            ],
        ];

        $token = config('psiconecta.whatsapp.evolution.webhook_token');
        if (filled($token)) {
            $payload['webhook']['headers'] = [
                'X-Webhook-Token' => (string) $token,
            ];
        }

        try {
            $response = Http::withHeaders([
                'apikey' => (string) config('psiconecta.whatsapp.evolution.api_key'),
                'Content-Type' => 'application/json',
            ])->timeout(20)->post("{$baseUrl}/webhook/set/{$instance}", $payload);

            if (! $response->successful()) {
                Log::warning('Evolution webhook sync failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'url' => $webhookUrl,
                ]);

                return [
                    'ok' => false,
                    'message' => __('Falha ao configurar webhook na Evolution (HTTP :status).', [
                        'status' => $response->status(),
                    ]),
                    'url' => $webhookUrl,
                    'details' => ['body' => $response->json()],
                ];
            }

            Log::info('Evolution webhook synced', ['url' => $webhookUrl, 'instance' => $instance]);

            return [
                'ok' => true,
                'message' => __('Webhook configurado na Evolution.'),
                'url' => $webhookUrl,
                'details' => is_array($response->json()) ? $response->json() : [],
            ];
        } catch (\Throwable $e) {
            Log::error('Evolution webhook sync exception', ['message' => $e->getMessage()]);

            return [
                'ok' => false,
                'message' => __('Erro ao configurar webhook: :error', ['error' => $e->getMessage()]),
                'url' => $webhookUrl,
            ];
        }
    }

    public function resolveDeliveryUrl(): string
    {
        $configured = config('psiconecta.whatsapp.evolution.webhook_url');
        if (is_string($configured) && trim($configured) !== '') {
            return rtrim(trim($configured), '/');
        }

        $path = '/api/v1/integrations/evolution/webhook';
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($this->shouldUseDockerHostGateway($appUrl)) {
            $parsed = parse_url($appUrl);
            $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

            return 'http://host.docker.internal'.$port.$path;
        }

        return url($path);
    }

    private function shouldUseDockerHostGateway(string $appUrl): bool
    {
        $evolutionUrl = (string) config('psiconecta.whatsapp.evolution.api_url', '');

        if (! str_contains($evolutionUrl, '127.0.0.1') && ! str_contains($evolutionUrl, 'localhost')) {
            return false;
        }

        return str_contains($appUrl, '127.0.0.1')
            || str_contains($appUrl, 'localhost');
    }
}
