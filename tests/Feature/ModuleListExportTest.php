<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleListExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_patients_pdf_and_excel_export(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);
        Patient::factory()->create(['professional_id' => $user->id, 'name' => 'Paciente Export']);

        $this->actingAs($user)
            ->get(route('patients.export.pdf', ['q' => 'Export']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('patients.export.excel'))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
    }

    public function test_payments_export_respects_status_filter(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        Payment::factory()->create([
            'patient_id' => $patient->id,
            'status' => PaymentStatus::Paid,
            'amount' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('payments.export.pdf', ['status' => PaymentStatus::Paid->value]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('payments.export.excel'))
            ->assertOk();
    }

    public function test_clinical_records_and_reports_export(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        ClinicalRecord::query()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'content' => 'Nota clínica de teste para exportação.',
        ]);

        $this->actingAs($user)
            ->get(route('clinical-records.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('clinical-records.export.excel'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('reports.export.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($user)
            ->get(route('reports.export.excel'))
            ->assertOk();
    }

    public function test_export_requires_authentication(): void
    {
        $this->get(route('patients.export.pdf'))->assertRedirect(route('login'));
        $this->get(route('payments.export.excel'))->assertRedirect(route('login'));
        $this->get(route('clinical-records.export.pdf'))->assertRedirect(route('login'));
        $this->get(route('reports.export.excel'))->assertRedirect(route('login'));
    }
}
