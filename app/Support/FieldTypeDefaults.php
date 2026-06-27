<?php

namespace App\Support;

/**
 * Tipos de campo do builder de anamnese + máscaras e regras padrão (backend).
 */
final class FieldTypeDefaults
{
    public const TYPES = ['cpf', 'phone', 'email', 'cep', 'date', 'number', 'text', 'textarea'];

    /**
     * @return array{mask: string|null, validation_rules: array<int, string>, meta: array<string, mixed>}
     */
    public static function forType(string $fieldType): array
    {
        return match ($fieldType) {
            'cpf' => [
                'mask' => 'cpf',
                'validation_rules' => ['cpf'],
                'meta' => [],
            ],
            'phone' => [
                'mask' => 'phone',
                'validation_rules' => ['phone_br'],
                'meta' => [],
            ],
            'email' => [
                'mask' => null,
                'validation_rules' => ['email'],
                'meta' => [],
            ],
            'cep' => [
                'mask' => 'cep',
                'validation_rules' => ['cep_br'],
                'meta' => [],
            ],
            'date' => [
                'mask' => 'date',
                'validation_rules' => ['date_br'],
                'meta' => [],
            ],
            'number' => [
                'mask' => 'number',
                'validation_rules' => ['numeric'],
                'meta' => [],
            ],
            'text' => [
                'mask' => null,
                'validation_rules' => [],
                'meta' => [],
            ],
            'textarea' => [
                'mask' => null,
                'validation_rules' => [],
                'meta' => [],
            ],
            default => [
                'mask' => null,
                'validation_rules' => [],
                'meta' => [],
            ],
        };
    }

    /**
     * Defaults para o JavaScript do builder (Alpine).
     *
     * @return array<string, array{mask: string|null, validation_rules: array<int, string>}>
     */
    public static function jsonForBuilder(): array
    {
        $out = [];
        foreach (self::TYPES as $type) {
            $d = self::forType($type);
            $out[$type] = [
                'mask' => $d['mask'],
                'validation_rules' => $d['validation_rules'],
            ];
        }

        return $out;
    }

    /**
     * Mescla máscara / regras quando o admin escolhe o tipo e deixa campos vazios.
     *
     * @param  array<string, mixed>  $row
     */
    public static function applyDefaultsToRow(array &$row): void
    {
        $type = $row['field_type'] ?? 'text';
        $defaults = self::forType(is_string($type) ? $type : 'text');

        if (empty($row['mask'])) {
            $row['mask'] = $defaults['mask'];
        }
        if (empty($row['validation_rules']) || (is_array($row['validation_rules']) && $row['validation_rules'] === [])) {
            $row['validation_rules'] = $defaults['validation_rules'];
        }
    }
}
