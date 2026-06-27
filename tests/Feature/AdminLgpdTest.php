<?php

namespace Tests\Feature;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Enums\UserRole;
use App\Mail\DataSubjectRequestResolvedMail;
use App\Models\DataSubjectRequest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminLgpdTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_lgpd_panel(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('admin.lgpd.requests.index'))
            ->assertForbidden();
    }

    public function test_admin_can_list_lgpd_requests(): void
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
            ->get(route('admin.lgpd.requests.index'))
            ->assertOk()
            ->assertSee($patientUser->email, false);
    }

    public function test_dpo_email_user_can_access_panel(): void
    {
        config(['compliance.lgpd.dpo_email' => 'dpo@test.local']);

        $dpo = User::factory()->create([
            'role' => UserRole::Professional,
            'email' => 'dpo@test.local',
        ]);

        $this->actingAs($dpo)
            ->get(route('admin.lgpd.requests.index'))
            ->assertOk();
    }

    public function test_admin_can_update_request_and_notify_patient(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'email' => 'paciente@test.local',
        ]);

        $request = DataSubjectRequest::query()->create([
            'user_id' => $patientUser->id,
            'type' => DataSubjectRequestType::Correction,
            'status' => DataSubjectRequestStatus::Pending,
            'details' => 'Corrigir telefone',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.lgpd.requests.update', $request), [
            'status' => DataSubjectRequestStatus::Completed->value,
            'response_notes' => 'Dados atualizados conforme solicitado.',
        ]);

        $response->assertRedirect(route('admin.lgpd.requests.show', $request));

        $request->refresh();
        $this->assertSame(DataSubjectRequestStatus::Completed, $request->status);
        $this->assertNotNull($request->completed_at);
        $this->assertSame('Dados atualizados conforme solicitado.', $request->response_notes);

        Mail::assertSent(DataSubjectRequestResolvedMail::class);
    }

    public function test_admin_can_export_requester_data(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $therapist = User::factory()->create(['role' => UserRole::Professional]);
        $email = 'export.admin@test.local';

        Patient::factory()->create([
            'professional_id' => $therapist->id,
            'email' => $email,
            'name' => 'Paciente Export',
        ]);

        $patientUser = User::factory()->create([
            'role' => UserRole::Patient,
            'email' => $email,
            'professional_id' => $therapist->id,
        ]);

        $request = DataSubjectRequest::query()->create([
            'user_id' => $patientUser->id,
            'type' => DataSubjectRequestType::Portability,
            'status' => DataSubjectRequestStatus::Pending,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.lgpd.requests.export', $request));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/json; charset=UTF-8');

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Paciente Export', $payload['patients'][0]['profile']['name']);
    }
}
