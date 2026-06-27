<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Patient;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_open_conversations_inbox(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        app(ConversationService::class)->findOrCreateForUsers($professional, $patient);

        $this->actingAs($professional)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertSee($patient->name, false);
    }

    public function test_professional_can_send_message_in_conversation(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);

        $this->actingAs($professional)
            ->post(route('conversations.messages.store', $conversation), [
                'body' => 'Olá, como está?',
            ])
            ->assertRedirect(route('conversations.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $professional->id,
            'recipient_id' => $patient->id,
        ]);
    }

    public function test_patient_sees_thread_and_unread_count_decreases_after_open(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $service = app(ConversationService::class);
        $conversation = $service->findOrCreateForUsers($professional, $patient);
        $service->sendMessage($conversation, $professional, 'Mensagem para o paciente');

        $conversation->refresh();
        $this->assertSame(1, $conversation->unreadCountFor($patient));

        $this->actingAs($patient)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Mensagem para o paciente', false);

        $conversation->refresh();
        $this->assertSame(0, $conversation->unreadCountFor($patient));
    }

    public function test_legacy_messages_route_redirects_to_conversations(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('messages.index'))
            ->assertRedirect(route('conversations.index'));
    }

    public function test_start_from_patient_record_opens_conversation(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $patientRecord = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'paciente@example.test',
        ]);

        $patientUser = User::factory()->create([
            'email' => 'paciente@example.test',
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->actingAs($professional)
            ->get(route('patients.conversation', $patientRecord))
            ->assertRedirect(route('conversations.show', Conversation::query()->where('patient_id', $patientRecord->id)->first()));

        $this->assertDatabaseHas('conversations', [
            'professional_id' => $professional->id,
            'patient_user_id' => $patientUser->id,
            'patient_id' => $patientRecord->id,
        ]);
    }

    public function test_conversation_picker_lists_all_registered_patients(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $withPortal = Patient::factory()->create([
            'professional_id' => $professional->id,
            'name' => 'Com Portal',
            'email' => 'com.portal@example.test',
        ]);

        Patient::factory()->create([
            'professional_id' => $professional->id,
            'name' => 'Sem Portal',
            'email' => 'sem.portal@example.test',
        ]);

        User::factory()->create([
            'email' => 'com.portal@example.test',
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->actingAs($professional)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertSee('Com Portal', false)
            ->assertSee('Sem Portal', false)
            ->assertSee(__('sem conta no portal'), false);
    }

    public function test_start_conversation_from_picker_opens_linked_thread(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $patientRecord = Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'picker@example.test',
        ]);

        $patientUser = User::factory()->create([
            'email' => 'picker@example.test',
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->actingAs($professional)
            ->post(route('conversations.start'), ['patient_id' => $patientRecord->id])
            ->assertRedirect();

        $conversation = Conversation::query()->where('patient_id', $patientRecord->id)->first();
        $this->assertNotNull($conversation);

        $this->actingAs($professional)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee($patientRecord->name, false);
    }
}
