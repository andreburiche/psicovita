<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Support\Api\PatientUserPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => PatientUserPayload::make($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $user = $request->user();
        $user->fill($validated);
        $user->save();

        return response()->json([
            'data' => PatientUserPayload::make($user->fresh()),
        ]);
    }
}
