<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfessionalPaymentSettingsRequest;
use App\Services\PaymentSettingsService;
use App\Services\ProfessionalPixSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProfessionalPaymentSettingsController extends Controller
{
    public function __construct(
        private readonly ProfessionalPixSettingsService $pixSettings,
        private readonly PaymentSettingsService $paymentSettings,
    ) {}

    public function update(UpdateProfessionalPaymentSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $user = $this->pixSettings->update(
            $user,
            $request->safe()->only([
                'payment_method_preference',
                'pix_manual_link',
                'remove_pix_qrcode',
            ]),
            $request->file('pix_qrcode'),
        );

        $resolution = $this->paymentSettings->resolvePaymentMethodFor($user);

        $payload = [
            'status' => __('Recebimento actualizado.'),
            'preference' => $user->payment_method_preference?->value,
            'pix_manual_link' => $user->pix_manual_link,
            'pix_qrcode_url' => $user->pixQrcodeUrl(),
            'badge' => $resolution->statusBadgeLabel(),
            'mode' => $resolution->mode,
        ];

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($payload);
        }

        return back()->with('status', $payload['status']);
    }
}
