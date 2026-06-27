<?php

namespace App\Http\Requests;

use App\Enums\ClinicalScaleType;
use App\Support\ClinicalScaleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientScaleAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = ClinicalScaleType::tryFrom((string) $this->input('scale_type'));
        $rules = [
            'scale_type' => ['required', Rule::enum(ClinicalScaleType::class)],
            'assessed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'answers' => ['required', 'array'],
        ];

        if ($type !== null) {
            foreach (ClinicalScaleCatalog::questions($type) as $question) {
                $rules['answers.'.$question['key']] = ['required', 'integer', 'min:0', 'max:3'];
            }
        }

        return $rules;
    }

    /** @return array<string, int> */
    public function normalizedAnswers(): array
    {
        $type = ClinicalScaleType::from((string) $this->input('scale_type'));
        $answers = [];

        foreach (ClinicalScaleCatalog::questions($type) as $question) {
            $answers[$question['key']] = (int) $this->input('answers.'.$question['key']);
        }

        return $answers;
    }
}
