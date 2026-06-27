<?php

namespace App\Http\Requests;

use App\Support\FieldTypeDefaults;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnamnesisFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isProfessional() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'questions' => ['nullable', 'array', 'max:200'],
            'questions.*.label' => ['required', 'string', 'max:500'],
            'questions.*.field_key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:64'],
            'questions.*.field_type' => ['required', Rule::in(FieldTypeDefaults::TYPES)],
            'questions.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'questions.*.required' => ['nullable', 'boolean'],
            'questions.*.mask' => ['nullable', 'string', 'max:64'],
            'questions.*.validation_rules' => ['nullable', 'array'],
            'questions.*.validation_rules.*' => ['string', 'max:64'],
            'questions.*.meta' => ['nullable', 'array'],
        ];
    }
}
