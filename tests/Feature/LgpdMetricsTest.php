<?php

namespace Tests\Feature;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Enums\UserRole;
use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LgpdMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_lgpd_metrics(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $patientUser = User::factory()->create(['role' => UserRole::Patient]);

        DataSubjectRequest::query()->create([
            'user_id' => $patientUser->id,
            'type' => DataSubjectRequestType::Access,
            'status' => DataSubjectRequestStatus::Pending,
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.lgpd.metrics'))
            ->assertOk()
            ->assertSee(__('Métricas LGPD'), false)
            ->assertSee(__('Pendentes'), false);
    }

    public function test_professional_cannot_view_lgpd_metrics(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('admin.lgpd.metrics'))
            ->assertForbidden();
    }
}
