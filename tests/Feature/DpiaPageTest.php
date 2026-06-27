<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DpiaPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dpia_ai_page_is_public(): void
    {
        $this->get(route('legal.dpia-ai'))
            ->assertOk()
            ->assertSee(__('Relatório de Impacto à Proteção de Dados (DPIA) — Assistente de IA'), false)
            ->assertSee(__('Riscos identificados e medidas'), false);
    }

    public function test_privacy_page_links_to_dpia(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee(route('legal.dpia-ai'), false);
    }

    public function test_validation_messages_use_portuguese_locale(): void
    {
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'invalido',
            'password' => 'x',
            'password_confirmation' => 'y',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);

        $errors = session('errors');
        $this->assertStringContainsString('obrigat', (string) $errors->first('name'));
        $this->assertStringContainsString('e-mail', (string) $errors->first('email'));
    }
}
