<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendDocumentRequestEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $documentRequest = $this->route('document_request');

        return $this->user()?->can('sendEmail', $documentRequest) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'to' => ['required', 'email', 'max:255'],
            'cc' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.required' => __('Informe o e-mail da instituição destinatária.'),
            'to.email' => __('O e-mail do destinatário é inválido.'),
        ];
    }
}
