<?php

namespace App\Support;

final class ContrastChecker
{
    /**
     * @return array{ratio: float, passes_aa_normal: bool, passes_aa_large: bool}
     */
    public static function evaluate(string $foregroundHex, string $backgroundHex): array
    {
        $ratio = self::contrastRatio(self::relativeLuminance($foregroundHex), self::relativeLuminance($backgroundHex));

        return [
            'ratio' => round($ratio, 2),
            'passes_aa_normal' => $ratio >= 4.5,
            'passes_aa_large' => $ratio >= 3.0,
        ];
    }

    public static function contrastRatio(float $l1, float $l2): float
    {
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public static function relativeLuminance(string $hex): float
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        $channels = array_map(function (float $c) {
            $c = $c / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, [$r, $g, $b]);

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
