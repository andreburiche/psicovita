<?php

namespace Tests\Feature;

use App\Enums\ClinicalScaleType;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\PatientScaleAssessment;
use App\Models\PatientTherapeuticGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientScaleAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_open_bai_form(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)
            ->get(route('patients.scale-assessments.create', [$patient, 'bai']))
            ->assertOk()
            ->assertSee('data-test="scale-assessment-create"', false)
            ->assertSee(__('Escala de Ansiedade (BAI)'), false)
            ->assertSee(__('Salvar avaliação'), false)
            ->assertSee(__('Progresso'), false);
    }

    public function test_professional_can_store_bai_assessment(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $answers = [];
        foreach (config('clinical_scales.bai.questions') as $question) {
            $answers[$question['key']] = 1;
        }

        $this->actingAs($professional)
            ->post(route('patients.scale-assessments.store', $patient), [
                'scale_type' => ClinicalScaleType::Bai->value,
                'assessed_at' => now()->toDateString(),
                'answers' => $answers,
            ])
            ->assertRedirect(route('patients.show', ['patient' => $patient, 'tab' => 'assessments']));

        $assessment = PatientScaleAssessment::query()->first();
        $this->assertNotNull($assessment);
        $this->assertSame(21, $assessment->total_score);
        $this->assertSame('moderate', $assessment->severity);
    }

    public function test_assessments_tab_shows_chart_and_history(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        PatientScaleAssessment::query()->create([
            'patient_id' => $patient->id,
            'professional_id' => $professional->id,
            'scale_type' => ClinicalScaleType::Bdi,
            'assessed_at' => now(),
            'total_score' => 18,
            'severity' => 'mild',
            'severity_label' => 'Depressão leve',
            'is_risk' => false,
            'responses' => ['q01' => 1],
        ]);

        $this->actingAs($professional)
            ->get(route('patients.show', ['patient' => $patient, 'tab' => 'assessments']))
            ->assertOk()
            ->assertSee('data-test="patient-assessments-tab"', false)
            ->assertSee(__('Evolução dos resultados'), false)
            ->assertSee(__('Escala de Depressão (BDI)'), false);
    }

    public function test_professional_can_create_therapeutic_goal(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->create(['professional_id' => $professional->id]);

        $this->actingAs($professional)
            ->post(route('patients.therapeutic-goals.store', $patient), [
                'title' => 'Reduzir ansiedade',
                'status' => 'in_progress',
                'progress_percent' => 25,
            ])
            ->assertRedirect(route('patients.show', ['patient' => $patient, 'tab' => 'assessments']));

        $this->assertDatabaseHas('patient_therapeutic_goals', [
            'patient_id' => $patient->id,
            'title' => 'Reduzir ansiedade',
        ]);
    }
}
