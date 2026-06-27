<?php

namespace App\Http\Requests;

use App\Enums\TherapeuticGoalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientTherapeuticGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(TherapeuticGoalStatus::class)],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'target_date' => ['nullable', 'date'],
        ];
    }
}
