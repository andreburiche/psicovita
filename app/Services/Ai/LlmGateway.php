<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Chat multi-provedor: openai | ollama | claude | gemini | mock.
 * Com failover opcional entre provedores com chave configurada.
 */
class LlmGateway
{
    public const PROVIDERS = ['openai', 'ollama', 'claude', 'gemini', 'mock'];

    public function provider(): string
    {
        return $this->normalizeProvider((string) Config::get('psiconecta.ai.provider', 'openai'));
    }

    public function chatReady(): bool
    {
        if (! (bool) Config::get('psiconecta.ai.enabled', true)) {
            return false;
        }

        foreach ($this->providerChain() as $provider) {
            if ($this->providerReady($provider)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    public function chat(
        string $system,
        string $user,
        int $maxTokens = 3500,
        float $temperature = 0.35,
    ): array {
        $chain = $this->providerChain();
        if ($chain === []) {
            throw new RuntimeException('LLM não configurado.');
        }

        $last = null;

        foreach ($chain as $index => $provider) {
            try {
                $result = $this->chatUsing($provider, $system, $user, $maxTokens, $temperature);

                if ($index > 0) {
                    Log::info('IA: failover usou provedor secundário.', [
                        'provider' => $provider,
                        'primary' => $chain[0],
                    ]);
                }

                return $result;
            } catch (Throwable $e) {
                $last = $e;
                $hasNext = isset($chain[$index + 1]);

                if (! $hasNext || ! $this->shouldFailover($e)) {
                    throw $e;
                }

                Log::warning('IA: falha no provedor; tentando failover.', [
                    'provider' => $provider,
                    'next' => $chain[$index + 1],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($last instanceof Throwable) {
            throw new RuntimeException(
                'Todos os provedores de IA da cadeia falharam. Último erro: '.$last->getMessage(),
                0,
                $last,
            );
        }

        throw new RuntimeException('LLM não configurado.');
    }

    public function chatModel(?string $provider = null): string
    {
        $provider = $this->normalizeProvider($provider ?? $this->provider());

        return match ($provider) {
            'claude' => (string) Config::get('psiconecta.ai.claude_chat_model', 'claude-sonnet-4-20250514'),
            'gemini' => (string) Config::get('psiconecta.ai.gemini_chat_model', 'gemini-2.0-flash'),
            default => (string) Config::get('psiconecta.ai.openai_chat_model', 'gpt-4o-mini'),
        };
    }

    /**
     * Cadeia: provedor principal + fallbacks configurados (só os ready, sem mock).
     *
     * @return list<string>
     */
    public function providerChain(): array
    {
        $primary = $this->provider();
        $chain = [];

        if ($primary !== 'mock' && $this->providerReady($primary)) {
            $chain[] = $primary;
        }

        if (! (bool) Config::get('psiconecta.ai.failover_enabled', true)) {
            return $chain;
        }

        foreach ($this->configuredFailoverProviders() as $candidate) {
            if ($candidate === 'mock' || in_array($candidate, $chain, true)) {
                continue;
            }
            if ($this->providerReady($candidate)) {
                $chain[] = $candidate;
            }
        }

        return $chain;
    }

    public function providerReady(string $provider): bool
    {
        $provider = $this->normalizeProvider($provider);

        return match ($provider) {
            'mock' => false,
            'ollama' => true,
            'openai' => filled($this->openaiKey()),
            'claude' => filled($this->claudeKey()),
            'gemini' => filled($this->geminiKey()),
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    private function configuredFailoverProviders(): array
    {
        $raw = Config::get('psiconecta.ai.failover_providers', ['gemini', 'claude', 'ollama']);

        if (is_string($raw)) {
            $parts = preg_split('/[\s,|]+/', $raw) ?: [];
        } elseif (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = ['gemini', 'claude', 'ollama'];
        }

        $out = [];
        foreach ($parts as $part) {
            if (! is_string($part) && ! is_numeric($part)) {
                continue;
            }
            $normalized = $this->normalizeProvider((string) $part);
            if (in_array($normalized, self::PROVIDERS, true) && $normalized !== 'mock') {
                $out[] = $normalized;
            }
        }

        return array_values(array_unique($out));
    }

    private function normalizeProvider(string $raw): string
    {
        $raw = strtolower(trim($raw));

        return match ($raw) {
            'chatgpt', 'gpt', 'openai' => 'openai',
            'anthropic', 'claude' => 'claude',
            'google', 'gemini' => 'gemini',
            'ollama' => 'ollama',
            'mock' => 'mock',
            default => in_array($raw, self::PROVIDERS, true) ? $raw : 'openai',
        };
    }

    private function shouldFailover(Throwable $e): bool
    {
        $m = $e->getMessage();

        $needles = [
            'exceeded your current quota',
            'insufficient_quota',
            'Billing hard limit',
            'rate_limit',
            'Rate limit',
            '429',
            '503',
            '502',
            '500',
            'overloaded',
            'temporarily unavailable',
            'service unavailable',
            'capacity',
        ];

        foreach ($needles as $needle) {
            if (stripos($m, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function chatUsing(
        string $provider,
        string $system,
        string $user,
        int $maxTokens,
        float $temperature,
    ): array {
        return match ($provider) {
            'claude' => $this->chatClaude($system, $user, $maxTokens, $temperature),
            'gemini' => $this->chatGemini($system, $user, $maxTokens, $temperature),
            'openai', 'ollama' => $this->chatOpenAiCompatible($provider, $system, $user, $maxTokens, $temperature),
            default => throw new RuntimeException('LLM não configurado.'),
        };
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function chatOpenAiCompatible(
        string $provider,
        string $system,
        string $user,
        int $maxTokens,
        float $temperature,
    ): array {
        $base = $this->openAiCompatibleBaseUrl($provider);
        $timeout = $this->timeout();
        $token = $this->resolveOpenAiCompatibleBearer($provider);

        $client = Http::timeout($timeout)
            ->connectTimeout(25)
            ->acceptJson();

        if ($token !== null && $token !== '') {
            $client = $client->withToken($token);
        }

        $response = $client->post($base.'/chat/completions', [
            'model' => $this->chatModel($provider),
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ]);

        $this->throwUnlessOk($response, 'chat');

        $content = $response->json('choices.0.message.content');
        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Resposta vazia do modelo de chat.');
        }

        $tokens = $response->json('usage.total_tokens');

        return [
            'text' => trim($content),
            'tokens_used' => is_numeric($tokens) ? (int) $tokens : null,
        ];
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function chatClaude(
        string $system,
        string $user,
        int $maxTokens,
        float $temperature,
    ): array {
        $key = $this->claudeKey();
        if ($key === '') {
            throw new RuntimeException('Chave Claude/Anthropic não configurada.');
        }

        $base = rtrim((string) Config::get('psiconecta.ai.claude_base_url', 'https://api.anthropic.com'), '/');
        $version = (string) Config::get('psiconecta.ai.claude_api_version', '2023-06-01');

        $response = Http::timeout($this->timeout())
            ->connectTimeout(25)
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => $version,
            ])
            ->post($base.'/v1/messages', [
                'model' => $this->chatModel('claude'),
                'max_tokens' => $maxTokens,
                'temperature' => $temperature,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        $this->throwUnlessOk($response, 'chat-claude');

        $blocks = $response->json('content');
        $text = '';
        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if (is_array($block) && ($block['type'] ?? '') === 'text') {
                    $text .= (string) ($block['text'] ?? '');
                }
            }
        }

        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('Resposta vazia do Claude.');
        }

        $input = $response->json('usage.input_tokens');
        $output = $response->json('usage.output_tokens');
        $tokens = (is_numeric($input) ? (int) $input : 0) + (is_numeric($output) ? (int) $output : 0);

        return [
            'text' => $text,
            'tokens_used' => $tokens > 0 ? $tokens : null,
        ];
    }

    /**
     * @return array{text: string, tokens_used: ?int}
     */
    private function chatGemini(
        string $system,
        string $user,
        int $maxTokens,
        float $temperature,
    ): array {
        $key = $this->geminiKey();
        if ($key === '') {
            throw new RuntimeException('Chave Gemini não configurada.');
        }

        $base = rtrim((string) Config::get('psiconecta.ai.gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = rawurlencode($this->chatModel('gemini'));
        $url = $base.'/models/'.$model.':generateContent?key='.urlencode($key);

        $response = Http::timeout($this->timeout())
            ->connectTimeout(25)
            ->acceptJson()
            ->post($url, [
                'systemInstruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $user]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => $temperature,
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);

        $this->throwUnlessOk($response, 'chat-gemini');

        $text = (string) ($response->json('candidates.0.content.parts.0.text') ?? '');
        $text = trim($text);
        if ($text === '') {
            $blockReason = $response->json('promptFeedback.blockReason')
                ?? $response->json('candidates.0.finishReason');
            throw new RuntimeException(
                'Resposta vazia do Gemini'.($blockReason ? ' ('.$blockReason.')' : '').'.'
            );
        }

        $promptTokens = $response->json('usageMetadata.promptTokenCount');
        $candidateTokens = $response->json('usageMetadata.candidatesTokenCount');
        $tokens = (is_numeric($promptTokens) ? (int) $promptTokens : 0)
            + (is_numeric($candidateTokens) ? (int) $candidateTokens : 0);

        return [
            'text' => $text,
            'tokens_used' => $tokens > 0 ? $tokens : null,
        ];
    }

    private function openAiCompatibleBaseUrl(string $provider): string
    {
        $raw = (string) Config::get('psiconecta.ai.openai_base_url', 'https://api.openai.com/v1');
        $base = rtrim($raw, '/');

        if ($provider === 'ollama' && str_contains($base, 'api.openai.com')) {
            return 'http://127.0.0.1:11434/v1';
        }

        return $base !== '' ? $base : 'https://api.openai.com/v1';
    }

    private function resolveOpenAiCompatibleBearer(string $provider): ?string
    {
        if ($provider === 'ollama') {
            $key = $this->openaiKey();

            return $key !== '' ? $key : 'ollama';
        }

        $key = $this->openaiKey();
        if ($key === '') {
            throw new RuntimeException('Chave OpenAI não configurada.');
        }

        return $key;
    }

    private function openaiKey(): string
    {
        $raw = Config::get('psiconecta.ai.openai_api_key');

        return is_string($raw) ? trim($raw) : '';
    }

    private function claudeKey(): string
    {
        $raw = Config::get('psiconecta.ai.claude_api_key');

        return is_string($raw) ? trim($raw) : '';
    }

    private function geminiKey(): string
    {
        $raw = Config::get('psiconecta.ai.gemini_api_key');

        return is_string($raw) ? trim($raw) : '';
    }

    private function timeout(): int
    {
        return (int) Config::get('psiconecta.ai.openai_timeout', 120);
    }

    private function throwUnlessOk(Response $response, string $contexto): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('error.message')
            ?? $response->json('error.status')
            ?? $response->json('message');
        $message = is_string($message) && $message !== ''
            ? $message
            : 'Erro HTTP '.$response->status();

        throw new RuntimeException('LLM ('.$contexto.'): '.$message);
    }
}
