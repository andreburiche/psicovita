<?php

namespace App\Support;

final class PortalBrand
{
    public static function logoAbsolutePath(): ?string
    {
        $configured = public_path((string) config('app.logo'));

        if (is_file($configured)) {
            return $configured;
        }

        $mark = public_path('images/brand-mark.svg');

        return is_file($mark) ? $mark : null;
    }

    public static function fileToDataUri(?string $path): ?string
    {
        if ($path === null || ! is_file($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $mime = match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    public static function logoDataUri(): ?string
    {
        return self::fileToDataUri(self::logoAbsolutePath());
    }

    public static function appName(): string
    {
        return (string) config('app.name');
    }
}
