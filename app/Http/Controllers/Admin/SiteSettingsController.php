<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $this->ensureAdmin($request);

        $social = SiteSetting::getValue('social_links', []);
        $whatsapp = SiteSetting::getValue('whatsapp', []);

        return view('admin.site.settings', [
            'social' => $social,
            'whatsapp' => $whatsapp,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'instagram' => ['nullable', 'url', 'max:500'],
            'linkedin' => ['nullable', 'url', 'max:500'],
            'facebook' => ['nullable', 'url', 'max:500'],
            'youtube' => ['nullable', 'url', 'max:500'],
            'whatsapp_phone' => ['nullable', 'string', 'max:30'],
            'whatsapp_message' => ['nullable', 'string', 'max:500'],
            'whatsapp_enabled' => ['sometimes', 'boolean'],
        ]);

        SiteSetting::put('social_links', [
            'instagram' => $validated['instagram'] ?? '',
            'linkedin' => $validated['linkedin'] ?? '',
            'facebook' => $validated['facebook'] ?? '',
            'youtube' => $validated['youtube'] ?? '',
        ]);

        SiteSetting::put('whatsapp', [
            'phone' => preg_replace('/\D+/', '', (string) ($validated['whatsapp_phone'] ?? '')),
            'message' => $validated['whatsapp_message'] ?? '',
            'enabled' => $request->boolean('whatsapp_enabled'),
        ]);

        return redirect()
            ->route('admin.site.settings')
            ->with('status', __('Configurações do site atualizadas.'));
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
