<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGatewayInterface;
use App\Models\MessageAttachment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EvolutionWhatsAppGateway implements WhatsAppGatewayInterface
{
    public function driver(): string
    {
        return 'evolution';
    }

    public function isConfigured(): bool
    {
        return (bool) config('psiconecta.whatsapp.enabled', false)
            && filled(config('psiconecta.whatsapp.evolution.api_url'))
            && filled(config('psiconecta.whatsapp.evolution.api_key'))
            && filled(config('psiconecta.whatsapp.evolution.instance'));
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'message' => __('Credenciais Evolution não configuradas no .env.'),
            ];
        }

        $baseUrl = rtrim((string) config('psiconecta.whatsapp.evolution.api_url'), '/');
        $misconfigured = $this->detectMisconfiguredUrl($baseUrl);
        if ($misconfigured !== null) {
            return [
                'ok' => false,
                'message' => $misconfigured,
            ];
        }

        $response = $this->get("/instance/connectionState/{$this->instance()}");

        if ($response === null) {
            return [
                'ok' => false,
                'message' => __('Não foi possível contactar a Evolution API em :url. Instale o Docker e execute: docker compose up -d evolution', [
                    'url' => $baseUrl,
                ]),
            ];
        }

        $state = (string) data_get($response, 'instance.state', data_get($response, 'state', ''));

        if ($state === 'open') {
            $webhook = app(EvolutionWebhookSetupService::class)->sync();

            return [
                'ok' => true,
                'message' => __('Instância conectada ao WhatsApp.'),
                'details' => [
                    'instance' => (string) data_get($response, 'instance.instanceName', $this->instance()),
                    'state' => $state,
                    'webhook' => $webhook,
                ],
            ];
        }

        return [
            'ok' => false,
            'message' => __('Instância não conectada. Estado: :state', ['state' => $state ?: __('desconhecido')]),
            'details' => [
                'instance' => $this->instance(),
                'state' => $state,
            ],
        ];
    }

    public function sendText(string $phone, string $body): ?string
    {
        $candidates = $this->phoneCandidates($phone);

        foreach ($candidates as $candidate) {
            $response = $this->request('post', "/message/sendText/{$this->instance()}", [
                'number' => $candidate,
                'text' => $body,
            ]);

            $messageId = $this->extractMessageId($response);
            if ($messageId !== null) {
                return $messageId;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function phoneCandidates(string $phone): array
    {
        $normalized = WhatsAppIncomingHandler::normalizePhone($phone);
        if ($normalized === '') {
            return [];
        }

        $candidates = [$normalized];

        if (! str_contains($normalized, '@')) {
            $candidates[] = $normalized.'@s.whatsapp.net';
        }

        return array_values(array_unique($candidates));
    }

    public function sendDocument(string $phone, MessageAttachment $attachment, ?string $caption = null): ?string
    {
        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            return null;
        }

        $contents = Storage::disk($attachment->disk)->get($attachment->path);
        $base64 = base64_encode($contents);
        $mediatype = str_starts_with($attachment->mime_type, 'image/') ? 'image' : 'document';

        $response = $this->request('post', "/message/sendMedia/{$this->instance()}", array_filter([
            'number' => $phone,
            'mediatype' => $mediatype,
            'mimetype' => $attachment->mime_type,
            'media' => $base64,
            'fileName' => $attachment->original_name,
            'caption' => $caption,
        ]));

        return $this->extractMessageId($response);
    }

    public function sendSessionTemplate(string $phone, string $patientName): ?string
    {
        $text = __('Olá :name, o seu profissional entrou em contacto consigo via :app.', [
            'name' => $patientName,
            'app' => config('app.name'),
        ]);

        return $this->sendText($phone, $text);
    }

    public function ingestWebhookPayload(array $payload): int
    {
        $event = strtolower((string) ($payload['event'] ?? ''));
        $event = str_replace('_', '.', $event);

        if (! in_array($event, ['messages.upsert'], true)) {
            return 0;
        }

        $data = $payload['data'] ?? $payload;
        $messages = is_array($data) && array_is_list($data) ? $data : [$data];

        $handler = app(WhatsAppIncomingHandler::class);
        $ingested = 0;

        foreach ($messages as $item) {
            if (! is_array($item) || ! $this->ingestEvolutionMessage($handler, $item)) {
                continue;
            }

            $ingested++;
        }

        return $ingested;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function ingestEvolutionMessage(WhatsAppIncomingHandler $handler, array $item): bool
    {
        $key = $item['key'] ?? [];
        if (! is_array($key)) {
            return false;
        }

        if (($key['fromMe'] ?? false) === true) {
            return false;
        }

        $remoteJid = (string) ($key['remoteJid'] ?? '');
        if (str_contains($remoteJid, '@lid') && filled($key['remoteJidAlt'] ?? null)) {
            $remoteJid = (string) $key['remoteJidAlt'];
        }
        if ($remoteJid === '') {
            $remoteJid = (string) ($key['remoteJidAlt'] ?? '');
        }
        $externalId = (string) ($key['id'] ?? '');

        if ($remoteJid === '' || $externalId === '') {
            return false;
        }

        $from = WhatsAppIncomingHandler::normalizePhone(strtok($remoteJid, '@') ?: $remoteJid);
        $messagePayload = is_array($item['message'] ?? null) ? $item['message'] : [];
        $attachment = $this->downloadMediaAttachment($key, $messagePayload);
        $body = $this->extractMessageBody($messagePayload);

        if ($body === '' && $attachment === null) {
            return false;
        }

        return $handler->store(
            $from,
            $body !== '' ? $body : __('📎 Anexo'),
            'evolution:'.$externalId,
            'evolution',
            $attachment,
        );
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $message
     * @return array{contents: string, mime_type: string, original_name: string}|null
     */
    private function downloadMediaAttachment(array $key, array $message): ?array
    {
        if (! $this->messageHasMedia($message)) {
            return null;
        }

        $response = $this->request('post', "/chat/getBase64FromMediaMessage/{$this->instance()}", [
            'message' => ['key' => $key],
            'convertToMp4' => (bool) data_get($message, 'audioMessage'),
        ]);

        $base64 = data_get($response, 'base64');
        if (! is_string($base64) || $base64 === '') {
            Log::warning('Evolution media download returned empty base64', [
                'message_id' => $key['id'] ?? null,
            ]);

            return null;
        }

        $contents = base64_decode($base64, true);
        if ($contents === false || $contents === '') {
            return null;
        }

        $mime = (string) (
            data_get($response, 'mimetype')
            ?? data_get($message, 'imageMessage.mimetype')
            ?? data_get($message, 'documentMessage.mimetype')
            ?? data_get($message, 'videoMessage.mimetype')
            ?? data_get($message, 'audioMessage.mimetype')
            ?? 'application/octet-stream'
        );

        $fileName = (string) (
            data_get($response, 'fileName')
            ?? data_get($message, 'documentMessage.fileName')
            ?? data_get($message, 'imageMessage.fileName')
            ?? 'whatsapp-media'
        );

        return [
            'contents' => $contents,
            'mime_type' => $mime,
            'original_name' => $fileName,
        ];
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function messageHasMedia(array $message): bool
    {
        return data_get($message, 'imageMessage') !== null
            || data_get($message, 'documentMessage') !== null
            || data_get($message, 'videoMessage') !== null
            || data_get($message, 'audioMessage') !== null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractMessageBody(array $message): string
    {
        if ($message === []) {
            return '';
        }

        if (isset($message['conversation'])) {
            return (string) $message['conversation'];
        }

        if ($text = data_get($message, 'extendedTextMessage.text')) {
            return (string) $text;
        }

        if ($caption = data_get($message, 'imageMessage.caption')) {
            return (string) $caption;
        }

        if (data_get($message, 'imageMessage')) {
            return __('📷 Imagem recebida via WhatsApp');
        }

        if ($caption = data_get($message, 'documentMessage.caption')) {
            return (string) $caption;
        }

        if ($fileName = data_get($message, 'documentMessage.fileName')) {
            return __('📎 Documento: :name', ['name' => $fileName]);
        }

        if ($caption = data_get($message, 'videoMessage.caption')) {
            return (string) $caption;
        }

        if (data_get($message, 'videoMessage')) {
            return __('🎬 Vídeo recebido via WhatsApp');
        }

        if (data_get($message, 'audioMessage')) {
            return __('🎤 Áudio recebido via WhatsApp');
        }

        return '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get(string $path): ?array
    {
        return $this->httpRequest('get', $path);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function request(string $method, string $path, array $payload): ?array
    {
        return $this->httpRequest($method, $path, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function httpRequest(string $method, string $path, array $payload = []): ?array
    {
        $baseUrl = rtrim((string) config('psiconecta.whatsapp.evolution.api_url'), '/');

        try {
            $client = Http::withHeaders([
                'apikey' => (string) config('psiconecta.whatsapp.evolution.api_key'),
                'Content-Type' => 'application/json',
            ])->timeout(30);

            $response = strtolower($method) === 'get'
                ? $client->get("{$baseUrl}{$path}")
                : $client->{$method}("{$baseUrl}{$path}", $payload);

            if (! $response->successful()) {
                Log::warning('Evolution API request failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            $json = $response->json();

            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            Log::error('Evolution API exception', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    private function extractMessageId(?array $response): ?string
    {
        if ($response === null) {
            return null;
        }

        $id = data_get($response, 'key.id')
            ?? data_get($response, 'message.key.id')
            ?? data_get($response, 'data.key.id')
            ?? data_get($response, 'id');

        if ($id) {
            return 'evolution:'.(string) $id;
        }

        if (data_get($response, 'status') === 'success' || data_get($response, 'message') !== null) {
            return 'evolution:sent:'.md5(json_encode($response));
        }

        return null;
    }

    private function instance(): string
    {
        return (string) config('psiconecta.whatsapp.evolution.instance');
    }

    private function detectMisconfiguredUrl(string $baseUrl): ?string
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === $appUrl) {
            return __('EVOLUTION_API_URL aponta para o PsiConecta (:url). Use outra porta (ex.: http://127.0.0.1:8082).', ['url' => $baseUrl]);
        }

        try {
            $probe = Http::timeout(5)->get($baseUrl);
            $body = (string) $probe->body();

            if (str_contains($body, 'PsiConecta API') || str_contains($body, '"openapi"')) {
                return __('EVOLUTION_API_URL (:url) responde como PsiConecta, não como Evolution API.', ['url' => $baseUrl]);
            }

            if (str_contains($body, 'expo-reset') || str_contains($body, 'Unmatched Route')) {
                return __('EVOLUTION_API_URL (:url) é outra aplicação (porta ocupada). Use a porta 8082 da Evolution.', ['url' => $baseUrl]);
            }
        } catch (\Throwable) {
            // Evolution pode estar offline; connectionState abaixo tratará o erro.
        }

        return null;
    }
}
