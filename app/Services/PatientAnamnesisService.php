<?php

namespace App\Services;

use App\Models\AnamnesisForm;
use App\Models\Patient;
use App\Models\PatientAnamnesis;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * Validação e gravação da anamnese preenchida na ficha do paciente.
 */
final class PatientAnamnesisService
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function validationRules(AnamnesisForm $form, User $professional): array
    {
        $rules = [
            'anamnesis_form_id' => [
                'required',
                'integer',
                Rule::exists('anamnesis_forms', 'id')
                    ->where('professional_id', $professional->id),
            ],
            'answers' => ['sometimes', 'array'],
        ];

        foreach ($form->questions as $q) {
            $path = 'answers.'.$q->field_key;
            $fieldRules = DynamicFieldRules::expand(
                is_array($q->validation_rules) ? $q->validation_rules : [],
                (bool) $q->required,
                $path
            );
            $fieldRules[] = 'max:20000';
            $rules[$path] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $answersInput
     * @return array<string, string>
     */
    public function normalizeAnswers(AnamnesisForm $form, array $answersInput): array
    {
        $out = [];
        foreach ($form->questions as $q) {
            $key = $q->field_key;
            $v = $answersInput[$key] ?? null;
            if (is_string($v)) {
                $out[$key] = trim($v);
            } elseif (is_scalar($v)) {
                $out[$key] = trim((string) $v);
            } else {
                $out[$key] = '';
            }
        }

        return $out;
    }

    public function upsert(Patient $patient, AnamnesisForm $form, User $professional, array $answers): PatientAnamnesis
    {
        return PatientAnamnesis::query()->updateOrCreate(
            [
                'patient_id' => $patient->id,
                'anamnesis_form_id' => $form->id,
            ],
            [
                'professional_id' => $professional->id,
                'answers' => $answers,
            ]
        );
    }
}
