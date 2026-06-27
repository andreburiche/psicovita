<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConversationPhase2Test extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_search_filters_by_patient_name(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional, 'name' => 'Dr. Ana']);
        $match = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id, 'name' => 'Carlos Match']);
        $other = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id, 'name' => 'Bruno Outro']);

        app(ConversationService::class)->findOrCreateForUsers($professional, $match);
        app(ConversationService::class)->findOrCreateForUsers($professional, $other);

        $this->actingAs($professional)
            ->get(route('conversations.index', ['q' => 'Carlos']))
            ->assertOk()
            ->assertSee('Carlos Match', false);

        $this->assertCount(1, app(ConversationService::class)->inboxFor($professional, 'Carlos'));
    }

    public function test_professional_can_send_message_with_attachment(): void
    {
        Storage::fake('local');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);

        $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        $this->actingAs($professional)
            ->post(route('conversations.messages.store', $conversation), [
                'body' => 'Segue documento',
                'attachment' => $file,
            ])
            ->assertRedirect(route('conversations.show', $conversation));

        $this->assertDatabaseHas('message_attachments', [
            'original_name' => 'documento.pdf',
        ]);
    }

    public function test_poll_returns_new_messages(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);
        $service = app(ConversationService::class);
        $conversation = $service->findOrCreateForUsers($professional, $patient);
        $message = $service->sendMessage($conversation, $professional, 'Nova via poll');

        $this->actingAs($patient)
            ->getJson(route('conversations.poll', ['conversation' => $conversation, 'after_id' => $message->id - 1]))
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Nova via poll');
    }

    public function test_patient_can_grant_whatsapp_consent(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $conversation->update(['whatsapp_enabled' => true]);

        $this->actingAs($patient)
            ->post(route('conversations.whatsapp.consent', $conversation))
            ->assertRedirect();

        $conversation->refresh();
        $this->assertNotNull($conversation->patient_whatsapp_consent_at);
        $this->assertTrue($conversation->canSyncWhatsApp());
    }

    public function test_patient_sees_consent_banner_on_conversation_show(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional, 'name' => 'Dr. Ana']);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id, 'name' => 'Pedro Paciente']);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $conversation->update(['whatsapp_enabled' => true]);

        $this->actingAs($patient)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee(__('Consentir sincronização WhatsApp'), false)
            ->assertDontSee(__('Enviar lembrete'), false)
            ->assertDontSee(__('Prontuário'), false);
    }

    public function test_professional_sees_reminder_not_consent_button(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $conversation->update(['whatsapp_enabled' => true]);

        Config::set('psiconecta.whatsapp.enabled', true);

        $this->actingAs($professional)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee(__('Enviar lembrete'), false)
            ->assertSee(__('Vista do profissional — aguarda consentimento do paciente'), false)
            ->assertDontSee(__('Consentir sincronização WhatsApp'), false);
    }

    public function test_professional_can_send_whatsapp_consent_reminder_email(): void
    {
        Notification::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'email' => 'paciente.consent@example.com',
        ]);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $conversation->update(['whatsapp_enabled' => true]);

        $this->actingAs($professional)
            ->post(route('conversations.whatsapp.consent-remind', $conversation), [
                'send_email' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($patient, \App\Notifications\WhatsAppConsentReminderNotification::class);
    }

    public function test_thread_search_finds_message_body(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);
        $service = app(ConversationService::class);
        $conversation = $service->findOrCreateForUsers($professional, $patient);
        $service->sendMessage($conversation, $professional, 'PalavraSecretaXYZ no texto');

        $this->actingAs($professional)
            ->get(route('conversations.show', ['conversation' => $conversation, 'q' => 'SecretaXYZ']))
            ->assertOk()
            ->assertSee('PalavraSecretaXYZ', false);
    }

    public function test_whatsapp_mirror_blocked_without_patient_phone(): void
    {
        Config::set('psiconecta.whatsapp.enabled', true);
        Config::set('psiconecta.whatsapp.driver', 'evolution');
        Config::set('psiconecta.whatsapp.evolution.api_url', 'http://evolution.test');
        Config::set('psiconecta.whatsapp.evolution.api_key', 'key');
        Config::set('psiconecta.whatsapp.evolution.instance', 'psiconecta');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
            'phone' => null,
        ]);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $conversation->update([
            'whatsapp_enabled' => true,
            'patient_whatsapp_consent_at' => now(),
        ]);

        $this->actingAs($professional)
            ->post(route('conversations.messages.store', $conversation), ['body' => 'Olá'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
