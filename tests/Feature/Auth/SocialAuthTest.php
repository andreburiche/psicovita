<?php

namespace Tests\Feature\Auth;

use App\Enums\UserProfessionalFunction;
use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-test-id',
            'services.google.client_secret' => 'google-test-secret',
        ]);
    }

    private function mockSocialiteCallback(string $provider, SocialiteUser $socialUser): void
    {
        $driver = Mockery::mock(Provider::class);
        $driver->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with($provider)
            ->andReturn($driver);
    }

    private function fakeSocialiteUser(string $id, string $email, string $name = 'Social User'): SocialiteUser
    {
        $user = Mockery::mock(SocialiteUser::class);
        $user->shouldReceive('getId')->andReturn($id);
        $user->shouldReceive('getEmail')->andReturn($email);
        $user->shouldReceive('getName')->andReturn($name);
        $user->shouldReceive('getAvatar')->andReturn(null);

        return $user;
    }

    public function test_google_callback_logs_in_existing_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.test',
            'role' => UserRole::Professional,
        ]);

        $this->mockSocialiteCallback('google', $this->fakeSocialiteUser('google-1', 'existing@example.test'));

        $response = $this->get('/auth/google/callback');

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-1',
        ]);
    }

    public function test_google_callback_redirects_new_user_to_complete_registration(): void
    {
        $this->mockSocialiteCallback('google', $this->fakeSocialiteUser('google-2', 'newuser@example.test', 'Novo Utilizador'));

        $response = $this->get('/auth/google/callback');

        $this->assertGuest();
        $response->assertRedirect(route('social.register.complete'));
        $response->assertSessionHas('social_registration.email', 'newuser@example.test');
    }

    public function test_social_registration_creates_professional_account(): void
    {
        $payload = [
            'provider' => 'google',
            'provider_id' => 'google-3',
            'email' => 'pro.social@example.test',
            'name' => 'Pro Social',
            'avatar' => null,
            'becomes_patient' => false,
            'role' => UserRole::Professional->value,
            'professional_id' => null,
        ];

        $response = $this->withSession(['social_registration' => $payload])
            ->post('/auth/social/complete', [
                'name' => 'Pro Social',
                'terms_accepted' => '1',
                'professional_function' => UserProfessionalFunction::Psychologist->value,
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email_hash' => ContactHasher::emailHash('pro.social@example.test'),
            'role' => UserRole::Professional->value,
        ]);

        $user = User::findByEmail('pro.social@example.test');
        $this->assertNotNull($user);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-3',
        ]);
    }

    public function test_social_registration_creates_patient_when_email_matches_single_patient_file(): void
    {
        $therapist = User::factory()->create(['role' => UserRole::Professional]);

        Patient::factory()->create([
            'professional_id' => $therapist->id,
            'email' => 'paciente.social@example.test',
        ]);

        $payload = [
            'provider' => 'google',
            'provider_id' => 'google-4',
            'email' => 'paciente.social@example.test',
            'name' => 'Paciente Social',
            'avatar' => null,
            'becomes_patient' => true,
            'role' => UserRole::Patient->value,
            'professional_id' => $therapist->id,
        ];

        $response = $this->withSession(['social_registration' => $payload])
            ->post('/auth/social/complete', [
                'name' => 'Paciente Social',
                'terms_accepted' => '1',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('patient.home', absolute: false));

        $this->assertDatabaseHas('users', [
            'email_hash' => ContactHasher::emailHash('paciente.social@example.test'),
            'role' => UserRole::Patient->value,
            'professional_id' => $therapist->id,
        ]);
    }

    public function test_google_callback_without_email_returns_to_login_with_error(): void
    {
        $this->mockSocialiteCallback('google', $this->fakeSocialiteUser('google-5', ''));

        $response = $this->get('/auth/google/callback');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_unconfigured_provider_returns_service_unavailable(): void
    {
        config(['services.google.client_id' => null]);

        $response = $this->get('/auth/google/redirect');

        $response->assertStatus(503);
    }
}
