<?php

namespace Tests\Feature;

use App\Enums\InstitutionType;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_open_document_request_create_form(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->get(route('patients.document-requests.create', $patient));

        $response->assertOk();
        $response->assertSee(__('Nova solicitação de documentos'), false);
        $response->assertSee(__('Antes de registrar'), false);
        $response->assertSee(__('Instituição solicitante'), false);
    }

    public function test_professional_can_create_document_request_for_patient(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('patients.document-requests.store', $patient), [
            'institution_name' => 'Clínica Parceira',
            'institution_type' => InstitutionType::Doctor->value,
            'requested_documents' => ['Laudo médico'],
            'request_reason' => 'Continuidade do tratamento.',
            'request_date' => now()->toDateString(),
            'status' => 'pendente',
            'patient_consent_confirmed' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('document_requests', [
            'patient_id' => $patient->id,
            'institution_name' => 'Clínica Parceira',
        ]);
    }

    public function test_patient_show_tab_lists_document_requests(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->get(route('patients.show', [
            'patient' => $patient,
            'tab' => 'document-requests',
        ]));

        $response->assertOk();
        $response->assertSee(__('Anexar documento na ficha'), false);
    }
}
