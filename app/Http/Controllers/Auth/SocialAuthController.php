<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\SocialAuthService;
use App\Support\SocialAuthProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuth,
    ) {}

    public function redirect(string $provider): SymfonyRedirectResponse|RedirectResponse
    {
        $this->guardProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->guardProvider($provider);

        $socialUser = Socialite::driver($provider)->user();
        $email = trim((string) $socialUser->getEmail());

        if ($email === '') {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Não foi possível obter o e-mail do :provider. Utilize o login com e-mail e palavra-passe ou autorize o acesso ao e-mail.', [
                    'provider' => ucfirst($provider),
                ])]);
        }

        $user = $this->socialAuth->resolveExistingUser($provider, $socialUser);

        if ($user !== null) {
            Auth::login($user, remember: true);

            return redirect()->intended(route($user->defaultAppRouteName()));
        }

        $request->session()->put('social_registration', $this->socialAuth->registrationPayload($provider, $socialUser));

        return redirect()->route('social.register.complete');
    }

    private function guardProvider(string $provider): void
    {
        abort_unless(SocialAuthProvider::isValid($provider), 404);

        if (! SocialAuthProvider::isConfigured($provider)) {
            abort(503, __('Login com :provider não está configurado.', ['provider' => ucfirst($provider)]));
        }
    }
}
