<?php

namespace Tests\Feature;

use App\Support\ContrastChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContrastCheckerTest extends TestCase
{
    use RefreshDatabase;

    public function test_black_on_white_passes_aa(): void
    {
        $result = ContrastChecker::evaluate('#000000', '#ffffff');

        $this->assertTrue($result['passes_aa_normal']);
        $this->assertGreaterThan(4.5, $result['ratio']);
    }

    public function test_admin_can_view_accessibility_report(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.lgpd.accessibility'))
            ->assertOk()
            ->assertSee(__('Relatório de acessibilidade'), false);
    }
}
