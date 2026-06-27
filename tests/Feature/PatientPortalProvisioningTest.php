<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\PatientPortalInvitation;
use App\Models\User;
use App\Notifications\PatientPortalInvitationNotification;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PatientPortalProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_provision_portal_access_when_creating_patient(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $response = $this->actingAs($professional)->post(route('patients.store'), [
            'name' => 'Maria Portal',
            'email' => 'maria.portal@example.com',
            'create_portal_access' => '1',
            'send_portal_invite_email' => '1',
            'portal_lgpd_acknowledged' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $patient = Patient::query()->where('email_hash', ContactHasher::emailHash('maria.portal@example.com'))->first();
        $this->assertNotNull($patient);

        $portalUser = User::query()->where('email_hash', ContactHasher::emailHash('maria.portal@example.com'))->first();
        $this->assertNotNull($portalUser);
        $this->assertTrue($portalUser->isPatient());
        $this->assertNull($portalUser->email_verified_at);

        $invitation = PatientPortalInvitation::query()->where('patient_id', $patient->id)->first();
        $this->assertNotNull($invitation);
        $this->assertTrue($invitation->isPending());

        Notification::assertSentOnDemand(PatientPortalInvitationNotification::class);
    }

    public function test_patient_can_activate_portal_with_invitation_token(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)->post(route('patients.store'), [
            'name' => 'João Portal',
            'email' => 'joao.portal@example.com',
            'create_portal_access' => '1',
            'send_portal_invite_email' => '1',
            'portal_lgpd_acknowledged' => '1',
        ]);

        $invitation = PatientPortalInvitation::query()->firstOrFail();

        $response = $this->post(route('patient-portal.activate.store', $invitation->token), [
            'password' => 'NovaSenhaSegura123!',
            'password_confirmation' => 'NovaSenhaSegura123!',
            'terms_accepted' => '1',
        ]);

        $portalUser = $invitation->fresh()->user;
        $response->assertRedirect(route('patient.home'));
        $this->assertAuthenticatedAs($portalUser);
        $this->assertNotNull($portalUser->fresh()->email_verified_at);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_conversation_starts_after_portal_provisioning(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)->post(route('patients.store'), [
            'name' => 'Ana Conversa',
            'email' => 'ana.conversa@example.com',
            'create_portal_access' => '1',
            'send_portal_invite_email' => '1',
            'portal_lgpd_acknowledged' => '1',
        ]);

        $patient = Patient::query()->where('email_hash', ContactHasher::emailHash('ana.conversa@example.com'))->firstOrFail();

        $response = $this->actingAs($professional)->get(route('patients.conversation', $patient));

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
        $this->assertStringContainsString('conversas', $response->headers->get('Location') ?? '');
    }

    public function test_professional_can_resend_portal_invite(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->for($professional, 'professional')->create([
            'email' => 'reenvio@example.com',
        ]);

        $this->actingAs($professional)->post(route('patients.portal-invite.resend', $patient), [
            'portal_lgpd_acknowledged' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentOnDemand(PatientPortalInvitationNotification::class);
    }

    public function test_first_portal_invite_requires_lgpd_acknowledgment(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->for($professional, 'professional')->create([
            'email' => 'lgpd@example.com',
        ]);

        $this->actingAs($professional)->from(route('patients.show', $patient))
            ->post(route('patients.portal-invite.resend', $patient))
            ->assertRedirect(route('patients.show', $patient))
            ->assertSessionHasErrors('portal_lgpd_acknowledged');
    }

    public function test_resend_portal_invite_can_send_whatsapp(): void
    {
        Notification::fake();

        Config::set('psiconecta.whatsapp.enabled', true);
        Config::set('psiconecta.whatsapp.driver', 'evolution');
        Config::set('psiconecta.whatsapp.default_calling_code', '55');
        Config::set('psiconecta.whatsapp.evolution.api_url', 'http://evolution.test');
        Config::set('psiconecta.whatsapp.evolution.api_key', 'test-api-key');
        Config::set('psiconecta.whatsapp.evolution.instance', 'psiconecta');

        Http::fake([
            'evolution.test/message/sendText/psiconecta' => Http::response([
                'key' => ['id' => 'INVITE123'],
            ], 200),
        ]);

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->for($professional, 'professional')->create([
            'email' => 'whatsapp.invite@example.com',
            'phone' => '21987874549',
        ]);

        $this->actingAs($professional)->post(route('patients.portal-invite.resend', $patient), [
            'send_portal_invite_email' => '1',
            'send_portal_invite_whatsapp' => '1',
            'portal_lgpd_acknowledged' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentOnDemand(PatientPortalInvitationNotification::class);

        Http::assertSent(function ($request) {
            $text = (string) ($request['text'] ?? '');

            return $request->url() === 'http://evolution.test/message/sendText/psiconecta'
                && str_contains($text, 'portal/activar/')
                && preg_match("/\n\nhttps?:\/\/[^\s]+\/portal\/activar\/[^\s]+\n\n/", $text) === 1;
        });
    }
}
