<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ContactHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_is_public(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee(__('Política de Privacidade'), false)
            ->assertSee(__('Encarregado (DPO)'), false);
    }

    public function test_terms_page_is_public(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSee(__('Termos de Uso'), false);
    }

    public function test_registration_requires_terms_acceptance(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Profissional Teste',
            'email' => 'prof@teste.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('terms_accepted');
    }

    public function test_registration_succeeds_with_terms_acceptance(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Profissional Teste',
            'email' => 'prof@teste.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => '1',
            'professional_function' => \App\Enums\UserProfessionalFunction::Psychologist->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email_hash' => ContactHasher::emailHash('prof@teste.com'),
        ]);
    }

    public function test_ai_transcription_persists_lgpd_consent(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('sessao.mp3', 120, 'audio/mpeg');

        $this->actingAs($user)->post(route('ai.transcribe'), [
            'audio' => $file,
            'session_type' => 'retorno',
            'lgpd_audio_consent' => '1',
        ]);

        $this->assertDatabaseHas('ai_requests', [
            'user_id' => $user->id,
            'type' => 'transcricao',
        ]);

        $request = $user->aiRequests()->first();
        $this->assertNotNull($request->lgpd_consent_at);
        $this->assertNotNull($request->lgpd_consent_ip);
    }
}
