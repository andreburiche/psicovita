<?php

namespace Tests\Unit;

use App\Services\Ai\LlmGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LlmGatewayTest extends TestCase
{
    public function test_provider_aliases_normalize(): void
    {
        $gateway = app(LlmGateway::class);

        config(['psiconecta.ai.provider' => 'chatgpt']);
        $this->assertSame('openai', $gateway->provider());

        config(['psiconecta.ai.provider' => 'anthropic']);
        $this->assertSame('claude', $gateway->provider());

        config(['psiconecta.ai.provider' => 'google']);
        $this->assertSame('gemini', $gateway->provider());
    }

    public function test_claude_chat_uses_anthropic_messages_api(): void
    {
        config([
            'psiconecta.ai.enabled' => true,
            'psiconecta.ai.provider' => 'claude',
            'psiconecta.ai.claude_api_key' => 'test-claude-key',
            'psiconecta.ai.claude_base_url' => 'https://api.anthropic.com',
            'psiconecta.ai.claude_chat_model' => 'claude-test',
        ]);

        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Olá do Claude']],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
            ], 200),
        ]);

        $result = app(LlmGateway::class)->chat('sistema', 'utilizador');

        $this->assertSame('Olá do Claude', $result['text']);
        $this->assertSame(15, $result['tokens_used']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'test-claude-key')
                && ($request['model'] ?? null) === 'claude-test';
        });
    }

    public function test_gemini_chat_uses_generate_content_api(): void
    {
        config([
            'psiconecta.ai.enabled' => true,
            'psiconecta.ai.provider' => 'gemini',
            'psiconecta.ai.gemini_api_key' => 'test-gemini-key',
            'psiconecta.ai.gemini_base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'psiconecta.ai.gemini_chat_model' => 'gemini-2.0-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Olá do Gemini']]]],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 8,
                    'candidatesTokenCount' => 4,
                ],
            ], 200),
        ]);

        $result = app(LlmGateway::class)->chat('sistema', 'utilizador');

        $this->assertSame('Olá do Gemini', $result['text']);
        $this->assertSame(12, $result['tokens_used']);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'gemini-2.0-flash:generateContent')
            && str_contains($request->url(), 'key=test-gemini-key'));
    }

    public function test_chat_ready_requires_provider_key(): void
    {
        $gateway = app(LlmGateway::class);

        config([
            'psiconecta.ai.enabled' => true,
            'psiconecta.ai.provider' => 'claude',
            'psiconecta.ai.claude_api_key' => '',
        ]);
        $this->assertFalse($gateway->chatReady());

        config(['psiconecta.ai.claude_api_key' => 'sk-ant-test']);
        $this->assertTrue($gateway->chatReady());
    }
}
