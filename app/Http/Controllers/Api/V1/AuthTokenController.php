<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Api\ApiAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:64'],
        ]);

        $credentials['email'] = Str::lower(trim($credentials['email']));

        /** @var User|null $user */
        $user = User::findByEmail($credentials['email']);

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('Credenciais inválidas.')],
            ]);
        }

        if (! $user->isProfessional()) {
            return response()->json([
                'message' => __('Apenas profissionais podem usar a API com token.'),
            ], 403);
        }

        $device = $credentials['device_name'] ?? 'api';
        $abilities = ApiAbilities::normalizeForToken($credentials['abilities'] ?? null);
        $token = $user->createToken($device, $abilities)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'abilities' => $abilities,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Token revogado.']);
    }
}
