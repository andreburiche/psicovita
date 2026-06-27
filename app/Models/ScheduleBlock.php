<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleBlock extends Model
{
    /** @use HasFactory<\Database\Factories\ScheduleBlockFactory> */
    use HasFactory;

    /**
     * @param  mixed  $value
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $query = static::query()->where($field, $value);

        $user = auth()->user();
        if ($user?->isProfessional()) {
            $query->where('professional_id', $user->id);
        }

        return $query->firstOrFail();
    }

    protected $fillable = [
        'professional_id',
        'block_date',
        'start_time',
        'end_time',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'block_date' => 'date',
        ];
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }
}
