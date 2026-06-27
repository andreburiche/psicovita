<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserProfessionalFunction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Rules\UniqueUserEmail;
use App\Services\Auth\SocialAuthService;
use App\Services\Auth\UserRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SocialRegistrationController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuth,
        private readonly UserRegistrationService $registration,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $payload = $request->session()->get('social_registration');

        if (! $this->socialAuth->isRegistrationSessionValid($payload)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Sessão de registo social expirada. Tente novamente.')]);
        }

        return view('auth.social-complete', [
            'payload' => $payload,
            'functionOptions' => UserProfessionalFunction::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->session()->get('social_registration');

        if (! $this->socialAuth->isRegistrationSessionValid($payload)) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Sessão de registo social expirada. Tente novamente.')]);
        }

        $becomesPatient = (bool) ($payload['becomes_patient'] ?? false);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'terms_accepted' => ['accepted'],
            'professional_function' => [
                $becomesPatient ? 'nullable' : 'required',
                'string',
                Rule::in(array_column(UserProfessionalFunction::cases(), 'value')),
            ],
        ], [
            'terms_accepted.accepted' => __('Você deve aceitar os Termos de Uso e a Política de Privacidade.'),
            'professional_function.required' => __('Selecione a sua função profissional.'),
        ]);

        $email = (string) $payload['email'];

        validator(
            ['email' => $email],
            ['email' => ['required', 'email', new UniqueUserEmail]],
        )->validate();

        $user = $this->registration->createUser([
            'name' => $validated['name'],
            'email' => $email,
            'role' => UserRole::from((string) $payload['role']),
            'professional_id' => $payload['professional_id'] ?? null,
            'professional_function' => $becomesPatient
                ? null
                : UserProfessionalFunction::from($request->string('professional_function')->toString()),
            'email_verified' => true,
        ]);

        $this->registration->linkSocialAccount(
            $user,
            (string) $payload['provider'],
            (string) $payload['provider_id'],
            $payload['avatar'] ?? null,
        );

        event(new Registered($user));

        $request->session()->forget('social_registration');

        Auth::login($user, remember: true);

        return redirect(route($user->defaultAppRouteName(), absolute: false));
    }
}
