<?php

namespace App\Http\Requests;

use App\Rules\UniqueUserEmail;
use App\Support\AvatarStyleOptions;
use App\Support\UiAccentOptions;
use App\Enums\UserProfessionalFunction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                new UniqueUserEmail($this->user()->id),
            ],
            'crp_number' => ['nullable', 'string', 'max:32'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'avatar_shape' => ['nullable', 'string', Rule::in(AvatarStyleOptions::SHAPES)],
            'avatar_ring' => ['nullable', 'string', Rule::in(AvatarStyleOptions::RINGS)],
            'avatar_filter' => ['nullable', 'string', Rule::in(AvatarStyleOptions::FILTERS)],
            'ui_accent' => ['nullable', 'string', Rule::in(UiAccentOptions::KEYS)],
        ];

        if ($this->user()->isProfessional() || $this->user()->isAdmin()) {
            $rules['professional_function'] = ['required', 'string', Rule::in(array_column(UserProfessionalFunction::cases(), 'value'))];
            $rules['professional_bio'] = ['nullable', 'string', 'max:5000'];
            $rules['phone'] = ['nullable', 'string', 'max:20'];
        }

        if ($this->user()->isClinicOwner()) {
            $rules['institution_logo'] = ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,svg', 'max:8192'];
            $rules['remove_institution_logo'] = ['sometimes', 'boolean'];
        }

        if ($this->user()->isProfessional() && config('asaas.split_enabled')) {
            $rules['asaas_wallet_id'] = ['nullable', 'string', 'max:64', 'regex:/^wal_[A-Za-z0-9]+$/'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'avatar' => __('foto de perfil'),
            'institution_logo' => __('logo da instituição'),
            'remove_avatar' => __('remover foto de perfil'),
            'remove_institution_logo' => __('remover logo da instituição'),
            'professional_function' => __('função profissional'),
            'professional_bio' => __('biografia profissional'),
            'phone' => __('telefone'),
            'crp_number' => __('número de registo profissional'),
            'ui_accent' => __('cor de destaque'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedAvatarStyle(): array
    {
        return AvatarStyleOptions::resolve([
            'shape' => $this->input('avatar_shape'),
            'ring' => $this->input('avatar_ring'),
            'filter' => $this->input('avatar_filter'),
        ]);
    }
}
