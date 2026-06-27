<?php

namespace App\Http\Controllers;

use App\Services\AsaasOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfessionalAsaasWalletController extends Controller
{
    public function __construct(
        private readonly AsaasOnboardingService $onboarding,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isProfessional()) {
            abort(403);
        }

        if ($user->isClinicTeamMember()) {
            abort(403, __('A carteira Asaas é gerida pelo titular da clínica.'));
        }

        if (! config('asaas.split_enabled')) {
            abort(404);
        }

        $connectEnabled = (bool) config('asaas.connect_enabled');

        $validated = $request->validate([
            'cpf_cnpj' => [$connectEnabled ? 'required' : 'nullable', 'string', 'max:18'],
            'postal_code' => [$connectEnabled ? 'required' : 'nullable', 'string', 'max:12'],
            'address' => [$connectEnabled ? 'required' : 'nullable', 'string', 'max:255'],
            'address_number' => [$connectEnabled ? 'required' : 'nullable', 'string', 'max:32'],
            'province' => [$connectEnabled ? 'required' : 'nullable', 'string', 'max:128'],
        ]);

        try {
            $walletId = $this->onboarding->provisionProfessionalWallet(
                $user,
                $validated['cpf_cnpj'] ?? null,
                [
                    'postal_code' => $validated['postal_code'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'address_number' => $validated['address_number'] ?? null,
                    'province' => $validated['province'] ?? null,
                ],
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('profile.edit')
                ->withErrors(['asaas_wallet' => $e->getMessage()]);
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', __('Carteira criada com sucesso: :id', ['id' => $walletId]));
    }
}
