<?php

namespace App\Support;

final class SocialAuthProvider
{
    public const GOOGLE = 'google';

    public const FACEBOOK = 'facebook';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::GOOGLE, self::FACEBOOK];
    }

    public static function isValid(string $provider): bool
    {
        return in_array($provider, self::all(), true);
    }

    public static function isConfigured(string $provider): bool
    {
        if (! self::isValid($provider)) {
            return false;
        }

        $clientId = config("services.{$provider}.client_id");

        return filled($clientId);
    }

    /**
     * @return list<string>
     */
    public static function configured(): array
    {
        return array_values(array_filter(self::all(), fn (string $provider) => self::isConfigured($provider)));
    }
}
