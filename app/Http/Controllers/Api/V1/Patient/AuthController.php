<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Enums\UserProfessionalFunction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\UniqueUserEmail;
use App\Services\Auth\SocialAuthService;
use App\Services\Auth\UserRegistrationService;
use App\Support\Api\PatientApiAbilities;
use App\Support\Api\PatientUserPayload;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserRegistrationService $registration,
        private readonly SocialAuthService $socialAuth,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $email = Str::lower(trim($credentials['email']));
        $user = User::findByEmail($email);

        if ($user === null || ! $user->hasPassword() || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('Credenciais inválidas.')],
            ]);
        }

        if (! $user->usesPatientPortalExperience()) {
            return response()->json([
                'message' => __('Esta conta não tem acesso ao portal do paciente. Utilize o portal web profissional.'),
                'code' => 'not_patient_portal',
            ], 403);
        }

        $token = $user->createToken(
            $credentials['device_name'] ?? 'flutter_app',
            PatientApiAbilities::normalizeForToken(null),
        )->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => PatientUserPayload::make($user),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email', '')))]);
        $roleContext = $this->registration->resolveRoleForEmail($request->email);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', new UniqueUserEmail],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms_accepted' => ['accepted'],
            'professional_function' => [
                $roleContext['becomes_patient'] ? 'nullable' : 'required',
                'string',
                Rule::in(array_column(UserProfessionalFunction::cases(), 'value')),
            ],
        ], [
            'terms_accepted.accepted' => __('Você deve aceitar os Termos de Uso e a Política de Privacidade.'),
        ]);

        if (! $roleContext['becomes_patient'] && $roleContext['role']->value !== 'professional') {
            // registo API paciente: só paciente ou profissional mal registado vira portal
        }

        $user = $this->registration->createUser([
            'name' => $validated['name'],
            'email' => $request->email,
            'password' => $validated['password'],
            'role' => $roleContext['role'],
            'professional_id' => $roleContext['professional_id'],
            'professional_function' => $roleContext['becomes_patient']
                ? null
                : UserProfessionalFunction::from($request->string('professional_function')->toString()),
        ]);

        event(new Registered($user));

        if (! $user->usesPatientPortalExperience()) {
            return response()->json([
                'message' => __('Conta criada. Aceda pelo portal web profissional.'),
                'user' => PatientUserPayload::make($user),
            ], 201);
        }

        $token = $user->createToken('flutter_app', PatientApiAbilities::normalizeForToken(null))->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => PatientUserPayload::make($user),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => __('Sessão encerrada.')]);
    }

    public function socialGoogle(Request $request): JsonResponse
    {
        return $this->handleSocial($request, 'google');
    }

    public function socialFacebook(Request $request): JsonResponse
    {
        return $this->handleSocial($request, 'facebook');
    }

    private function handleSocial(Request $request, string $provider): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! \App\Support\SocialAuthProvider::isConfigured($provider)) {
            return response()->json(['message' => __('Provedor não configurado.')], 503);
        }

        $socialUser = Socialite::driver($provider)->stateless()->userFromToken($validated['token']);
        $email = Str::lower(trim((string) $socialUser->getEmail()));

        if ($email === '') {
            return response()->json([
                'message' => __('Não foi possível obter o e-mail do provedor.'),
            ], 422);
        }

        $user = $this->socialAuth->resolveExistingUser($provider, $socialUser);

        if ($user !== null) {
            if (! $user->usesPatientPortalExperience()) {
                return response()->json([
                    'message' => __('Conta sem acesso ao app do paciente.'),
                    'code' => 'not_patient_portal',
                ], 403);
            }

            $token = $user->createToken(
                $validated['device_name'] ?? 'flutter_app',
                PatientApiAbilities::normalizeForToken(null),
            )->plainTextToken;

            return response()->json([
                'token' => $token,
                'token_type' => 'Bearer',
                'requires_completion' => false,
                'user' => PatientUserPayload::make($user),
            ]);
        }

        return response()->json([
            'requires_completion' => true,
            'registration' => $this->socialAuth->registrationPayload($provider, $socialUser),
        ]);
    }

    public function completeSocialRegistration(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'provider' => ['required', 'string', Rule::in(\App\Support\SocialAuthProvider::all())],
            'provider_id' => ['required', 'string'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string'],
            'terms_accepted' => ['accepted'],
            'professional_function' => ['nullable', 'string'],
            'becomes_patient' => ['nullable', 'boolean'],
            'role' => ['required', 'string'],
            'professional_id' => ['nullable', 'integer'],
        ]);

        validator(['email' => $payload['email']], ['email' => [new UniqueUserEmail]])->validate();

        $becomesPatient = (bool) ($payload['becomes_patient'] ?? false);

        $user = $this->registration->createUser([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'role' => \App\Enums\UserRole::from($payload['role']),
            'professional_id' => $payload['professional_id'] ?? null,
            'professional_function' => $becomesPatient || empty($payload['professional_function'])
                ? null
                : UserProfessionalFunction::from($payload['professional_function']),
            'email_verified' => true,
        ]);

        $this->registration->linkSocialAccount(
            $user,
            $payload['provider'],
            $payload['provider_id'],
            $payload['avatar'] ?? null,
        );

        event(new Registered($user));

        if (! $user->usesPatientPortalExperience()) {
            return response()->json(['message' => __('Conta criada para área profissional.')], 201);
        }

        $token = $user->createToken('flutter_app', PatientApiAbilities::normalizeForToken(null))->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => PatientUserPayload::make($user),
        ], 201);
    }
}
