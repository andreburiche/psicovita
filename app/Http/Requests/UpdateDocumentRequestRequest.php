<?php

namespace App\Http\Requests;

use App\Enums\DocumentRequestStatus;
use App\Enums\InstitutionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $documentRequest = $this->route('document_request');

        return $this->user()?->can('update', $documentRequest) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('document_requests.max_upload_kb', 10240);
        $mimes = config('document_requests.allowed_mimes', ['pdf', 'jpg', 'jpeg', 'png', 'webp']);

        return [
            'institution_name' => ['required', 'string', 'max:255'],
            'institution_type' => ['required', Rule::enum(InstitutionType::class)],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'requested_documents' => ['required', 'array', 'min:1'],
            'requested_documents.*' => ['string', 'max:255'],
            'requested_documents_other' => ['nullable', 'string', 'max:255'],
            'request_reason' => ['required', 'string', 'max:5000'],
            'authorization_attached' => ['nullable', 'boolean'],
            'request_date' => ['required', 'date'],
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:request_date'],
            'status' => ['required', Rule::enum(DocumentRequestStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'patient_consent_confirmed' => ['sometimes', 'boolean'],
            'authorization_file' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
            'institution_file' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
            'complementary_file' => ['nullable', 'file', 'max:'.$maxKb, 'mimes:'.implode(',', $mimes)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'authorization_attached' => $this->boolean('authorization_attached'),
            'patient_consent_confirmed' => $this->boolean('patient_consent_confirmed'),
        ]);
    }
}
