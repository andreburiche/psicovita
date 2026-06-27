<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGatewayInterface;
use App\Models\MessageAttachment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MetaWhatsAppGateway implements WhatsAppGatewayInterface
{
    public function driver(): string
    {
        return 'meta';
    }

    public function isConfigured(): bool
    {
        return (bool) config('psiconecta.whatsapp.enabled', false)
            && filled(config('psiconecta.whatsapp.access_token'))
            && filled(config('psiconecta.whatsapp.phone_number_id'));
    }

    public function sendText(string $phone, string $body): ?string
    {
        return $this->postMessage($phone, [
            'type' => 'text',
            'text' => ['body' => $body],
        ]);
    }

    public function sendDocument(string $phone, MessageAttachment $attachment, ?string $caption = null): ?string
    {
        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            return null;
        }

        $mediaId = $this->uploadMedia($attachment);
        if ($mediaId === null) {
            return null;
        }

        $waType = str_starts_with($attachment->mime_type, 'image/') ? 'image' : 'document';

        return $this->postMessage($phone, [
            'type' => $waType,
            $waType => array_filter([
                'id' => $mediaId,
                'caption' => $caption,
                'filename' => $waType === 'document' ? $attachment->original_name : null,
            ]),
        ]);
    }

    public function sendSessionTemplate(string $phone, string $patientName): ?string
    {
        $template = config('psiconecta.whatsapp.session_template');
        if (! $template) {
            return null;
        }

        return $this->postMessage($phone, [
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => (string) config('psiconecta.whatsapp.session_template_language', 'pt_BR')],
                'components' => [[
                    'type' => 'body',
                    'parameters' => [[
                        'type' => 'text',
                        'text' => $patientName,
                    ]],
                ]],
            ],
        ]);
    }

    public function ingestWebhookPayload(array $payload): int
    {
        $ingested = 0;
        $handler = app(WhatsAppIncomingHandler::class);

        foreach ($payload['entry'] ?? [] as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            foreach ($entry['changes'] ?? [] as $change) {
                if (! is_array($change)) {
                    continue;
                }

                $value = $change['value'] ?? [];
                if (! is_array($value)) {
                    continue;
                }

                foreach ($value['messages'] ?? [] as $incoming) {
                    if (! is_array($incoming) || ! $this->ingestMetaMessage($handler, $incoming)) {
                        continue;
                    }

                    $ingested++;
                }
            }
        }

        return $ingested;
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => __('Credenciais Meta não configuradas no .env.'),
            ];
        }

        $phoneNumberId = (string) config('psiconecta.whatsapp.phone_number_id');
        $token = (string) config('psiconecta.whatsapp.access_token');
        $baseUrl = rtrim((string) config('psiconecta.whatsapp.api_url', 'https://graph.facebook.com/v21.0'), '/');

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->get("{$baseUrl}/{$phoneNumberId}");

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => __('Falha ao contactar a API Meta.'),
                    'details' => ['status' => $response->status()],
                ];
            }

            return [
                'ok' => true,
                'message' => __('Conexão com WhatsApp Cloud API OK.'),
                'details' => [
                    'phone' => data_get($response->json(), 'display_phone_number'),
                    'verified_name' => data_get($response->json(), 'verified_name'),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => __('Erro ao testar conexão Meta: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $incoming
     */
    private function ingestMetaMessage(WhatsAppIncomingHandler $handler, array $incoming): bool
    {
        $type = (string) ($incoming['type'] ?? '');
        $from = (string) ($incoming['from'] ?? '');
        $externalId = (string) ($incoming['id'] ?? '');

        $body = match ($type) {
            'text' => (string) data_get($incoming, 'text.body', ''),
            'image' => (string) (data_get($incoming, 'image.caption') ?: __('📷 Imagem recebida via WhatsApp')),
            'document' => (string) (data_get($incoming, 'document.caption')
                ?: __('📎 Documento: :name', ['name' => data_get($incoming, 'document.filename', 'arquivo')])),
            default => '',
        };

        return $handler->store($from, $body, $externalId, $type);
    }

    private function uploadMedia(MessageAttachment $attachment): ?string
    {
        $phoneNumberId = (string) config('psiconecta.whatsapp.phone_number_id');
        $token = (string) config('psiconecta.whatsapp.access_token');
        $baseUrl = rtrim((string) config('psiconecta.whatsapp.api_url', 'https://graph.facebook.com/v21.0'), '/');
        $path = Storage::disk($attachment->disk)->path($attachment->path);
        $waType = str_starts_with($attachment->mime_type, 'image/') ? 'image' : 'document';

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->attach('file', fopen($path, 'r'), $attachment->original_name)
                ->post("{$baseUrl}/{$phoneNumberId}/media", [
                    'messaging_product' => 'whatsapp',
                    'type' => $waType,
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp Meta media upload failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return data_get($response->json(), 'id');
        } catch (\Throwable $e) {
            Log::error('WhatsApp Meta media upload exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $messagePayload
     */
    private function postMessage(string $phone, array $messagePayload): ?string
    {
        $phoneNumberId = (string) config('psiconecta.whatsapp.phone_number_id');
        $token = (string) config('psiconecta.whatsapp.access_token');
        $baseUrl = rtrim((string) config('psiconecta.whatsapp.api_url', 'https://graph.facebook.com/v21.0'), '/');

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->post("{$baseUrl}/{$phoneNumberId}/messages", array_merge([
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                ], $messagePayload));

            if (! $response->successful()) {
                Log::warning('WhatsApp Meta send failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            return data_get($response->json(), 'messages.0.id');
        } catch (\Throwable $e) {
            Log::error('WhatsApp Meta send exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
