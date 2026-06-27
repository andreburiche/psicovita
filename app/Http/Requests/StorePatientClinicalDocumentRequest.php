<?php

namespace App\Http\Requests;

use App\Enums\PatientClinicalDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientClinicalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = PatientClinicalDocumentType::tryFrom((string) $this->input('type'));

        $rules = [
            'type' => ['required', Rule::enum(PatientClinicalDocumentType::class)],
            'issued_at' => ['required', 'date'],
            'place' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:8000'],
        ];

        if ($type === PatientClinicalDocumentType::Atestado) {
            $rules['atestado_kind'] = ['required', Rule::in(['comparecimento', 'afastamento'])];
            $rules['session_date'] = ['nullable', 'date'];
            $rules['days'] = ['nullable', 'integer', 'min:1', 'max:365'];
            $rules['start_date'] = ['nullable', 'date'];
            $rules['end_date'] = ['nullable', 'date', 'after_or_equal:start_date'];
            $rules['cid'] = ['nullable', 'string', 'max:20'];
        }

        if ($type === PatientClinicalDocumentType::Declaracao) {
            $rules['subject'] = ['nullable', 'string', 'max:200'];
        }

        if ($type === PatientClinicalDocumentType::Receita) {
            $rules['medications'] = ['required', 'string', 'max:6000'];
            $rules['observations'] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => __('A data final deve ser igual ou posterior à data inicial.'),
        ];
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        $type = PatientClinicalDocumentType::from((string) $this->input('type'));

        $payload = [
            'place' => $this->input('place'),
            'body' => $this->input('body'),
        ];

        return match ($type) {
            PatientClinicalDocumentType::Atestado => array_merge($payload, [
                'kind' => $this->input('atestado_kind'),
                'session_date' => $this->input('session_date'),
                'days' => $this->input('days'),
                'start_date' => $this->input('start_date'),
                'end_date' => $this->input('end_date'),
                'cid' => $this->input('cid'),
            ]),
            PatientClinicalDocumentType::Declaracao => array_merge($payload, [
                'subject' => $this->input('subject'),
            ]),
            PatientClinicalDocumentType::Receita => array_merge($payload, [
                'medications' => $this->input('medications'),
                'observations' => $this->input('observations'),
            ]),
        };
    }
}
