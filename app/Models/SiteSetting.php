<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("site_setting.{$key}", 3600, function () use ($key, $default) {
            $row = static::query()->find($key);

            return $row?->value ?? $default;
        });
    }

    public static function put(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        Cache::forget("site_setting.{$key}");
        Cache::forget('site_public_context');
    }

    /**
     * @return array{social_links: array<string, string>, whatsapp: array<string, mixed>}
     */
    public static function publicContext(): array
    {
        return Cache::remember('site_public_context', 3600, function () {
            return [
                'social_links' => static::getValue('social_links', [
                    'instagram' => '',
                    'linkedin' => '',
                    'facebook' => '',
                    'youtube' => '',
                ]),
                'whatsapp' => static::getValue('whatsapp', [
                    'phone' => '',
                    'message' => '',
                    'enabled' => true,
                ]),
            ];
        });
    }
}
