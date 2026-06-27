<?php

namespace App\Support;

use Illuminate\Support\Str;

final class ContactHasher
{
    public static function emailHash(string $email): string
    {
        $normalized = Str::lower(trim($email));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    public static function phoneHash(string $phone): string
    {
        $digits = only_digits($phone);

        return hash_hmac('sha256', $digits, (string) config('app.key'));
    }
}
