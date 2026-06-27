<?php

namespace Tests\Feature;

use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Models\Patient;
use App\Models\ScheduleBlock;
use App\Models\TherapySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapySessionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_therapy_sessions_index_shows_stats_for_current_month(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $month = Carbon::now()->startOfMonth();

        TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'session_date' => $month->copy()->day(10)->format('Y-m-d'),
            'status' => TherapySessionStatus::Completed,
            'type' => TherapySessionType::Online,
        ]);

        TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'session_date' => $month->copy()->day(12)->format('Y-m-d'),
            'status' => TherapySessionStatus::Scheduled,
            'type' => TherapySessionType::InPerson,
        ]);

        TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'session_date' => $month->copy()->subMonth()->day(5)->format('Y-m-d'),
            'status' => TherapySessionStatus::Cancelled,
            'type' => TherapySessionType::Online,
        ]);

        $this->actingAs($user)
            ->get(route('therapy-sessions.index', ['month' => $month->format('Y-m')]))
            ->assertOk()
            ->assertSee(__('Relatório'))
            ->assertSee(__('Total'))
            ->assertSee('2', false);
    }

    public function test_therapy_sessions_index_filters_by_status(): void
    {
        $user = User::factory()->create();
        $completedPatient = Patient::factory()->create(['professional_id' => $user->id, 'name' => 'Paciente Concluido XYZ']);
        $scheduledPatient = Patient::factory()->create(['professional_id' => $user->id, 'name' => 'Paciente Agendado ABC']);
        $date = Carbon::now()->startOfMonth()->day(8)->format('Y-m-d');

        TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $completedPatient->id,
            'session_date' => $date,
            'status' => TherapySessionStatus::Completed,
        ]);

        TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $scheduledPatient->id,
            'session_date' => $date,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $response = $this->actingAs($user)
            ->get(route('therapy-sessions.index', [
                'month' => Carbon::parse($date)->format('Y-m'),
                'status' => TherapySessionStatus::Completed->value,
            ]))
            ->assertOk()
            ->assertSee('Paciente Concluido XYZ');

        preg_match('/id="sessions-list-heading".*?<tbody.*?<\/tbody>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringContainsString('Paciente Concluido XYZ', $matches[0]);
        $this->assertStringNotContainsString('Paciente Agendado ABC', $matches[0]);
    }

    public function test_can_mark_session_completed_from_quick_action(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $session = TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $this->actingAs($user)
            ->from(route('therapy-sessions.index'))
            ->patch(route('therapy-sessions.update-status', $session), [
                'status' => TherapySessionStatus::Completed->value,
            ])
            ->assertRedirect(route('therapy-sessions.index'))
            ->assertSessionHas('status');

        $this->assertSame(
            TherapySessionStatus::Completed,
            $session->fresh()->status
        );
    }

    public function test_can_mark_session_cancelled_from_quick_action(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $session = TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $this->actingAs($user)
            ->from(route('therapy-sessions.index'))
            ->patch(route('therapy-sessions.update-status', $session), [
                'status' => TherapySessionStatus::Cancelled->value,
            ])
            ->assertRedirect(route('therapy-sessions.index'));

        $this->assertSame(
            TherapySessionStatus::Cancelled,
            $session->fresh()->status
        );
    }

    public function test_quick_status_update_rejects_scheduled_status(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $session = TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'status' => TherapySessionStatus::Scheduled,
        ]);

        $this->actingAs($user)
            ->from(route('therapy-sessions.index'))
            ->patch(route('therapy-sessions.update-status', $session), [
                'status' => TherapySessionStatus::Scheduled->value,
            ])
            ->assertRedirect(route('therapy-sessions.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame(
            TherapySessionStatus::Scheduled,
            $session->fresh()->status
        );
    }

    public function test_schedule_index_shows_stats_and_respects_filters(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id, 'name' => 'Maria Silva Teste']);
        $month = Carbon::now()->startOfMonth();
        $date = $month->copy()->day(15)->format('Y-m-d');

        TherapySession::factory()->create([
            'professional_id' => $user->id,
            'patient_id' => $patient->id,
            'session_date' => $date,
            'status' => TherapySessionStatus::Scheduled,
            'type' => TherapySessionType::Online,
        ]);

        ScheduleBlock::query()->create([
            'professional_id' => $user->id,
            'block_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reason' => 'Férias',
        ]);

        $this->actingAs($user)
            ->get(route('schedule.index', [
                'month' => $month->format('Y-m'),
                'q' => 'Maria Silva',
            ]))
            ->assertOk()
            ->assertSee(__('Relatório'))
            ->assertSee('Maria Silva Teste')
            ->assertSee('1', false);
    }
}
