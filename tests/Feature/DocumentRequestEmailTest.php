<?php

namespace Tests\Feature;

use App\Enums\DocumentRequestStatus;
use App\Enums\InstitutionType;
use App\Enums\UserRole;
use App\Mail\DocumentRequestOficioMail;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestAccessLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DocumentRequestEmailTest extends TestCase
{
    use RefreshDatabase;

    private function createDocumentRequest(User $professional, Patient $patient): DocumentRequest
    {
        return DocumentRequest::query()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'institution_name' => 'Escola Teste',
            'institution_type' => InstitutionType::School,
            'contact_email' => 'secretaria@escola.test',
            'requested_documents' => ['Histórico escolar'],
            'request_reason' => 'Acompanhamento psicológico.',
            'request_date' => now()->toDateString(),
            'status' => DocumentRequestStatus::Pending,
            'patient_consent_at' => now(),
            'patient_consent_recorded_by' => $professional->id,
            'created_by' => $professional->id,
            'updated_by' => $professional->id,
        ]);
    }

    public function test_professional_can_send_document_request_by_email(): void
    {
        Mail::fake();

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $documentRequest = $this->createDocumentRequest($professional, $patient);

        $response = $this->actingAs($professional)->post(
            route('patients.document-requests.send-email', [$patient, $documentRequest]),
            [
                'to' => 'destino@instituicao.test',
                'message' => 'Segue solicitação formal.',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Mail::assertSent(DocumentRequestOficioMail::class, function (DocumentRequestOficioMail $mail) {
            return $mail->hasTo('destino@instituicao.test');
        });

        $documentRequest->refresh();

        $this->assertSame(DocumentRequestStatus::Sent, $documentRequest->status);
        $this->assertSame('destino@instituicao.test', $documentRequest->last_email_sent_to);
        $this->assertNotNull($documentRequest->last_email_sent_at);
        $this->assertSame($professional->id, $documentRequest->last_email_sent_by);

        $this->assertDatabaseHas('document_request_access_logs', [
            'document_request_id' => $documentRequest->id,
            'user_id' => $professional->id,
            'action' => DocumentRequestAccessLog::ACTION_EMAIL_SENT,
        ]);
    }

    public function test_show_page_displays_email_form(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);
        $documentRequest = $this->createDocumentRequest($professional, $patient);

        $response = $this->actingAs($professional)->get(
            route('patients.document-requests.show', [$patient, $documentRequest])
        );

        $response->assertOk();
        $response->assertSee(__('Enviar por e-mail'), false);
        $response->assertSee('secretaria@escola.test', false);
    }

    public function test_other_professional_cannot_send_email(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['role' => UserRole::Professional]);
        $other = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $owner->id]);
        $documentRequest = $this->createDocumentRequest($owner, $patient);

        $response = $this->actingAs($other)->post(
            route('patients.document-requests.send-email', [$patient, $documentRequest]),
            ['to' => 'destino@instituicao.test']
        );

        $response->assertNotFound();
        Mail::assertNothingSent();
    }
}
