<?php

namespace App\Support;

use App\Enums\ClinicalScaleType;

final class ClinicalScaleCatalog
{
    /** @return array<string, mixed> */
    public static function definition(ClinicalScaleType $type): array
    {
        return config('clinical_scales.'.$type->value, []);
    }

    /** @return list<array{key: string, text: string}> */
    public static function questions(ClinicalScaleType $type): array
    {
        return self::definition($type)['questions'] ?? [];
    }

    /** @return array<int, string> */
    public static function options(ClinicalScaleType $type): array
    {
        return self::definition($type)['options'] ?? [];
    }

    public static function maxScore(ClinicalScaleType $type): int
    {
        return (int) (self::definition($type)['max_score'] ?? 0);
    }

    /**
     * @param  array<string, int>  $responses
     * @return array{total: int, severity: string, severity_label: string, is_risk: bool}
     */
    public static function score(ClinicalScaleType $type, array $responses): array
    {
        $questions = self::questions($type);
        $total = 0;

        foreach ($questions as $question) {
            $key = $question['key'];
            $total += (int) ($responses[$key] ?? 0);
        }

        $band = self::resolveBand($type, $total);

        return [
            'total' => $total,
            'severity' => $band['severity'],
            'severity_label' => $band['label'],
            'is_risk' => (bool) ($band['risk'] ?? false),
        ];
    }

    /** @return array{severity: string, label: string, risk: bool} */
    public static function resolveBand(ClinicalScaleType $type, int $total): array
    {
        $bands = self::definition($type)['bands'] ?? [];

        foreach ($bands as $band) {
            if ($total <= (int) $band['max']) {
                return [
                    'severity' => (string) $band['severity'],
                    'label' => (string) $band['label'],
                    'risk' => (bool) ($band['risk'] ?? false),
                ];
            }
        }

        return [
            'severity' => 'unknown',
            'label' => __('Não classificado'),
            'risk' => false,
        ];
    }

    public static function severityTone(string $severity): string
    {
        return match ($severity) {
            'minimal' => 'emerald',
            'mild' => 'sky',
            'moderate' => 'amber',
            'severe' => 'rose',
            default => 'slate',
        };
    }
}
