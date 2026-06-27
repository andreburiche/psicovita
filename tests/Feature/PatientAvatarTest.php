<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_upload_avatar_on_patient_without_portal_account(): void
    {
        Storage::fake('public');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = Patient::factory()->for($professional, 'professional')->create([
            'email' => 'isolado@example.com',
        ]);

        $response = $this->actingAs($professional)->put(route('patients.update', $patient), [
            'name' => $patient->name,
            'email' => $patient->email,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 600, 600),
            'avatar_shape' => 'circle',
            'avatar_ring' => 'violet',
            'avatar_filter' => 'none',
            'remove_avatar' => '0',
        ]);

        $response->assertRedirect(route('patients.show', $patient));

        $patient->refresh();
        $this->assertNotNull($patient->avatar_path);
        Storage::disk('public')->assertExists($patient->avatar_path);
        $this->assertTrue($patient->hasAvatar());
    }

    public function test_patient_avatar_syncs_to_portal_user_when_email_matches(): void
    {
        Storage::fake('public');

        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $portalUser = User::factory()->create([
            'role' => UserRole::Patient,
            'email' => 'paciente@example.com',
        ]);
        $patient = Patient::factory()->for($professional, 'professional')->create([
            'email' => 'paciente@example.com',
            'email_hash' => ContactHasher::emailHash('paciente@example.com'),
        ]);

        $this->actingAs($professional)->put(route('patients.update', $patient), [
            'name' => $patient->name,
            'email' => $patient->email,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 600, 600),
            'avatar_shape' => 'rounded',
            'avatar_ring' => 'emerald',
            'avatar_filter' => 'warm',
            'remove_avatar' => '0',
        ])->assertRedirect();

        $portalUser->refresh();
        $patient->refresh();

        $this->assertNotNull($portalUser->avatar_path);
        Storage::disk('public')->assertExists($portalUser->avatar_path);
        $this->assertNull($patient->avatar_path);
        $this->assertSame('rounded', $patient->resolvedAvatarStyle()['shape']);
        $this->assertTrue($patient->hasAvatar());
    }
}
