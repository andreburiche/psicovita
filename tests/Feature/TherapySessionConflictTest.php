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

class TherapySessionConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_schedule_second_session_same_slot(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $date = Carbon::now()->addDays(2)->format('Y-m-d');

        TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $user->id,
            'session_date' => $date,
            'session_time' => '10:00:00',
            'status' => TherapySessionStatus::Scheduled,
            'type' => TherapySessionType::Online,
        ]);

        $this->actingAs($user);

        $response = $this->from(route('therapy-sessions.create'))->post(route('therapy-sessions.store'), [
            'patient_id' => $patient->id,
            'session_date' => $date,
            'session_time' => '10:00',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::Online->value,
        ]);

        $response->assertSessionHasErrors('session_time');
    }

    public function test_cannot_schedule_session_inside_block(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        $date = Carbon::now()->addDays(5)->format('Y-m-d');

        ScheduleBlock::query()->create([
            'professional_id' => $user->id,
            'block_date' => $date,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'reason' => 'Bloqueio teste',
        ]);

        $this->actingAs($user);

        $response = $this->from(route('therapy-sessions.create'))->post(route('therapy-sessions.store'), [
            'patient_id' => $patient->id,
            'session_date' => $date,
            'session_time' => '14:30',
            'status' => TherapySessionStatus::Scheduled->value,
            'type' => TherapySessionType::InPerson->value,
        ]);

        $response->assertSessionHasErrors('session_time');
    }
}
