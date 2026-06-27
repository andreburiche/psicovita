<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\EvolutionWebhookSetupService;
use App\Services\WhatsAppConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppIntegrationController extends Controller
{
    public function __construct(
        private readonly WhatsAppConversationService $whatsApp,
        private readonly EvolutionWebhookSetupService $webhookSetup,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $driver = (string) config('psiconecta.whatsapp.driver', 'meta');
        $evolutionApiUrl = rtrim((string) config('psiconecta.whatsapp.evolution.api_url'), '/');

        return view('admin.integrations.whatsapp', [
            'enabled' => (bool) config('psiconecta.whatsapp.enabled', false),
            'configured' => $this->whatsApp->isConfigured(),
            'driver' => $driver,
            'driverLabel' => $this->whatsApp->driverLabel(),
            'webhookUrl' => $this->webhookUrl($driver),
            'webhookDeliveryUrl' => $driver === 'evolution'
                ? $this->webhookSetup->resolveDeliveryUrl()
                : null,
            'evolutionApiUrl' => $evolutionApiUrl,
            'evolutionManagerUrl' => $driver === 'evolution' ? "{$evolutionApiUrl}/manager" : null,
            'evolutionInstance' => config('psiconecta.whatsapp.evolution.instance'),
            'hasWebhookToken' => filled(config('psiconecta.whatsapp.evolution.webhook_token')),
            'appUrl' => rtrim((string) config('app.url'), '/'),
        ]);
    }

    public function syncWebhook(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $result = app(EvolutionWebhookSetupService::class)->sync();

        return redirect()
            ->route('admin.integrations.whatsapp')
            ->with($result['ok'] ? 'connection_ok' : 'connection_error', $result['message'])
            ->with('connection_details', ['webhook' => $result]);
    }

    public function testConnection(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $result = $this->whatsApp->testConnection();

        return redirect()
            ->route('admin.integrations.whatsapp')
            ->with($result['ok'] ? 'connection_ok' : 'connection_error', $result['message'])
            ->with('connection_details', $result['details'] ?? []);
    }

    private function webhookUrl(string $driver): string
    {
        $path = $driver === 'evolution'
            ? '/api/v1/integrations/evolution/webhook'
            : '/api/v1/integrations/whatsapp/webhook';

        return url($path);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
