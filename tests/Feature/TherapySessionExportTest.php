<?php

namespace Tests\Feature;

use App\Enums\TherapySessionStatus;
use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapySessionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_therapy_sessions_pdf_export_downloads_with_filters(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id, 'name' => 'Export PDF Patient']);
        $month = Carbon::now()->startOfMonth();

        TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'session_date' => $month->copy()->day(10)->format('Y-m-d'),
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $response = $this->actingAs($user)->get(route('therapy-sessions.export.pdf', [
            'month' => $month->format('Y-m'),
            'status' => TherapySessionStatus::Scheduled->value,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_schedule_excel_export_downloads(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id, 'name' => 'Export Excel Patient']);
        $month = Carbon::now()->startOfMonth();

        TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'session_date' => $month->copy()->day(12)->format('Y-m-d'),
            'status' => TherapySessionStatus::Completed,
        ]);

        $response = $this->actingAs($user)->get(route('schedule.export.excel', [
            'month' => $month->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
    }

    public function test_export_requires_authentication(): void
    {
        $this->get(route('therapy-sessions.export.pdf'))->assertRedirect(route('login'));
        $this->get(route('schedule.export.excel'))->assertRedirect(route('login'));
    }
}
