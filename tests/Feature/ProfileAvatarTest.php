<?php

namespace Tests\Feature;

use App\Enums\UserProfessionalFunction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_avatar_on_profile_update(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'professional_function' => UserProfessionalFunction::Psychologist->value,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 600, 600),
            'avatar_shape' => 'circle',
            'avatar_ring' => 'violet',
            'avatar_filter' => 'none',
            'remove_avatar' => '0',
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        $this->assertSame([
            'shape' => 'circle',
            'ring' => 'violet',
            'filter' => 'none',
        ], $user->resolvedAvatarStyle());
    }

    public function test_user_can_remove_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('avatar.jpg')->store('avatars/'.$user->id, 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'professional_function' => UserProfessionalFunction::Psychologist->value,
            'remove_avatar' => '1',
            'avatar_shape' => 'rounded',
            'avatar_ring' => 'emerald',
            'avatar_filter' => 'warm',
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertNull($user->avatar_path);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('rounded', $user->resolvedAvatarStyle()['shape']);
    }

    public function test_profile_page_shows_avatar_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee(__('Foto de perfil'), false);
    }

    public function test_avatar_is_served_via_media_route(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('avatar.jpg', 200, 200)->store('avatars/'.$user->id, 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        $this->get(route('media.user-avatar', $user))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->assertStringContainsString('/media/avatars/users/'.$user->id, (string) $user->fresh()->avatarUrl());
    }

    public function test_avatar_is_served_via_storage_fallback_route(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('avatar.jpg', 200, 200)->store('avatars/'.$user->id, 'public');
        $user->forceFill(['avatar_path' => $path])->save();

        $this->get('/storage/'.$path)
            ->assertOk();
    }
}
