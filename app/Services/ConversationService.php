<?php

namespace App\Services;

use App\Enums\MessageChannel;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\NewConversationMessageNotification;
use App\Support\AuditTrail;
use App\Support\ContactHasher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConversationService
{
    public function __construct(
        private readonly WhatsAppConversationService $whatsApp,
        private readonly MessageAttachmentService $attachments,
    ) {}

    /**
     * @return Collection<int, Conversation>
     */
    public function inboxFor(User $user, ?string $search = null): Collection
    {
        $query = Conversation::query()
            ->with(['professional', 'patientUser', 'patient', 'latestMessage.sender'])
            ->orderByDesc(DB::raw('COALESCE(last_message_at, updated_at)'))
            ->orderByDesc('id');

        if ($user->isProfessional()) {
            $query->where('professional_id', $user->clinicalPracticeId());
        } elseif ($user->isPatient()) {
            $query->where('patient_user_id', $user->id);
        } else {
            return new Collection;
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term, $user): void {
                if ($user->isProfessional()) {
                    $q->whereHas('patientUser', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhereHas('patient', fn ($p) => $p->where('name', 'like', $term));
                } else {
                    $q->whereHas('professional', fn ($u) => $u->where('name', 'like', $term));
                }
            });
        }

        return $query->get();
    }

    public function findOrCreateForUsers(User $professional, User $patientUser, ?Patient $patient = null): Conversation
    {
        $conversation = Conversation::query()->firstOrCreate(
            [
                'professional_id' => $professional->clinicalPracticeId(),
                'patient_user_id' => $patientUser->id,
            ],
            [
                'patient_id' => $patient?->id ?? $this->resolvePatientRecord($professional, $patientUser)?->id,
                'whatsapp_phone_hash' => $this->resolveWhatsAppPhoneHash($professional, $patientUser),
            ],
        );

        if ($patient !== null && (int) $conversation->patient_id !== (int) $patient->id) {
            $conversation->update(['patient_id' => $patient->id]);
        }

        if ($conversation->whatsapp_phone_hash === null) {
            $hash = $this->resolveWhatsAppPhoneHash($professional, $patientUser);
            if ($hash) {
                $conversation->update(['whatsapp_phone_hash' => $hash]);
            }
        }

        return $conversation->fresh(['professional', 'patientUser', 'patient']);
    }

    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $body,
        MessageChannel $channel = MessageChannel::Internal,
        bool $mirrorToWhatsApp = false,
        ?string $externalId = null,
        ?UploadedFile $attachment = null,
    ): Message {
        $recipient = $conversation->peerFor($sender);

        if ($recipient === null) {
            throw new \InvalidArgumentException(__('Utilizador não pertence a esta conversa.'));
        }

        $body = trim($body);
        if ($body === '' && $attachment === null) {
            throw new \InvalidArgumentException(__('A mensagem não pode estar vazia.'));
        }

        $message = DB::transaction(function () use ($conversation, $sender, $recipient, $body, $channel, $externalId, $attachment) {
            $message = Message::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'body' => $body !== '' ? $body : __('📎 Anexo'),
                'channel' => $channel,
                'external_id' => $externalId,
            ]);

            if ($attachment !== null) {
                $this->attachments->store($message, $attachment);
            }

            $conversation->update(['last_message_at' => $message->created_at]);

            AuditTrail::entity('send', 'messages', $message, [
                'conversation_id' => $conversation->id,
                'channel' => $channel->value,
                'has_attachment' => $attachment !== null,
            ]);

            return $message;
        });

        if ($mirrorToWhatsApp && $sender->id === $conversation->professional_id && $conversation->canSyncWhatsApp()) {
            $message->load('attachments');

            if ($message->attachments->isNotEmpty()) {
                $caption = $body !== '' && $body !== __('📎 Anexo') ? $body : null;
                foreach ($message->attachments as $attachment) {
                    $waId = $this->whatsApp->sendDocument($conversation, $attachment, $caption);
                    if ($waId && $message->external_id === null) {
                        $message->update(['external_id' => $waId]);
                    }
                    $caption = null;
                }
            } else {
                $waExternalId = $this->whatsApp->sendText($conversation, $message->body);
                if ($waExternalId) {
                    $message->update(['external_id' => $waExternalId]);
                }
            }
        }

        $recipient->notify(new NewConversationMessageNotification($conversation, $message));

        return $message->load(['sender', 'recipient', 'attachments']);
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Message>
     */
    public function paginateMessages(Conversation $conversation, ?string $search = null, int $perPage = 50)
    {
        $query = $conversation->messages()
            ->with(['sender', 'recipient', 'attachments'])
            ->orderBy('created_at');

        if ($search !== null && trim($search) !== '') {
            $needle = Str::lower(trim($search));
            $matchingIds = $conversation->messages()
                ->get()
                ->filter(fn (Message $message) => str_contains(Str::lower((string) $message->body), $needle))
                ->pluck('id');

            $query->whereIn('id', $matchingIds);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return Collection<int, Message>
     */
    public function messagesSince(Conversation $conversation, int $afterId): Collection
    {
        return $conversation->messages()
            ->with(['sender', 'recipient', 'attachments'])
            ->where('id', '>', $afterId)
            ->orderBy('created_at')
            ->get();
    }

    public function recordWhatsappConsent(Conversation $conversation, User $patientUser): void
    {
        if ($patientUser->id !== $conversation->patient_user_id) {
            throw new \InvalidArgumentException(__('Apenas o paciente pode consentir.'));
        }

        $conversation->update(['patient_whatsapp_consent_at' => now()]);

        AuditTrail::entity('consent', 'conversations', $conversation, [
            'type' => 'whatsapp_sync',
        ], $patientUser);
    }

    public function revokeWhatsappConsent(Conversation $conversation, User $patientUser): void
    {
        if ($patientUser->id !== $conversation->patient_user_id) {
            throw new \InvalidArgumentException(__('Apenas o paciente pode revogar.'));
        }

        $conversation->update([
            'patient_whatsapp_consent_at' => null,
            'whatsapp_enabled' => false,
        ]);
    }

    public function markAsRead(Conversation $conversation, User $reader): void
    {
        if (! $conversation->involvesUser($reader)) {
            return;
        }

        $now = now();

        if ($reader->id === $conversation->professional_id) {
            $conversation->update(['professional_last_read_at' => $now]);
        } else {
            $conversation->update(['patient_last_read_at' => $now]);
        }

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('recipient_id', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => $now]);
    }

    public function totalUnreadFor(User $user): int
    {
        return $this->inboxFor($user)->sum(fn (Conversation $c) => $c->unreadCountFor($user));
    }

    public function userCanAccess(User $user, Conversation $conversation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $conversation->involvesUser($user);
    }

    /**
     * @return SupportCollection<int, array{patient: Patient, portal_user: User|null, can_converse: bool}>
     */
    public function listPatientsForConversationPicker(User $professional): SupportCollection
    {
        $practiceId = $professional->clinicalPracticeId();

        return Patient::query()
            ->where('professional_id', $practiceId)
            ->orderBy('name')
            ->get()
            ->map(function (Patient $patient) use ($professional): array {
                $portalUser = $this->resolvePortalUserForPatient($patient, $professional);

                return [
                    'patient' => $patient,
                    'portal_user' => $portalUser,
                    'can_converse' => $portalUser !== null,
                ];
            })
            ->values();
    }

    public function resolvePortalUserForPatient(Patient $patient, User $professional): ?User
    {
        if ((int) $patient->professional_id !== $professional->clinicalPracticeId()) {
            return null;
        }

        $existingConversation = Conversation::query()
            ->where('professional_id', $professional->clinicalPracticeId())
            ->where('patient_id', $patient->id)
            ->whereNotNull('patient_user_id')
            ->with('patientUser')
            ->first();

        if ($existingConversation?->patientUser instanceof User) {
            return $existingConversation->patientUser;
        }

        $portalUser = $patient->portalUser();
        if ($portalUser !== null && $portalUser->isPatient()) {
            return $portalUser;
        }

        return null;
    }

    /**
     * @return SupportCollection<int, User>
     */
    public function eligiblePatientUsers(User $professional): SupportCollection
    {
        $practiceId = $professional->clinicalPracticeId();

        $direct = User::query()
            ->where('professional_id', $practiceId)
            ->where('role', UserRole::Patient)
            ->get(['id', 'name', 'email', 'role', 'phone']);

        $patientEmails = Patient::query()
            ->where('professional_id', $practiceId)
            ->whereNotNull('email_hash')
            ->get(['email'])
            ->map(fn (Patient $patient) => Str::lower(trim((string) $patient->email)))
            ->filter()
            ->unique()
            ->values();

        if ($patientEmails->isEmpty()) {
            return $direct->sortBy('name')->values();
        }

        $emailHashes = $patientEmails
            ->map(fn (string $email) => ContactHasher::emailHash($email))
            ->unique()
            ->values();

        $byEmail = User::query()
            ->where('id', '!=', $professional->id)
            ->whereIn('email_hash', $emailHashes)
            ->get(['id', 'name', 'email', 'role', 'phone']);

        return $direct
            ->merge($byEmail)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    public function patientUserIsEligible(User $professional, User $patientUser): bool
    {
        return $this->eligiblePatientUsers($professional)->contains('id', $patientUser->id);
    }

    public function resolvePatientRecord(User $professional, User $patientUser): ?Patient
    {
        if ($patientUser->professional_id === $professional->clinicalPracticeId()) {
            return Patient::query()
                ->where('professional_id', $professional->clinicalPracticeId())
                ->where('email_hash', $patientUser->email_hash)
                ->orderByDesc('updated_at')
                ->first();
        }

        if (! $patientUser->email) {
            return null;
        }

        return Patient::query()
            ->where('professional_id', $professional->clinicalPracticeId())
            ->where('email_hash', ContactHasher::emailHash(Str::lower(trim((string) $patientUser->email))))
            ->orderByDesc('updated_at')
            ->first();
    }

    public function resolveProfessionalForPatientUser(User $patientUser): ?User
    {
        if ($patientUser->professional_id) {
            return User::query()->find($patientUser->professional_id);
        }

        $email = $patientUser->email;
        if (! $email) {
            return null;
        }

        $patient = Patient::query()
            ->where('email_hash', ContactHasher::emailHash(Str::lower(trim((string) $email))))
            ->orderByDesc('updated_at')
            ->first();

        return $patient ? User::query()->find($patient->professional_id) : null;
    }

    public function backfillFromLegacyMessages(): void
    {
        Message::query()
            ->whereNull('conversation_id')
            ->orderBy('id')
            ->chunkById(100, function ($messages): void {
                foreach ($messages as $message) {
                    $pair = $this->resolveTherapyPair(
                        User::query()->find($message->sender_id),
                        User::query()->find($message->recipient_id),
                    );

                    if ($pair === null) {
                        continue;
                    }

                    [$professional, $patientUser] = $pair;
                    $conversation = $this->findOrCreateForUsers($professional, $patientUser);

                    $message->update([
                        'conversation_id' => $conversation->id,
                        'channel' => MessageChannel::Internal,
                    ]);

                    if ($conversation->last_message_at === null || $message->created_at->gt($conversation->last_message_at)) {
                        $conversation->update(['last_message_at' => $message->created_at]);
                    }
                }
            });
    }

    /**
     * @return array{0: User, 1: User}|null
     */
    private function resolveTherapyPair(?User $a, ?User $b): ?array
    {
        if ($a === null || $b === null) {
            return null;
        }

        if ($a->isProfessional() && $this->patientUserIsEligible($a, $b)) {
            return [$a, $b];
        }

        if ($b->isProfessional() && $this->patientUserIsEligible($b, $a)) {
            return [$b, $a];
        }

        if ($a->isPatient() && $b->isProfessional() && $a->professional_id === $b->clinicalPracticeId()) {
            return [$b, $a];
        }

        if ($b->isPatient() && $a->isProfessional() && $b->professional_id === $a->clinicalPracticeId()) {
            return [$a, $b];
        }

        return null;
    }

    private function resolveWhatsAppPhoneHash(User $professional, User $patientUser): ?string
    {
        $patient = $this->resolvePatientRecord($professional, $patientUser);
        $phone = $patient?->phone ?: $patientUser->phone;

        if (! $phone) {
            return null;
        }

        return ContactHasher::phoneHash(
            \App\Services\WhatsApp\WhatsAppIncomingHandler::normalizePhone(trim((string) $phone)),
        );
    }
}
