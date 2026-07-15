<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethodPreference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfessionalPaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->isProfessional()
            && $user->isClinicOwner();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method_preference' => ['required', Rule::enum(PaymentMethodPreference::class)],
            'pix_manual_link' => ['nullable', 'string', 'max:2000'],
            'pix_qrcode' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
            'remove_pix_qrcode' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $preference = PaymentMethodPreference::tryFrom((string) $this->input('payment_method_preference'));
            if ($preference !== PaymentMethodPreference::Manual && $preference !== PaymentMethodPreference::Auto) {
                return;
            }

            $hasLink = filled(trim((string) $this->input('pix_manual_link', '')));
            $hasNewQr = $this->hasFile('pix_qrcode');
            $keepsQr = filled($this->user()?->pix_qrcode_path) && ! $this->boolean('remove_pix_qrcode');

            if ($preference === PaymentMethodPreference::Manual && ! $hasLink && ! $hasNewQr && ! $keepsQr) {
                $validator->errors()->add(
                    'pix_manual_link',
                    __('Para o modo PIX manual, indique a chave/link ou envie a imagem do QR Code.')
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_method_preference' => __('preferência de recebimento'),
            'pix_manual_link' => __('link ou chave PIX'),
            'pix_qrcode' => __('imagem do QR Code PIX'),
        ];
    }
}
