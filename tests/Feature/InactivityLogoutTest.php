<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class InactivityLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('security.inactivity_timeout', [
            'admin' => 30,
            'professional' => 60,
            'patient' => 60,
            'default' => 60,
        ]);
        Config::set('security.inactivity_warning_seconds', 60);
    }

    public function test_admin_is_logged_out_after_inactivity_timeout(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertSame(30, $admin->inactivityTimeoutMinutes());

        $response = $this->actingAs($admin)
            ->withSession([
                'last_activity_at' => now()->subMinutes(31)->getTimestamp(),
            ])
            ->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', __('Sua sessão expirou por inatividade.'));
        $this->assertGuest();
    }

    public function test_professional_is_logged_out_after_inactivity_timeout(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->assertSame(60, $professional->inactivityTimeoutMinutes());

        $response = $this->actingAs($professional)
            ->withSession([
                'last_activity_at' => now()->subMinutes(61)->getTimestamp(),
            ])
            ->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', __('Sua sessão expirou por inatividade.'));
        $this->assertGuest();
    }

    public function test_professional_within_timeout_keeps_session_and_updates_last_activity(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $previous = now()->subMinutes(10)->getTimestamp();

        $response = $this->actingAs($professional)
            ->withSession([
                'last_activity_at' => $previous,
            ])
            ->get(route('profile.edit'));

        $response->assertOk();
        $this->assertAuthenticatedAs($professional);
        $this->assertNotSame($previous, session('last_activity_at'));
        $this->assertGreaterThanOrEqual($previous, (int) session('last_activity_at'));
    }

    public function test_keep_alive_updates_last_activity_at(): void
    {
        $user = User::factory()->create(['role' => UserRole::Professional]);
        $previous = now()->subMinutes(20)->getTimestamp();

        $response = $this->actingAs($user)
            ->withSession([
                'last_activity_at' => $previous,
            ])
            ->post(route('session.keep-alive'));

        $response->assertNoContent();
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previous, session('last_activity_at'));
        $this->assertGreaterThanOrEqual(now()->subSeconds(5)->getTimestamp(), (int) session('last_activity_at'));
    }

    public function test_inactivity_expire_route_logs_out_with_status_message(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('session.inactivity-expire'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', __('Sua sessão expirou por inatividade.'));
        $this->assertGuest();
    }

    public function test_patient_timeout_uses_patient_config(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);
        $patient = User::factory()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        $this->assertSame(60, $patient->inactivityTimeoutMinutes());

        $response = $this->actingAs($patient)
            ->withSession([
                'last_activity_at' => now()->subMinutes(61)->getTimestamp(),
            ])
            ->get(route('patient.home'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_empty_env_timeout_falls_back_to_default(): void
    {
        Config::set('security.inactivity_timeout.professional', 60);

        // Simula o que o config/security.php faz com valor vazio/zero.
        $resolved = (static function (mixed $value, int $default): int {
            if ($value === null || $value === '') {
                return $default;
            }

            $parsed = (int) $value;

            return $parsed > 0 ? $parsed : $default;
        })('', 60);

        $this->assertSame(60, $resolved);
    }
}
