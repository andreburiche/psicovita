<?php

namespace Tests\Feature;

use App\Enums\DocumentRequestStatus;
use App\Enums\InstitutionType;
use App\Enums\UserRole;
use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentRequestShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_open_document_request_show_page(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $documentRequest = DocumentRequest::query()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'institution_name' => 'Escola Teste',
            'institution_type' => InstitutionType::School,
            'requested_documents' => ['Histórico escolar'],
            'request_reason' => 'Acompanhamento.',
            'request_date' => now()->toDateString(),
            'status' => DocumentRequestStatus::Pending,
            'patient_consent_at' => now(),
            'patient_consent_recorded_by' => $professional->id,
            'created_by' => $professional->id,
            'updated_by' => $professional->id,
        ]);

        $response = $this->actingAs($professional)->get(
            route('patients.document-requests.show', [$patient, $documentRequest])
        );

        $response->assertOk();
        $response->assertSee('Escola Teste', false);
    }
}
