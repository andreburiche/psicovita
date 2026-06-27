<?php

namespace App\Models;

use App\Services\UserAvatarService;
use App\Support\AvatarStyleOptions;
use App\Support\ContactHasher;
use App\Support\CpfHasher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
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
            $query->where('professional_id', $user->clinicalPracticeId());
        }

        return $query->firstOrFail();
    }

    protected $fillable = [
        'professional_id',
        'name',
        'email',
        'email_hash',
        'phone',
        'phone_hash',
        'birth_date',
        'cpf',
        'cpf_hash',
        'address_postal_code',
        'address_street',
        'address_number',
        'address_complement',
        'address_district',
        'address_city',
        'address_state',
        'notes',
        'asaas_customer_id',
        'avatar_path',
        'avatar_style',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'cpf' => 'encrypted',
            'notes' => 'encrypted',
            'avatar_style' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Patient $patient) {
            app(UserAvatarService::class)->deleteStoredFile($patient);
        });

        static::saving(function (Patient $patient) {
            if ($patient->isDirty('cpf')) {
                $plaintext = $patient->cpf;
                if ($plaintext === null || $plaintext === '') {
                    $patient->cpf_hash = null;
                } else {
                    $digits = only_digits((string) $plaintext);
                    $patient->cpf_hash = strlen($digits) === 11 ? CpfHasher::hash($digits) : null;
                }
            }

            if ($patient->isDirty('email')) {
                $email = $patient->email;
                if ($email === null || trim((string) $email) === '') {
                    $patient->email_hash = null;
                } else {
                    $patient->email_hash = ContactHasher::emailHash((string) $email);
                }
            }

            if ($patient->isDirty('phone')) {
                $phone = $patient->phone;
                if ($phone === null || trim((string) $phone) === '') {
                    $patient->phone_hash = null;
                } else {
                    $digits = only_digits((string) $phone);
                    $patient->phone_hash = strlen($digits) >= 10 ? ContactHasher::phoneHash($digits) : null;
                }
            }
        });
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function therapySessions(): HasMany
    {
        return $this->hasMany(TherapySession::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function clinicalRecords(): HasMany
    {
        return $this->hasMany(ClinicalRecord::class);
    }

    public function aiRequests(): HasMany
    {
        return $this->hasMany(AiRequest::class);
    }

    public function anamneses(): HasMany
    {
        return $this->hasMany(PatientAnamnesis::class);
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PatientDocument::class)->orderByDesc('received_at')->orderByDesc('id');
    }

    public function clinicalDocuments(): HasMany
    {
        return $this->hasMany(PatientClinicalDocument::class)->latest('issued_at')->latest('id');
    }

    public function scaleAssessments(): HasMany
    {
        return $this->hasMany(PatientScaleAssessment::class)->latest('assessed_at')->latest('id');
    }

    public function therapeuticGoals(): HasMany
    {
        return $this->hasMany(PatientTherapeuticGoal::class)->latest('id');
    }

    public function portalInvitations(): HasMany
    {
        return $this->hasMany(PatientPortalInvitation::class)->latest('id');
    }

    public function portalUser(): ?User
    {
        if ($this->relationLoaded('portalUser')) {
            return $this->getRelation('portalUser');
        }

        if ($this->email_hash === null || $this->email_hash === '') {
            return null;
        }

        return User::query()->where('email_hash', $this->email_hash)->first();
    }

    /** Conta ou ficha onde a foto é guardada (sincronização portal ↔ ficha clínica). */
    public function avatarOwner(): User|Patient
    {
        return $this->portalUser() ?? $this;
    }

    /**
     * @return array{shape: string, ring: string, filter: string}
     */
    public function resolvedAvatarStyle(): array
    {
        $owner = $this->avatarOwner();

        if ($owner instanceof User) {
            return $owner->resolvedAvatarStyle();
        }

        return AvatarStyleOptions::resolve($this->avatar_style);
    }

    public function avatarUrl(): ?string
    {
        $owner = $this->avatarOwner();

        if ($owner instanceof User) {
            return $owner->avatarUrl();
        }

        if ($this->avatar_path === null || $this->avatar_path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($this->avatar_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    public function avatarInitials(): string
    {
        $parts = preg_split('/\s+/u', trim((string) $this->name)) ?: [];

        if ($parts === []) {
            return '?';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[array_key_last($parts)], 0, 1));
    }

    public function hasAvatar(): bool
    {
        return $this->avatarUrl() !== null;
    }
}
