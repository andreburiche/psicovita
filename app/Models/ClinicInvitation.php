<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicInvitation extends Model
{
    protected $fillable = [
        'clinic_owner_id',
        'email',
        'email_hash',
        'token',
        'expires_at',
        'accepted_at',
        'accepted_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinic_owner_id');
    }

    public function acceptedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
