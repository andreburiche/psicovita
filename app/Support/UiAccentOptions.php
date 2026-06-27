<?php

namespace App\Support;

final class UiAccentOptions
{
    public const DEFAULT = 'violet';

    /** @var list<string> */
    public const KEYS = ['violet', 'indigo', 'emerald', 'sky', 'rose', 'amber', 'teal', 'slate'];

    /**
     * @return array<string, array{label: string, swatch: string, light: array{from: string, via: string, to: string}, dark: array{from: string, via: string, to: string}}>
     */
    public static function presets(): array
    {
        return [
            'violet' => [
                'label' => 'Violeta',
                'swatch' => '#7c3aed',
                'light' => ['from' => 'rgb(226 232 240 / 0.9)', 'via' => 'rgb(245 243 255 / 0.55)', 'to' => 'rgb(224 231 255 / 0.65)'],
                'dark' => ['from' => 'rgb(2 6 23)', 'via' => 'rgb(15 23 42)', 'to' => 'rgb(46 16 101 / 0.35)'],
            ],
            'indigo' => [
                'label' => 'Índigo',
                'swatch' => '#4f46e5',
                'light' => ['from' => 'rgb(226 232 240 / 0.9)', 'via' => 'rgb(238 242 255 / 0.55)', 'to' => 'rgb(199 210 254 / 0.65)'],
                'dark' => ['from' => 'rgb(2 6 23)', 'via' => 'rgb(15 23 42)', 'to' => 'rgb(30 27 75 / 0.45)'],
            ],
            'emerald' => [
                'label' => 'Esmeralda',
                'swatch' => '#059669',
                'light' => ['from' => 'rgb(226 232 240 / 0.9)', 'via' => 'rgb(236 253 245 / 0.55)', 'to' => 'rgb(167 243 208 / 0.45)'],
                'dark' => ['from' => 'rgb(2 6 23)', 'via' => 'rgb(15 23 42)', 'to' => 'rgb(6 78 59 / 0.35)'],
            ],
            'sky' => [
                'label' => 'Céu',
                'swatch' => '#0284c7',
                'light' => ['from' => 'rgb(224 242 254 / 0.95)', 'via' => 'rgb(255 255 255 / 0.9)', 'to' => 'rgb(186 230 253 / 0.55)'],
                'dark' => ['from' => 'rgb(2 6 23)', 'via' => 'rgb(15 23 42)', 'to' => 'rgb(12 74 110 / 0.35)'],
            ],
            'rose' => [
                'label' => 'Rosa',
                'swatch' => '#e11d48',
                'light' => ['from' => 'rgb(226 232 240 / 0.9)', 'via' => 'rgb(255 241 242 / 0.55)', 'to' => 'rgb(254 205 211 / 0.55)'],
                'dark' => ['from' => 'rgb(2 6 23)', 'via' => 'rgb(15 23 42)', 'to' => 'rgb(136 19 55 / 0.35)'],
            ],
            'amber' => [
                'label' => 'Âmbar',
                'swatch' => '#d97706',
                'light' => ['from' => 'rgb(226 232 240 / 0.9)', 'via' => 'rgb(255 251 235 / 0.55)', 'to' => 'rgb(253 230 138 / 0.45)'],
                'dark' => ['from' => 'rgb(2 6 23)', 'via' => 'rgb(15 23 42)', 'to' => 'rgb(120 53 15 / 0.35)'],
            ],
            'teal' => [
                'label' => 'Teal',
                'swatch' => '#0d9488',
                'light' => ['from' => 'rgb(226 232 240 / 0.9)', 'via' => 'rgb(240 253 250 / 0.55)', 'to' => 'rgb(153 246 228 / 0.45)'],
                'dark' => ['from' => 'rgb(2 6 23)', 'via' => 'rgb(15 23 42)', 'to' => 'rgb(19 78 74 / 0.35)'],
            ],
            'slate' => [
                'label' => 'Neutro',
                'swatch' => '#64748b',
                'light' => ['from' => 'rgb(241 245 249 / 0.95)', 'via' => 'rgb(248 250 252 / 0.9)', 'to' => 'rgb(226 232 240 / 0.75)'],
                'dark' => ['from' => 'rgb(2 6 23)', 'via' => 'rgb(15 23 42)', 'to' => 'rgb(15 23 42)'],
            ],
        ];
    }

    public static function resolve(?string $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, self::KEYS, true) ? $value : self::DEFAULT;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::presets() as $key => $preset) {
            $labels[$key] = __($preset['label']);
        }

        return $labels;
    }

    public static function cssBlock(): string
    {
        $lines = [
            'body.psi-app-background {',
            '  background-image: linear-gradient(to bottom right, var(--psi-bg-from), var(--psi-bg-via), var(--psi-bg-to));',
            '  background-attachment: fixed;',
            '}',
        ];

        foreach (self::presets() as $key => $preset) {
            $lines[] = "html[data-ui-accent=\"{$key}\"] {";
            $lines[] = "  --psi-bg-from: {$preset['light']['from']};";
            $lines[] = "  --psi-bg-via: {$preset['light']['via']};";
            $lines[] = "  --psi-bg-to: {$preset['light']['to']};";
            $lines[] = '}';
            $lines[] = "html.dark[data-ui-accent=\"{$key}\"] {";
            $lines[] = "  --psi-bg-from: {$preset['dark']['from']};";
            $lines[] = "  --psi-bg-via: {$preset['dark']['via']};";
            $lines[] = "  --psi-bg-to: {$preset['dark']['to']};";
            $lines[] = '}';
        }

        return implode("\n", $lines);
    }
}
