<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\TherapySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_endpoint_returns_dashboard_stats(): void
    {
        $user = User::factory()->create();
        Patient::factory()->count(2)->create(['professional_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/summary')
            ->assertOk()
            ->assertJsonStructure([
                'patients_count',
                'sessions_today',
                'monthly_revenue',
                'occupancy_rate',
            ]);
    }

    public function test_therapy_sessions_index_returns_paginated_json(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);
        TherapySession::factory()->create([
            'patient_id' => $patient->id,
            'professional_id' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/therapy-sessions')
            ->assertOk()
            ->assertJsonPath('data.0.patient_id', $patient->id);
    }
}
