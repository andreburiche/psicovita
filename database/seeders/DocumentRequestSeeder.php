<?php

namespace Database\Seeders;

use App\Enums\DocumentRequestStatus;
use App\Enums\InstitutionType;
use App\Models\DocumentRequest;
use App\Models\Patient;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentRequestSeeder extends Seeder
{
    public function run(): void
    {
        $professional = User::query()->where('role', UserRole::Professional)->first();
        if (! $professional) {
            return;
        }

        $patient = Patient::query()->where('professional_id', $professional->id)->first();
        if (! $patient) {
            return;
        }

        DocumentRequest::query()->firstOrCreate(
            [
                'patient_id' => $patient->id,
                'institution_name' => 'Escola Municipal Exemplo',
            ],
            [
                'professional_id' => $professional->id,
                'institution_type' => InstitutionType::School,
                'contact_name' => 'Coordenação pedagógica',
                'contact_email' => 'coordenacao@escola.exemplo.br',
                'requested_documents' => ['Histórico escolar', 'Relatório pedagógico'],
                'request_reason' => 'Acompanhamento psicológico e articulação com a rede de apoio.',
                'authorization_attached' => false,
                'request_date' => now()->subDays(5)->toDateString(),
                'expected_return_date' => now()->addDays(15)->toDateString(),
                'status' => DocumentRequestStatus::Sent,
                'patient_consent_at' => now()->subDays(5),
                'patient_consent_recorded_by' => $professional->id,
                'created_by' => $professional->id,
                'updated_by' => $professional->id,
            ]
        );
    }
}
