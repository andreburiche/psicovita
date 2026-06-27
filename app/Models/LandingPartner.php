<?php

namespace App\Models;

use App\Services\LandingPartnerLogoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LandingPartner extends Model
{
    protected $fillable = [
        'name',
        'url',
        'logo_path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActiveOrdered($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function logoUrl(): ?string
    {
        if (blank($this->logo_path) || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    protected static function booted(): void
    {
        static::deleting(function (LandingPartner $partner): void {
            app(LandingPartnerLogoService::class)->deleteStoredFile($partner);
        });
    }
}
