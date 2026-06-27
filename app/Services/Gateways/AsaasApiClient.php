<?php

namespace App\Services\Gateways;

use App\Exceptions\AsaasApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AsaasApiClient
{
    public function isConfigured(): bool
    {
        return (bool) config('asaas.enabled')
            && filled(config('asaas.api_key'))
            && filled(config('asaas.base_url'));
    }

    /**
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        return $this->request('post', $path, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $path): array
    {
        return $this->request('get', $path);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->isConfigured()) {
            throw new AsaasApiException(__('Integração Asaas não configurada.'));
        }

        $response = $this->http()
            ->{$method}($this->normalizePath($path), $method === 'get' ? [] : $payload);

        if ($response->failed()) {
            $message = collect($response->json('errors', []))
                ->pluck('description')
                ->filter()
                ->implode(' ');

            throw new AsaasApiException($message !== ''
                ? $message
                : __('Falha na comunicação com o Asaas (:status).', ['status' => $response->status()]));
        }

        /** @var array<string, mixed> $data */
        $data = $response->json() ?? [];

        return $data;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('asaas.base_url'), '/'))
            ->acceptJson()
            ->withHeaders([
                'access_token' => (string) config('asaas.api_key'),
            ])
            ->timeout(20);
    }

    private function normalizePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : '/'.$path;
    }
}
