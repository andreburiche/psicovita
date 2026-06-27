<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_ia_index(): void
    {
        $this->get(route('ai.index'))->assertRedirect(route('login'));
    }

    public function test_professional_can_view_ia_assistant(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ai.index'))
            ->assertOk()
            ->assertSee('IA Assistente', false);
    }

    public function test_professional_can_submit_generate_text(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('ai.generate-text'), [
                'session_text' => 'Resumo fictício da sessão para teste.',
                'approach' => 'tcc',
                'output_type' => 'resumo_clinico',
            ])
            ->assertRedirect(route('ai.index').'#ultimo-resultado')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('ai_requests', [
            'user_id' => $user->id,
            'type' => 'texto_abordagem',
            'status' => 'completed',
        ]);
    }

    public function test_generate_text_can_return_to_clinical_record_create(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('ai.generate-text'), [
                'session_text' => 'Resumo fictício da sessão para teste.',
                'approach' => 'tcc',
                'output_type' => 'resumo_clinico',
                'return_to' => 'clinical-records.create',
            ])
            ->assertRedirect(route('clinical-records.create').'#conteudo-prontuario')
            ->assertSessionHas('status')
            ->assertSessionHas('ai_content');

        $this->assertDatabaseHas('ai_requests', [
            'user_id' => $user->id,
            'type' => 'texto_abordagem',
            'status' => 'completed',
        ]);
    }

    public function test_clinical_record_create_shows_optional_ai_panel_for_premium_plan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('clinical-records.create'))
            ->assertOk()
            ->assertSee(__('Apoio da IA (opcional)'), false)
            ->assertSee(__('Transcrever áudio'), false)
            ->assertSee(__('Gerar texto'), false);
    }

    public function test_clinical_record_create_hides_ai_panel_on_essencial_plan(): void
    {
        $user = User::factory()->create();
        $plan = \App\Models\SubscriptionPlan::query()
            ->where('slug', \App\Enums\SubscriptionPlanSlug::Essencial)
            ->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => \App\Enums\SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->get(route('clinical-records.create'))
            ->assertOk()
            ->assertDontSee(__('Apoio da IA (opcional)'), false)
            ->assertDontSee(__('Transcrever áudio'), false);
    }

    public function test_essencial_plan_sidebar_hides_ia_assistant_link(): void
    {
        $user = User::factory()->create();
        $plan = \App\Models\SubscriptionPlan::query()
            ->where('slug', \App\Enums\SubscriptionPlanSlug::Essencial)
            ->firstOrFail();

        $user->professionalSubscription->update([
            'subscription_plan_id' => $plan->id,
            'status' => \App\Enums\SubscriptionStatus::Active,
            'trial_ends_at' => null,
            'ends_at' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('IA Assistente'), false);
    }

    public function test_professional_can_view_ai_request_detail_page(): void
    {
        $user = User::factory()->create();
        \App\Models\Patient::factory()->create([
            'professional_id' => $user->id,
            'name' => 'Paciente IA Detalhe',
        ]);

        $this->actingAs($user)
            ->post(route('ai.generate-text'), [
                'session_text' => 'Resumo fictício da sessão para teste de detalhe.',
                'approach' => 'tcc',
                'output_type' => 'resumo_clinico',
            ])
            ->assertRedirect();

        $aiRequest = \App\Models\AiRequest::query()->where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($aiRequest);

        $this->get(route('ai.show', $aiRequest))
            ->assertOk()
            ->assertSee(__('Resultado da análise'), false)
            ->assertSee(__('Conteúdo gerado'), false)
            ->assertSee(__('Revisão profissional obrigatória'), false)
            ->assertSee(__('Salvar no prontuário'), false);
    }

    public function test_professional_can_submit_transcribe_with_consent(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('sessao.mp3', 120, 'audio/mpeg');

        $this->actingAs($user)
            ->post(route('ai.transcribe'), [
                'audio' => $file,
                'session_type' => 'retorno',
                'lgpd_audio_consent' => '1',
            ])
            ->assertRedirect(route('ai.index').'#ultimo-resultado');

        $this->assertDatabaseHas('ai_requests', [
            'user_id' => $user->id,
            'type' => 'transcricao',
            'status' => 'completed',
        ]);

        $aiRequest = $user->aiRequests()->first();
        $this->assertNotNull($aiRequest->lgpd_consent_at);
    }
}
