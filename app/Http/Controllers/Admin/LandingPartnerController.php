<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingPartner;
use App\Services\LandingPartnerLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPartnerController extends Controller
{
    public function __construct(
        private readonly LandingPartnerLogoService $logos,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $partners = LandingPartner::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.site.partners', compact('partners'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99'],
            'logo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:2048'],
        ]);

        $partner = LandingPartner::query()->create([
            'name' => $validated['name'],
            'url' => $validated['url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? ((int) LandingPartner::query()->max('sort_order')) + 1,
            'is_active' => true,
        ]);

        if ($request->hasFile('logo')) {
            $this->logos->store($partner, $request->file('logo'));
            $partner->save();
        }

        return redirect()
            ->route('admin.site.partners')
            ->with('status', __('Parceiro adicionado.'));
    }

    public function update(Request $request, LandingPartner $partner): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:99'],
            'is_active' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $partner->fill([
            'name' => $validated['name'],
            'url' => $validated['url'] ?? null,
            'sort_order' => (int) $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->boolean('remove_logo')) {
            $this->logos->remove($partner);
        } elseif ($request->hasFile('logo')) {
            $this->logos->store($partner, $request->file('logo'));
        }

        $partner->save();

        return redirect()
            ->route('admin.site.partners')
            ->with('status', __('Parceiro atualizado.'));
    }

    public function destroy(Request $request, LandingPartner $partner): RedirectResponse
    {
        $this->ensureAdmin($request);

        $partner->delete();

        return redirect()
            ->route('admin.site.partners')
            ->with('status', __('Parceiro removido.'));
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
