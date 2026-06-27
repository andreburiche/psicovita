<?php

namespace Tests\Feature;

use App\Enums\UserProfessionalFunction;
use App\Enums\UserProfessionalFileCategory;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserProfessionalFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileProfessionalTest extends TestCase
{
    use RefreshDatabase;

    public function test_professional_can_save_bio_on_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'professional_function' => UserProfessionalFunction::Psychotherapist->value,
            'professional_bio' => 'Psicóloga clínica com foco em TCC.',
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame(UserProfessionalFunction::Psychotherapist, $user->fresh()->professional_function);
        $this->assertSame('Psicóloga clínica com foco em TCC.', $user->fresh()->professional_bio);
    }

    public function test_professional_can_save_function_on_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Professional,
            'professional_function' => UserProfessionalFunction::Psychologist,
        ]);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'professional_function' => UserProfessionalFunction::Psychiatrist->value,
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame(UserProfessionalFunction::Psychiatrist, $user->fresh()->professional_function);
    }

    public function test_clinic_owner_can_upload_institution_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'professional_function' => UserProfessionalFunction::Psychologist->value,
            'institution_logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNotNull($user->institution_logo_path);
        Storage::disk('public')->assertExists($user->institution_logo_path);
    }

    public function test_professional_can_upload_multiple_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($user)->post(route('profile.professional-files.store'), [
            'category' => UserProfessionalFileCategory::Curriculum->value,
            'title' => 'Currículo',
            'files' => [
                UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('certificado.pdf', 80, 'application/pdf'),
            ],
        ])->assertRedirect(route('profile.edit'));

        $files = $user->fresh()->professionalFiles;
        $this->assertCount(2, $files);
        Storage::disk('local')->assertExists($files->first()->file_path);
    }

    public function test_professional_can_download_own_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => UserRole::Professional]);
        $path = 'professional-files/'.$user->id.'/test.pdf';
        Storage::disk('local')->put($path, 'conteudo');

        $file = UserProfessionalFile::query()->create([
            'user_id' => $user->id,
            'title' => 'CV',
            'category' => UserProfessionalFileCategory::Curriculum,
            'original_name' => 'cv.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 8,
        ]);

        $this->actingAs($user)
            ->get(route('profile.professional-files.download', $file))
            ->assertOk();
    }

    public function test_user_cannot_download_another_users_file(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create(['role' => UserRole::Professional]);
        $other = User::factory()->create(['role' => UserRole::Professional]);
        $path = 'professional-files/'.$owner->id.'/test.pdf';
        Storage::disk('local')->put($path, 'conteudo');

        $file = UserProfessionalFile::query()->create([
            'user_id' => $owner->id,
            'title' => 'CV',
            'category' => UserProfessionalFileCategory::Curriculum,
            'original_name' => 'cv.pdf',
            'file_path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 8,
        ]);

        $this->actingAs($other)
            ->get(route('profile.professional-files.download', $file))
            ->assertForbidden();
    }

    public function test_patient_cannot_upload_professional_files(): void
    {
        $user = User::factory()->create(['role' => UserRole::Patient]);

        $this->actingAs($user)->post(route('profile.professional-files.store'), [
            'category' => UserProfessionalFileCategory::Other->value,
            'files' => [UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf')],
        ])->assertForbidden();
    }
}
