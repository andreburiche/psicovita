<?php

namespace App\Support;

final class AvatarStyleOptions
{
    public const SHAPES = ['circle', 'rounded', 'square'];

    public const RINGS = ['violet', 'indigo', 'emerald', 'rose', 'none'];

    public const FILTERS = ['none', 'grayscale', 'warm', 'cool'];

    /**
     * @return array{shape: string, ring: string, filter: string}
     */
    public static function defaults(): array
    {
        return [
            'shape' => 'circle',
            'ring' => 'violet',
            'filter' => 'none',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $style
     * @return array{shape: string, ring: string, filter: string}
     */
    public static function resolve(?array $style): array
    {
        $defaults = self::defaults();

        if ($style === null) {
            return $defaults;
        }

        $shape = (string) ($style['shape'] ?? $defaults['shape']);
        $ring = (string) ($style['ring'] ?? $defaults['ring']);
        $filter = (string) ($style['filter'] ?? $defaults['filter']);

        return [
            'shape' => in_array($shape, self::SHAPES, true) ? $shape : $defaults['shape'],
            'ring' => in_array($ring, self::RINGS, true) ? $ring : $defaults['ring'],
            'filter' => in_array($filter, self::FILTERS, true) ? $filter : $defaults['filter'],
        ];
    }

    public static function shapeClass(string $shape): string
    {
        return match ($shape) {
            'square' => 'rounded-none',
            'rounded' => 'rounded-2xl',
            default => 'rounded-full',
        };
    }

    public static function ringClass(string $ring): string
    {
        return match ($ring) {
            'indigo' => 'ring-2 ring-indigo-400/80 dark:ring-indigo-500/70',
            'emerald' => 'ring-2 ring-emerald-400/80 dark:ring-emerald-500/70',
            'rose' => 'ring-2 ring-rose-400/80 dark:ring-rose-500/70',
            'none' => '',
            default => 'ring-2 ring-violet-400/80 dark:ring-violet-500/70',
        };
    }

    public static function filterClass(string $filter): string
    {
        return match ($filter) {
            'grayscale' => 'grayscale',
            'warm' => 'sepia-[.35] saturate-[1.35]',
            'cool' => 'hue-rotate-[15deg] saturate-[1.15]',
            default => '',
        };
    }
}
