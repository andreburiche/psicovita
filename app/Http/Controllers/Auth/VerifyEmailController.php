<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Confirma o e-mail do utilizador indicado na ligação assinada.
     *
     * Não exige que a sessão seja a mesma conta: o utilizador pode abrir o e-mail
     * noutro dispositivo ou com outra sessão iniciada no browser.
     * A segurança fica na assinatura temporal da URL (middleware dedicado).
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = User::query()->find($request->route('id'));

        if ($user === null) {
            abort(404);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            abort(403, __('Ligação inválida ou expirada. Peça um novo e-mail de confirmação.'));
        }

        if ($user->hasVerifiedEmail()) {
            if (Auth::check() && Auth::id() === $user->id) {
                return redirect()->intended(route($user->defaultAppRouteName(), absolute: false).'?verified=1');
            }

            return redirect()
                ->route('login')
                ->with('status', __('Este e-mail já foi confirmado. Pode iniciar sessão.'));
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if (Auth::check() && Auth::id() === $user->id) {
            return redirect()->intended(route($user->defaultAppRouteName(), absolute: false).'?verified=1');
        }

        return redirect()
            ->route('login')
            ->with('status', __('E-mail confirmado com sucesso. Já pode iniciar sessão.'));
    }
}
