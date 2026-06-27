<?php

namespace App\Support;

class PixCheckout
{
    /**
     * @param  array<string, mixed>  $pix
     */
    public static function hasImage(array $pix): bool
    {
        return filled($pix['image_url'] ?? null)
            || filled($pix['encoded_image'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $pix
     */
    public static function isDisplayable(array $pix): bool
    {
        if (! self::hasImage($pix)) {
            return false;
        }

        if (filled($pix['payload'] ?? null)) {
            return true;
        }

        return filled($pix['image_url'] ?? null)
            && (bool) ($pix['raw']['static_fallback'] ?? false);
    }

    public static function isAsaasStubMode(): bool
    {
        return ! config('asaas.enabled') || ! filled(config('asaas.api_key'));
    }
}
