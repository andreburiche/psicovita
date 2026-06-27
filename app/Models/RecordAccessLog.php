<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordAccessLog extends Model
{
    use HasFactory;

    public const ACTION_VIEWED = 'viewed';

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_EXPORTED = 'exported';

    public const ACTION_DELETED = 'deleted';

    protected $fillable = [
        'user_id',
        'clinical_record_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clinicalRecord(): BelongsTo
    {
        return $this->belongsTo(ClinicalRecord::class);
    }
}
