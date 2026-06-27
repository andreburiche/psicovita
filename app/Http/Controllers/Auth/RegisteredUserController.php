<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserProfessionalFunction;
use App\Http\Controllers\Controller;
use App\Rules\UniqueUserEmail;
use App\Services\Auth\UserRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly UserRegistrationService $registration,
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email', ''))),
        ]);

        $roleContext = $this->registration->resolveRoleForEmail($request->email);
        $becomesPatient = $roleContext['becomes_patient'];

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', new UniqueUserEmail],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
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

        $user = $this->registration->createUser([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $roleContext['role'],
            'professional_id' => $roleContext['professional_id'],
            'professional_function' => $becomesPatient
                ? null
                : UserProfessionalFunction::from($request->string('professional_function')->toString()),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route($user->defaultAppRouteName(), absolute: false));
    }
}
