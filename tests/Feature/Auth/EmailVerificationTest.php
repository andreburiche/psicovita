<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertOk()
            ->assertSee('data-test="verify-email-page"', false)
            ->assertSee($user->email, false)
            ->assertSee(__('Confirme o seu e-mail'), false)
            ->assertSee(__('Reenviar e-mail de confirmação'), false);
    }

    public function test_verification_notification_mail_uses_portuguese_subject_and_custom_view(): void
    {
        $user = User::factory()->unverified()->create();
        $mail = (new VerifyEmail)->toMail($user);

        $this->assertStringContainsString((string) config('app.name'), $mail->subject);
        $this->assertStringContainsString('Confirmar', $mail->subject);
        $this->assertIsArray($mail->view);
        $this->assertSame('emails.verify-email', $mail->view['html']);
    }

    public function test_email_can_be_verified_when_logged_in_as_same_user(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::to(URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            false,
        ));

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_patient_redirects_to_patient_portal_after_verification_when_logged_in(): void
    {
        $professional = User::factory()->create();
        $user = User::factory()->unverified()->create([
            'role' => UserRole::Patient,
            'professional_id' => $professional->id,
        ]);

        Event::fake();

        $verificationUrl = URL::to(URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            false,
        ));

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('patient.home', absolute: false).'?verified=1');
    }

    public function test_guest_can_verify_email_via_signed_link_without_session(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::to(URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            false,
        ));

        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::to(URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')],
            false,
        ));

        $this->get($verificationUrl)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_absolute_signed_verification_url_still_validates(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)],
            true,
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
