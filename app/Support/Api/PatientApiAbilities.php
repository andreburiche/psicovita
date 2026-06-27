<?php

namespace App\Support\Api;

final class PatientApiAbilities
{
    public const ALL = 'patient:*';

    public const READ = 'patient:read';

    public const WRITE = 'patient:write';

    /**
     * @return list<string>
     */
    public static function whitelist(): array
    {
        return [self::ALL, self::READ, self::WRITE];
    }

    /**
     * @param  list<string>|null  $requested
     * @return list<string>
     */
    public static function normalizeForToken(?array $requested): array
    {
        if ($requested === null || $requested === []) {
            return [self::ALL];
        }

        $flat = array_values(array_unique(array_map('strval', $requested)));

        foreach ($flat as $ability) {
            if (! in_array($ability, self::whitelist(), true)) {
                return [self::ALL];
            }
        }

        if (in_array(self::ALL, $flat, true)) {
            return [self::ALL];
        }

        return $flat;
    }
}
