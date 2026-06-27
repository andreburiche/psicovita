<?php

namespace App\Services;

use App\Rules\Cpf;
use Closure;

/**
 * Converte chaves guardadas em validation_rules (JSON) em regras Laravel.
 */
final class DynamicFieldRules
{
    /**
     * @param  array<int, string|mixed>  $keys
     * @return array<int, mixed>
     */
    public static function expand(?array $keys, bool $required, string $attribute = 'value'): array
    {
        $keys = array_values(array_filter($keys ?? [], fn ($k) => is_string($k) && $k !== ''));

        $rules = $required
            ? ['required', 'string']
            : ['nullable', 'string'];

        foreach ($keys as $key) {
            foreach (self::ruleForKey($key, $attribute) as $r) {
                $rules[] = $r;
            }
        }

        return $rules;
    }

    /**
     * @return list<mixed>
     */
    private static function ruleForKey(string $key, string $attribute): array
    {
        return match ($key) {
            'cpf' => [new Cpf],
            'phone_br' => [
                function (string $attr, mixed $value, Closure $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (! is_string($value) || ! is_valid_br_phone_digits($value)) {
                        $fail(__('Telefone inválido.'));
                    }
                },
                function (string $attr, mixed $value, Closure $fail) {
                    if (! is_string($value) || $value === '') {
                        return;
                    }
                    $len = strlen(only_digits($value));
                    if ($len > 0 && ($len < 10 || $len > 11)) {
                        $fail(__('Telefone deve ter 10 ou 11 dígitos.'));
                    }
                },
            ],
            'email' => ['email', 'max:255'],
            'cep_br' => [
                'regex:/^\d{5}-?\d{3}$/',
                function (string $attr, mixed $value, Closure $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (! is_string($value) || ! is_valid_cep_digits($value)) {
                        $fail(__('CEP inválido.'));
                    }
                },
            ],
            'date_br' => ['regex:/^\d{2}\/\d{2}\/\d{4}$/'],
            'numeric' => ['numeric'],
            default => [],
        };
    }
}
