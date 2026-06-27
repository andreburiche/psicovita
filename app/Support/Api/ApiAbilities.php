<?php

namespace App\Support\Api;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

final class ApiAbilities
{
    public const ALL = '*';

    public const READ = 'api:read';

    public const WRITE = 'api:write';

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

        $flat = Arr::flatten($requested);
        $flat = array_values(array_unique(array_map('strval', $flat)));

        foreach ($flat as $ability) {
            if (! in_array($ability, self::whitelist(), true)) {
                throw ValidationException::withMessages([
                    'abilities' => [__('Habilidade inválida: :a', ['a' => $ability])],
                ]);
            }
        }

        if (in_array(self::ALL, $flat, true)) {
            return [self::ALL];
        }

        return $flat;
    }
}
