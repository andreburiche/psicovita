<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_export_audit_logs(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('admin.lgpd.audit.export'))
            ->assertForbidden();
    }

    public function test_admin_can_export_audit_logs_as_csv(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        AuditLog::query()->create([
            'action' => 'view',
            'entity' => 'patient',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.lgpd.audit.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('view', $content);
        $this->assertStringContainsString('patient', $content);
        $this->assertStringNotContainsString($admin->email, $content);
    }

    public function test_export_creates_audit_trail_entry(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.lgpd.audit.export'))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'export',
            'entity' => 'audit_logs',
            'user_id' => $admin->id,
        ]);
    }
}
