<?php

namespace Tests\Feature;

use App\Enums\TherapySessionStatus;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use App\Notifications\TherapySessionTomorrowReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OpenApiAndIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_json_is_public_and_valid_shape(): void
    {
        $this->getJson('/api/v1/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('info.title', 'PsiConecta API');
    }

    public function test_whatsapp_webhook_verify_returns_challenge(): void
    {
        config(['psiconecta.whatsapp.webhook_verify_token' => 'my-verify']);

        $query = http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'my-verify',
            'hub.challenge' => 'CHALLENGE_OK',
        ]);

        $this->get('/api/v1/integrations/whatsapp/webhook?'.$query)
            ->assertOk()
            ->assertSee('CHALLENGE_OK', false);
    }

    public function test_whatsapp_webhook_verify_rejects_bad_token(): void
    {
        config(['psiconecta.whatsapp.webhook_verify_token' => 'expected']);

        $query = http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong',
            'hub.challenge' => 'x',
        ]);

        $this->getJson('/api/v1/integrations/whatsapp/webhook?'.$query)
            ->assertForbidden();
    }

    public function test_whatsapp_post_returns_503_when_disabled(): void
    {
        config(['psiconecta.whatsapp.enabled' => false]);

        $this->postJson('/api/v1/integrations/whatsapp/webhook', ['entry' => []])
            ->assertStatus(503);
    }

    public function test_session_reminders_command_sends_database_notification_once_per_day(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);

        $session = TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $user->id,
            'session_date' => now()->addDay()->toDateString(),
            'session_time' => '10:00:00',
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $this->assertSame(0, Artisan::call('psiconecta:session-reminders'));

        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(TherapySessionTomorrowReminder::class, $user->fresh()->notifications->first()->type);
        $this->assertSame($session->id, (int) data_get($user->fresh()->notifications->first()->data, 'therapy_session_id'));

        Artisan::call('psiconecta:session-reminders');
        $this->assertDatabaseCount('notifications', 1);
    }
}
