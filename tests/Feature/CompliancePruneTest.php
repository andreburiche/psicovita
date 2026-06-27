<?php

namespace Tests\Feature;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompliancePruneTest extends TestCase
{
    use RefreshDatabase;

    public function test_prune_removes_old_completed_requests(): void
    {
        config(['compliance.retention.data_subject_requests_days' => 30]);

        $user = User::factory()->create();

        DataSubjectRequest::query()->create([
            'user_id' => $user->id,
            'type' => DataSubjectRequestType::Access,
            'status' => DataSubjectRequestStatus::Completed,
            'completed_at' => now()->subDays(60),
            'ip_address' => '127.0.0.1',
        ]);

        DataSubjectRequest::query()->create([
            'user_id' => $user->id,
            'type' => DataSubjectRequestType::Access,
            'status' => DataSubjectRequestStatus::Pending,
            'ip_address' => '127.0.0.1',
        ]);

        $this->artisan('psiconecta:compliance-prune')
            ->assertSuccessful();

        $this->assertDatabaseCount('data_subject_requests', 1);
        $this->assertDatabaseHas('data_subject_requests', [
            'status' => DataSubjectRequestStatus::Pending->value,
        ]);
    }

    public function test_prune_dry_run_does_not_delete(): void
    {
        config(['compliance.retention.data_subject_requests_days' => 30]);

        $user = User::factory()->create();

        DataSubjectRequest::query()->create([
            'user_id' => $user->id,
            'type' => DataSubjectRequestType::Deletion,
            'status' => DataSubjectRequestStatus::Rejected,
            'completed_at' => now()->subDays(90),
            'ip_address' => '127.0.0.1',
        ]);

        $this->artisan('psiconecta:compliance-prune', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('data_subject_requests', 1);
    }
}
