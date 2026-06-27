<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', Rule::in(['android', 'ios'])],
        ]);

        DeviceToken::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $validated['token'],
            ],
            ['platform' => $validated['platform']],
        );

        return response()->json(['message' => __('Dispositivo registado para notificações.')]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['message' => __('Token removido.')]);
    }
}
