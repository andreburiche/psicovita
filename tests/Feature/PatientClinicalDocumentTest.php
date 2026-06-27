<?php

namespace Tests\Feature;

use App\Enums\PatientClinicalDocumentType;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\PatientClinicalDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientClinicalDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_open_atestado_form(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)
            ->get(route('patients.clinical-documents.create', [$patient, 'atestado']))
            ->assertOk()
            ->assertSee(__('Atestado'), false)
            ->assertSee(__('Tipo de atestado'), false)
            ->assertSee(__('Pré-visualizar'), false)
            ->assertSee(__('Gerar PDF'), false);
    }

    public function test_professional_can_preview_atestado_pdf_without_saving(): void
    {
        $professional = User::factory()->create([
            'role' => UserRole::Professional,
            'crp_number' => '06/123456',
        ]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)
            ->post(route('patients.clinical-documents.preview', $patient), [
                'type' => PatientClinicalDocumentType::Atestado->value,
                'issued_at' => now()->toDateString(),
                'place' => 'São Paulo',
                'atestado_kind' => 'comparecimento',
                'session_date' => now()->toDateString(),
                'body' => 'Atesto comparecimento do paciente.',
            ]);

        $response->assertRedirect();

        $this->actingAs($professional)
            ->get($response->headers->get('Location'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertDatabaseCount('patient_clinical_documents', 0);
    }

    public function test_get_preview_without_token_redirects_with_message(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)
            ->get(route('patients.clinical-documents.preview.unavailable', $patient))
            ->assertRedirect(route('patients.show', ['patient' => $patient, 'tab' => 'document-requests']))
            ->assertSessionHas('error');
    }

    public function test_preview_rejects_invalid_afastamento_dates(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)
            ->post(route('patients.clinical-documents.preview', $patient), [
                'type' => PatientClinicalDocumentType::Atestado->value,
                'issued_at' => now()->toDateString(),
                'atestado_kind' => 'afastamento',
                'days' => 3,
                'start_date' => now()->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
                'body' => 'Texto do atestado.',
            ])
            ->assertSessionHasErrors('end_date');
    }

    public function test_professional_can_generate_declaracao_pdf(): void
    {
        $professional = User::factory()->create([
            'role' => UserRole::Professional,
            'crp_number' => '06/123456',
        ]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('patients.clinical-documents.store', $patient), [
            'type' => PatientClinicalDocumentType::Declaracao->value,
            'issued_at' => now()->toDateString(),
            'place' => 'São Paulo',
            'subject' => 'Declaração de acompanhamento',
            'body' => 'Declaro que o paciente está em acompanhamento psicológico.',
        ]);

        $document = PatientClinicalDocument::query()->first();
        $this->assertNotNull($document);

        $response->assertRedirect(route('patients.clinical-documents.pdf', [$patient, $document]));

        $this->actingAs($professional)
            ->get(route('patients.clinical-documents.pdf', [$patient, $document]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_patient_show_documents_tab_lists_clinical_documents_section(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)
            ->get(route('patients.show', ['patient' => $patient, 'tab' => 'document-requests']))
            ->assertOk()
            ->assertSee('data-test="clinical-documents-generate"', false)
            ->assertSee(__('Receita (Prescrição)'), false);
    }
}
