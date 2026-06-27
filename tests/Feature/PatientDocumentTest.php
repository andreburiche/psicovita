<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_attach_document_to_patient_chart(): void
    {
        Storage::fake('local');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $response = $this->actingAs($professional)->post(route('patients.documents.store', $patient), [
            'title' => 'Devolutiva escola',
            'category' => 'resposta_instituicao',
            'received_at' => now()->toDateString(),
            'file' => UploadedFile::fake()->create('devolutiva.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('patient_documents', [
            'patient_id' => $patient->id,
            'title' => 'Devolutiva escola',
        ]);
    }
}
