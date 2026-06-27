<?php

namespace App\Models;

use App\Enums\DocumentRequestStatus;
use App\Enums\InstitutionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'institution_name',
        'institution_type',
        'contact_name',
        'contact_email',
        'contact_phone',
        'requested_documents',
        'request_reason',
        'authorization_attached',
        'request_date',
        'expected_return_date',
        'status',
        'notes',
        'last_email_sent_at',
        'last_email_sent_to',
        'last_email_sent_by',
        'patient_consent_at',
        'patient_consent_recorded_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_documents' => 'array',
            'authorization_attached' => 'boolean',
            'request_date' => 'date',
            'expected_return_date' => 'date',
            'status' => DocumentRequestStatus::class,
            'institution_type' => InstitutionType::class,
            'patient_consent_at' => 'datetime',
            'last_email_sent_at' => 'datetime',
            'notes' => 'encrypted',
            'request_reason' => 'encrypted',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DocumentRequestFile::class);
    }

    public function patientDocuments(): HasMany
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentRequestAccessLog::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function consentRecordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_consent_recorded_by');
    }

    public function lastEmailSentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_email_sent_by');
    }
}
