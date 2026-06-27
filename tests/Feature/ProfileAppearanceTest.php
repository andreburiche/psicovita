<?php

namespace Tests\Feature;

use App\Enums\UserProfessionalFunction;
use App\Models\User;
use App\Support\UiAccentOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_ui_accent_on_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'professional_function' => UserProfessionalFunction::Psychologist->value,
            'ui_accent' => 'emerald',
        ])->assertRedirect(route('profile.edit'));

        $this->assertSame('emerald', $user->fresh()->resolvedUiAccent());
    }

    public function test_invalid_ui_accent_falls_back_to_default_on_save(): void
    {
        $user = User::factory()->create(['ui_accent' => 'violet']);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'professional_function' => UserProfessionalFunction::Psychologist->value,
            'ui_accent' => 'invalid-color',
        ])->assertSessionHasErrors('ui_accent');
    }

    public function test_profile_page_shows_appearance_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee(__('Aparência da aplicação'), false)
            ->assertSee(__('Esmeralda'), false);
    }

    public function test_ui_accent_options_resolve_unknown_values(): void
    {
        $this->assertSame('violet', UiAccentOptions::resolve(null));
        $this->assertSame('sky', UiAccentOptions::resolve('sky'));
        $this->assertSame('violet', UiAccentOptions::resolve('not-a-theme'));
    }
}
