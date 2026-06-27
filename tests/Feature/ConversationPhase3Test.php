<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ClinicalRecord;
use App\Models\Conversation;
use App\Models\Patient;
use App\Models\User;
use App\Services\ConversationExportService;
use App\Services\ConversationService;
use App\Services\ConversationTypingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConversationPhase3Test extends TestCase
{
    use RefreshDatabase;

    public function test_export_service_builds_transcript(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional, 'name' => 'Dr. Teste']);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id, 'name' => 'Paciente A']);
        $service = app(ConversationService::class);
        $conversation = $service->findOrCreateForUsers($professional, $patient);
        $service->sendMessage($conversation, $professional, 'Olá paciente');

        $transcript = app(ConversationExportService::class)->buildTranscript($conversation);

        $this->assertStringContainsString('Olá paciente', $transcript);
        $this->assertStringContainsString('Dr. Teste', $transcript);
        $this->assertStringContainsString('Paciente A', $transcript);
    }

    public function test_professional_can_archive_conversation_to_clinical_record(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patientRecord = Patient::factory()->create(['professional_id' => $professional->id]);
        $patientUser = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);

        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patientUser, $patientRecord);
        app(ConversationService::class)->sendMessage($conversation, $professional, 'Nota clínica da conversa');

        $this->actingAs($professional)
            ->post(route('conversations.archive.record', $conversation))
            ->assertRedirect();

        $this->assertDatabaseHas('clinical_records', [
            'patient_id' => $patientRecord->id,
            'professional_id' => $professional->id,
        ]);

        $record = ClinicalRecord::query()->first();
        $this->assertStringContainsString('Nota clínica da conversa', (string) $record->content);
    }

    public function test_typing_indicator_detects_peer(): void
    {
        Cache::flush();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        $typing = app(ConversationTypingService::class);

        $typing->pulse($conversation, $patient);

        $this->assertTrue($typing->isPeerTyping($conversation, $professional));
        $this->assertFalse($typing->isPeerTyping($conversation, $patient));
    }

    public function test_poll_includes_peer_typing_flag(): void
    {
        Cache::flush();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);

        app(ConversationTypingService::class)->pulse($conversation, $patient);

        $this->actingAs($professional)
            ->getJson(route('conversations.poll', ['conversation' => $conversation, 'after_id' => 0]))
            ->assertOk()
            ->assertJsonPath('peer_typing', true);
    }

    public function test_professional_can_export_pdf(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create(['role' => UserRole::Patient, 'professional_id' => $professional->id]);
        $conversation = app(ConversationService::class)->findOrCreateForUsers($professional, $patient);
        app(ConversationService::class)->sendMessage($conversation, $professional, 'Texto PDF');

        $response = $this->actingAs($professional)
            ->get(route('conversations.export.pdf', $conversation));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
