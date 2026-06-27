<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Message;
use App\Models\Patient;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageRecipientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_messages_page_lists_patient_user_matched_by_patient_record_email(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        Patient::factory()->create([
            'professional_id' => $professional->id,
            'name' => 'João Ficha',
            'email' => 'joao.paciente@example.test',
            'phone' => '+351912345678',
        ]);

        $patientUser = User::factory()->create([
            'name' => 'João Conta',
            'email' => 'JOAO.PACIENTE@example.test',
            'role' => UserRole::Patient,
            'professional_id' => null,
        ]);

        $this->actingAs($professional);

        $this->get(route('conversations.index'))
            ->assertOk()
            ->assertSee($patientUser->name, false);
    }

    public function test_messages_page_lists_user_matched_by_ficha_email_even_if_role_is_professional(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        Patient::factory()->create([
            'professional_id' => $professional->id,
            'name' => 'Maria Ficha',
            'email' => 'maria@example.test',
        ]);

        $account = User::factory()->create([
            'name' => 'Maria Conta',
            'email' => 'MARIA@example.test',
            'role' => UserRole::Professional,
            'professional_id' => null,
        ]);

        $this->actingAs($professional);

        $this->get(route('conversations.index'))
            ->assertOk()
            ->assertSee($account->name, false);
    }

    public function test_professional_cannot_message_patient_user_not_linked_to_their_patients(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        Patient::factory()->create([
            'professional_id' => $professional->id,
            'email' => 'only-on-file@example.test',
        ]);

        $stranger = User::factory()->create([
            'email' => 'stranger@example.test',
            'role' => UserRole::Patient,
            'professional_id' => null,
        ]);

        $this->actingAs($professional);

        $this->post(route('messages.store'), [
            'recipient_id' => $stranger->id,
            'body' => 'Olá',
        ])->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseMissing('messages', [
            'sender_id' => $professional->id,
            'recipient_id' => $stranger->id,
        ]);
    }

    public function test_patient_can_open_messages_page_and_see_internal_message_from_professional(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        app(ConversationService::class)->sendMessage($conversation, $professional, 'Mensagem interna do profissional');

        $this->actingAs($patient)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Mensagem interna do profissional', false);
    }
}
