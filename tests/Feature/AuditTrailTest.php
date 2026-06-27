<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_entity_persists_to_audit_logs_table(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['professional_id' => $user->id]);

        $this->actingAs($user);

        AuditTrail::entity('view', 'patient', $patient);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'view',
            'entity' => 'patient',
            'subject_type' => $patient->getMorphClass(),
            'subject_id' => $patient->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame(1, AuditLog::query()->count());
    }
}
