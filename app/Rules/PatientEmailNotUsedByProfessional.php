<?php

namespace App\Rules;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\ContactHasher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class PatientEmailNotUsedByProfessional implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = Str::lower(trim((string) $value));
        if ($email === '') {
            return;
        }

        $exists = User::query()
            ->where('email_hash', ContactHasher::emailHash($email))
            ->whereIn('role', [UserRole::Professional, UserRole::Admin])
            ->exists();

        if ($exists) {
            $fail(__('Este e-mail já pertence a uma conta profissional. Use outro e-mail na ficha ou peça para alterar o e-mail da conta profissional antes de criar o portal do paciente.'));
        }
    }
}
