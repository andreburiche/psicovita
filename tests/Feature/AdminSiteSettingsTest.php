<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\LandingPartner;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_site_settings(): void
    {
        $professional = User::factory()->create(['role' => UserRole::Professional]);

        $this->actingAs($professional)
            ->get(route('admin.site.settings'))
            ->assertForbidden();
    }

    public function test_admin_can_update_social_links_and_whatsapp(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patch(route('admin.site.settings.update'), [
                'instagram' => 'https://instagram.com/psiconecta',
                'linkedin' => 'https://linkedin.com/company/psiconecta',
                'facebook' => '',
                'youtube' => '',
                'whatsapp_phone' => '11999990000',
                'whatsapp_message' => 'Olá!',
                'whatsapp_enabled' => '1',
            ])
            ->assertRedirect(route('admin.site.settings'));

        $social = SiteSetting::getValue('social_links');
        $this->assertSame('https://instagram.com/psiconecta', $social['instagram']);

        $whatsapp = SiteSetting::getValue('whatsapp');
        $this->assertSame('11999990000', $whatsapp['phone']);
        $this->assertTrue($whatsapp['enabled']);
    }

    public function test_landing_shows_dynamic_partners_and_whatsapp(): void
    {
        SiteSetting::put('whatsapp', [
            'phone' => '11988887777',
            'message' => 'Teste',
            'enabled' => true,
        ]);

        LandingPartner::query()->create([
            'name' => 'Clínica Teste',
            'url' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-test="landing-partners"', false)
            ->assertSee('Clínica Teste', false)
            ->assertSee('wa.me', false)
            ->assertSee(__('Voltar ao topo'), false);
    }

    public function test_admin_can_manage_partners(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.site.partners.store'), [
                'name' => 'Novo Parceiro',
                'url' => 'https://example.com',
                'sort_order' => 10,
                'logo' => UploadedFile::fake()->image('logo.png', 240, 80),
            ])
            ->assertRedirect(route('admin.site.partners'));

        $partner = LandingPartner::query()->where('name', 'Novo Parceiro')->firstOrFail();
        $this->assertNotNull($partner->logo_path);
        Storage::disk('public')->assertExists($partner->logo_path);

        $this->actingAs($admin)
            ->patch(route('admin.site.partners.update', $partner), [
                'name' => 'Parceiro Atualizado',
                'url' => 'https://example.com',
                'sort_order' => 5,
                'is_active' => '1',
                'remove_logo' => '1',
            ])
            ->assertRedirect(route('admin.site.partners'));

        $partner->refresh();
        $this->assertNull($partner->logo_path);
        $this->assertSame('Parceiro Atualizado', $partner->name);
    }

    public function test_landing_shows_partner_logo_when_configured(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('clinica.png', 200, 60)->store('landing-partners/1', 'public');

        LandingPartner::query()->create([
            'name' => 'Clínica Logo',
            'url' => null,
            'logo_path' => $path,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('/storage/landing-partners/', false)
            ->assertSee('alt="Clínica Logo"', false);
    }
}
