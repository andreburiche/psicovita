<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequestAccessLog extends Model
{
    public const ACTION_VIEWED = 'viewed';

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_PDF = 'pdf_downloaded';

    public const ACTION_FILE_UPLOADED = 'file_uploaded';

    public const ACTION_FILE_DOWNLOADED = 'file_downloaded';

    public const ACTION_EMAIL_SENT = 'email_sent';

    protected $fillable = [
        'document_request_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
    ];

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
