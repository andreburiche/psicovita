<?php

namespace Tests\Feature;

use App\Services\WhatsApp\EvolutionWebhookSetupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EvolutionWebhookSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_docker_host_gateway_for_local_dev(): void
    {
        config([
            'app.url' => 'http://127.0.0.1:8080',
            'psiconecta.whatsapp.evolution.api_url' => 'http://127.0.0.1:8082',
            'psiconecta.whatsapp.evolution.webhook_url' => null,
        ]);

        $url = app(EvolutionWebhookSetupService::class)->resolveDeliveryUrl();

        $this->assertSame(
            'http://host.docker.internal:8080/api/v1/integrations/evolution/webhook',
            $url,
        );
    }

    public function test_sync_registers_webhook_on_evolution(): void
    {
        config([
            'psiconecta.whatsapp.enabled' => true,
            'psiconecta.whatsapp.driver' => 'evolution',
            'psiconecta.whatsapp.evolution.api_url' => 'http://evolution.test',
            'psiconecta.whatsapp.evolution.api_key' => 'test-key',
            'psiconecta.whatsapp.evolution.instance' => 'psiconecta',
            'app.url' => 'http://127.0.0.1:8080',
        ]);

        Http::fake([
            'evolution.test/webhook/set/psiconecta' => Http::response(['ok' => true], 200),
        ]);

        $service = app(EvolutionWebhookSetupService::class);
        $expectedUrl = $service->resolveDeliveryUrl();

        $result = $service->sync();

        $this->assertTrue($result['ok']);

        Http::assertSent(function ($request) use ($expectedUrl) {
            $body = $request->data();

            return str_ends_with($request->url(), '/webhook/set/psiconecta')
                && data_get($body, 'webhook.url') === $expectedUrl
                && data_get($body, 'webhook.enabled') === true
                && in_array('MESSAGES_UPSERT', data_get($body, 'webhook.events', []), true);
        });
    }
}
