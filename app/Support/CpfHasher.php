<?php

namespace App\Support;

final class CpfHasher
{
    public static function hash(string $digits): string
    {
        $normalized = only_digits($digits);

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
