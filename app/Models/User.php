<?php

namespace App\Models;

use App\Enums\PaymentMethodPreference;
use App\Enums\UserProfessionalFunction;
use App\Enums\UserRole;
use App\Services\ProfessionalPixSettingsService;
use App\Services\SubscriptionService;
use App\Services\UserAvatarService;
use App\Support\AvatarStyleOptions;
use App\Support\ContactHasher;
use App\Support\Permissions;
use App\Support\UiAccentOptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_hash',
        'password',
        'role',
        'professional_id',
        'clinic_owner_id',
        'phone',
        'phone_hash',
        'crp_number',
        'professional_function',
        'professional_bio',
        'avatar_path',
        'avatar_style',
        'institution_logo_path',
        'ui_accent',
        'whatsapp_notifications',
        'asaas_customer_id',
        'asaas_wallet_id',
        'pix_manual_link',
        'pix_qrcode_path',
        'payment_method_preference',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'professional_function' => UserProfessionalFunction::class,
            'avatar_style' => 'array',
            'whatsapp_notifications' => 'boolean',
            'payment_method_preference' => PaymentMethodPreference::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            app(UserAvatarService::class)->deleteStoredFile($user);
            app(\App\Services\InstitutionLogoService::class)->deleteStoredFile($user);
            app(ProfessionalPixSettingsService::class)->deleteQrcode($user);
        });

        static::saving(function (User $user) {
            if ($user->isDirty('email')) {
                $email = $user->email;
                if ($email !== null && trim((string) $email) !== '') {
                    $user->email = Str::lower(trim((string) $email));
                    $email = $user->email;
                }
                if ($email === null || trim((string) $email) === '') {
                    $user->email_hash = null;
                } else {
                    $user->email_hash = ContactHasher::emailHash(Str::lower(trim((string) $email)));
                }
            }

            if ($user->isDirty('phone')) {
                $phone = $user->phone;
                if ($phone !== null && trim((string) $phone) !== '') {
                    $digits = \App\Services\WhatsApp\WhatsAppIncomingHandler::normalizePhone((string) $phone);
                    $user->phone = $digits !== '' ? $digits : null;
                    $phone = $user->phone;
                }
                if ($phone === null || trim((string) $phone) === '') {
                    $user->phone_hash = null;
                } else {
                    $digits = \App\Services\WhatsApp\WhatsAppIncomingHandler::normalizePhone((string) $phone);
                    $user->phone_hash = strlen($digits) >= 10
                        ? ContactHasher::phoneHash($digits)
                        : null;
                }
            }
        });
    }

    public static function findByEmail(string $email): ?self
    {
        $normalized = Str::lower(trim($email));
        if ($normalized === '') {
            return null;
        }

        return static::query()
            ->where('email_hash', ContactHasher::emailHash($normalized))
            ->first();
    }

    public static function findByPhoneDigits(string $phone): ?self
    {
        $variants = \App\Services\WhatsApp\WhatsAppIncomingHandler::phoneDigitVariants($phone);
        if ($variants === []) {
            return null;
        }

        $hashes = array_map(
            fn (string $digits) => ContactHasher::phoneHash($digits),
            $variants,
        );

        return static::query()
            ->whereIn('phone_hash', $hashes)
            ->first();
    }

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function clinicOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clinic_owner_id');
    }

    public function clinicTeamMembers(): HasMany
    {
        return $this->hasMany(User::class, 'clinic_owner_id');
    }

    public function clinicInvitations(): HasMany
    {
        return $this->hasMany(ClinicInvitation::class, 'clinic_owner_id');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function clinicalPracticeId(): int
    {
        if ($this->isProfessional() && $this->clinic_owner_id !== null) {
            return (int) $this->clinic_owner_id;
        }

        return (int) $this->id;
    }

    public function isClinicTeamMember(): bool
    {
        return $this->isProfessional() && $this->clinic_owner_id !== null;
    }

    public function isClinicOwner(): bool
    {
        return $this->isProfessional() && $this->clinic_owner_id === null;
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'professional_id');
    }

    public function therapySessionsAsProfessional(): HasMany
    {
        return $this->hasMany(TherapySession::class, 'professional_id');
    }

    public function scheduleBlocks(): HasMany
    {
        return $this->hasMany(ScheduleBlock::class, 'professional_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function recordAccessLogs(): HasMany
    {
        return $this->hasMany(RecordAccessLog::class);
    }

    public function aiRequests(): HasMany
    {
        return $this->hasMany(AiRequest::class);
    }

    public function professionalFiles(): HasMany
    {
        return $this->hasMany(UserProfessionalFile::class)->latest();
    }

    public function professionalSubscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProfessionalSubscription::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isProfessional()) {
            return false;
        }

        return in_array($permission, Permissions::documentRequestPermissions(), true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSupportAgent(): bool
    {
        return $this->role === UserRole::SupportAgent;
    }

    public function canAccessSupportDesk(): bool
    {
        return ($this->isAdmin() || $this->isSupportAgent())
            && config('psiconecta.chatbot.enabled', true);
    }

    public function isDpo(): bool
    {
        $dpoEmail = strtolower(trim((string) config('compliance.lgpd.dpo_email')));

        return $dpoEmail !== '' && $this->normalizedEmail() === $dpoEmail;
    }

    public function canManageLgpdRequests(): bool
    {
        return $this->isAdmin() || $this->isDpo();
    }

    public function dataSubjectRequests(): HasMany
    {
        return $this->hasMany(DataSubjectRequest::class);
    }

    public function isProfessional(): bool
    {
        return $this->role === UserRole::Professional;
    }

    public function isPatient(): bool
    {
        return $this->role === UserRole::Patient;
    }

    /**
     * Timeout de inatividade (minutos) conforme o perfil do utilizador.
     */
    public function inactivityTimeoutMinutes(): int
    {
        $timeouts = config('security.inactivity_timeout', []);

        if ($this->isAdmin() || $this->isSupportAgent()) {
            return max(1, (int) ($timeouts['admin'] ?? 30));
        }

        if ($this->isProfessional()) {
            return max(1, (int) ($timeouts['professional'] ?? 60));
        }

        if ($this->isPatient()) {
            return max(1, (int) ($timeouts['patient'] ?? 60));
        }

        return max(1, (int) ($timeouts['default'] ?? 60));
    }

    public function canUseSubscriptionFeature(string $feature): bool
    {
        return app(SubscriptionService::class)->canUseFeature($this, $feature);
    }

    public function tenantProfessionalId(): ?int
    {
        if ($this->isProfessional() || $this->isAdmin()) {
            return $this->clinicalPracticeId();
        }

        if ($this->isPatient()) {
            return $this->professional_id;
        }

        return null;
    }

    /**
     * E-mail normalizado para comparação com fichas de paciente.
     */
    public function normalizedEmail(): string
    {
        return Str::lower(trim((string) ($this->email ?? '')));
    }

    /**
     * Conta criada como "profissional" mas só existe na plataforma como paciente na ficha de outro (sem pacientes próprios).
     */
    public function isMisregisteredProfessionalAsPatient(): bool
    {
        if (! $this->isProfessional()) {
            return false;
        }

        if ($this->patients()->exists()) {
            return false;
        }

        $email = $this->normalizedEmail();
        if ($email === '') {
            return false;
        }

        $distinctOtherPractices = Patient::query()
            ->where('email_hash', \App\Support\ContactHasher::emailHash($email))
            ->where('professional_id', '<>', $this->id)
            ->distinct()
            ->pluck('professional_id');

        // Igual ao registo: só tratamos como “só paciente” quando a ficha aponta para um único consultório.
        return $distinctOtherPractices->count() === 1;
    }

    /**
     * Deve ver o portal do paciente (layout / rota inicial), não a área clínica.
     */
    public function usesPatientPortalExperience(): bool
    {
        if ($this->isAdmin()) {
            return false;
        }

        if ($this->isPatient()) {
            return true;
        }

        return $this->isMisregisteredProfessionalAsPatient();
    }

    /**
     * Rota nomeada principal após login / verificação de e-mail (área clínica vs portal do paciente).
     */
    public function defaultAppRouteName(): string
    {
        if ($this->isAdmin()) {
            return 'dashboard';
        }

        if ($this->isSupportAgent()) {
            return 'admin.support.index';
        }

        if ($this->usesPatientPortalExperience()) {
            return 'patient.home';
        }

        if ($this->isProfessional()) {
            return 'dashboard';
        }

        return 'patient.home';
    }

    /**
     * @return array{shape: string, ring: string, filter: string}
     */
    public function resolvedAvatarStyle(): array
    {
        return AvatarStyleOptions::resolve($this->avatar_style);
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null || $this->avatar_path === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($this->avatar_path)) {
            return null;
        }

        // Rota da app (não depende de symlink public/storage — comum falhar na hospedagem).
        return route('media.user-avatar', [
            'user' => $this,
            'v' => $this->updated_at?->getTimestamp() ?? time(),
        ]);
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

    public function institutionLogoUrl(): ?string
    {
        if (blank($this->institution_logo_path) || ! Storage::disk('public')->exists($this->institution_logo_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->institution_logo_path);
    }

    public function pixQrcodeUrl(): ?string
    {
        if (blank($this->pix_qrcode_path) || ! Storage::disk('public')->exists($this->pix_qrcode_path)) {
            return null;
        }

        return route('storage.public', [
            'path' => $this->pix_qrcode_path,
            'v' => $this->updated_at?->getTimestamp() ?? time(),
        ]);
    }

    public function clinicalPracticeOwner(): User
    {
        if ($this->isClinicTeamMember() && $this->clinic_owner_id) {
            return static::query()->find($this->clinic_owner_id) ?? $this;
        }

        return $this;
    }

    public function hasInstitutionLogo(): bool
    {
        return $this->institutionLogoUrl() !== null;
    }

    public function resolvedUiAccent(): string
    {
        return UiAccentOptions::resolve($this->ui_accent);
    }

    public function professionalFunctionLabel(): ?string
    {
        return $this->professional_function?->label();
    }
}
