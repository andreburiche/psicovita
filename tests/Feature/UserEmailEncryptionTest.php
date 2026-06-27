<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserEmailEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_email_is_encrypted_with_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'utilizador@Example.COM',
        ]);

        $this->assertSame('utilizador@example.com', $user->email);
        $this->assertSame(
            ContactHasher::emailHash('utilizador@example.com'),
            $user->email_hash,
        );

        $raw = DB::table('users')->where('id', $user->id)->first();
        $this->assertNotSame('utilizador@example.com', $raw->email);
    }

    public function test_user_can_login_with_encrypted_email(): void
    {
        $user = User::factory()->create([
            'email' => 'login@test.local',
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'email' => 'login@test.local',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_phone_is_encrypted_with_hash(): void
    {
        $user = User::factory()->create([
            'phone' => '(11) 98765-4321',
        ]);

        $this->assertSame('11987654321', $user->phone);
        $this->assertSame(
            ContactHasher::phoneHash('11987654321'),
            $user->phone_hash,
        );

        $raw = DB::table('users')->where('id', $user->id)->first();
        $this->assertNotSame('11987654321', $raw->phone);
    }

    public function test_registration_rejects_duplicate_email_via_hash(): void
    {
        User::factory()->create(['email' => 'dup@test.local']);

        $this->post(route('register'), [
            'name' => 'Outro',
            'email' => 'dup@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => '1',
        ])->assertSessionHasErrors('email');
    }
}
