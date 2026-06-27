<?php

namespace App\Services\Chatbot;

use App\Enums\SupportConversationStatus;
use App\Enums\SupportMessageSenderType;
use App\Enums\SupportSourceChannel;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppIncomingHandler;
use App\Support\ContactHasher;
use Illuminate\Support\Facades\DB;

class SupportConversationService
{
    public function __construct(
        private readonly ProtocolService $protocols,
    ) {}

    public function findOpenForUser(User $user): ?SupportConversation
    {
        return SupportConversation::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                SupportConversationStatus::Open,
                SupportConversationStatus::PendingHuman,
                SupportConversationStatus::Assigned,
            ])
            ->latest('updated_at')
            ->first();
    }

    public function createForUser(
        User $user,
        SupportSourceChannel $channel = SupportSourceChannel::WebWidget,
        ?string $whatsappPhoneHash = null,
    ): SupportConversation {
        return SupportConversation::query()->create([
            'user_id' => $user->id,
            'status' => SupportConversationStatus::Open,
            'protocol_number' => $this->protocols->generate(),
            'source_channel' => $channel,
            'whatsapp_phone_hash' => $whatsappPhoneHash,
            'bot_active' => true,
        ]);
    }

    public function findOrCreateForUser(User $user): SupportConversation
    {
        return $this->findOpenForUser($user) ?? $this->createForUser($user);
    }

    public function findOrCreateForWhatsApp(?User $user, string $phoneDigits): SupportConversation
    {
        $normalizedPhone = WhatsAppIncomingHandler::normalizePhone($phoneDigits);
        $phoneHash = ContactHasher::phoneHash($normalizedPhone);

        if ($user !== null) {
            $open = $this->findOpenForUser($user);
            if ($open !== null) {
            if ($open->whatsapp_phone_hash === null) {
                $open->update(['whatsapp_phone_hash' => $phoneHash]);
            }

            return $this->ensureWhatsAppContact($open->fresh(), $normalizedPhone);
        }
        }

        $openByPhone = SupportConversation::query()
            ->where('whatsapp_phone_hash', $phoneHash)
            ->whereIn('status', [
                SupportConversationStatus::Open,
                SupportConversationStatus::PendingHuman,
                SupportConversationStatus::Assigned,
            ])
            ->latest('updated_at')
            ->first();

        if ($openByPhone !== null) {
            if ($user !== null && $openByPhone->user_id === null) {
                $openByPhone->update(['user_id' => $user->id]);
            }

            return $this->ensureWhatsAppContact($openByPhone->fresh(), $normalizedPhone);
        }

        return SupportConversation::query()->create([
            'user_id' => $user?->id,
            'status' => SupportConversationStatus::Open,
            'protocol_number' => $this->protocols->generate(),
            'source_channel' => SupportSourceChannel::Whatsapp,
            'whatsapp_phone_hash' => $phoneHash,
            'bot_context' => [
                'whatsapp_phone' => $normalizedPhone,
                'whatsapp_welcome_sent' => false,
            ],
            'bot_active' => true,
        ]);
    }

    public function ensureWhatsAppContact(SupportConversation $conversation, string $phoneDigits): SupportConversation
    {
        $normalizedPhone = WhatsAppIncomingHandler::normalizePhone($phoneDigits);
        $context = is_array($conversation->bot_context) ? $conversation->bot_context : [];
        $updates = [];

        if ($normalizedPhone !== '' && data_get($context, 'whatsapp_phone') !== $normalizedPhone) {
            $context['whatsapp_phone'] = $normalizedPhone;
            $updates['bot_context'] = $context;
        }

        if ($conversation->whatsapp_phone_hash === null && $normalizedPhone !== '') {
            $updates['whatsapp_phone_hash'] = ContactHasher::phoneHash($normalizedPhone);
        }

        if ($updates !== []) {
            $conversation->update($updates);

            return $conversation->fresh();
        }

        return $conversation;
    }

    public function markWhatsAppWelcomeSent(SupportConversation $conversation, bool $delivered): void
    {
        $context = is_array($conversation->bot_context) ? $conversation->bot_context : [];
        $context['whatsapp_welcome_sent'] = $delivered;

        $conversation->update(['bot_context' => $context]);
    }

    public function sendIncomingMessage(
        SupportConversation $conversation,
        string $body,
        ?User $user = null,
        ?string $externalId = null,
    ): SupportMessage {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException(__('A mensagem não pode estar vazia.'));
        }

        return $this->storeMessage(
            $conversation,
            SupportMessageSenderType::User,
            $user,
            $body,
            externalId: $externalId,
        );
    }

    public function sendUserMessage(
        SupportConversation $conversation,
        User $user,
        string $body,
        ?string $externalId = null,
    ): SupportMessage {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException(__('A mensagem não pode estar vazia.'));
        }

        return $this->storeMessage(
            $conversation,
            SupportMessageSenderType::User,
            $user,
            $body,
            externalId: $externalId,
        );
    }

    public function sendBotMessage(
        SupportConversation $conversation,
        string $body,
        ?string $intentSlug = null,
        ?array $metadata = null,
    ): SupportMessage {
        return $this->storeMessage(
            $conversation,
            SupportMessageSenderType::Bot,
            null,
            $body,
            $intentSlug,
            $metadata,
        );
    }

    public function sendSystemMessage(SupportConversation $conversation, string $body): SupportMessage
    {
        return $this->storeMessage($conversation, SupportMessageSenderType::System, null, $body);
    }

    public function sendAgentMessage(SupportConversation $conversation, User $agent, string $body): SupportMessage
    {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException(__('A mensagem não pode estar vazia.'));
        }

        return $this->storeMessage(
            $conversation,
            SupportMessageSenderType::Agent,
            $agent,
            $body,
        );
    }

    private function storeMessage(
        SupportConversation $conversation,
        SupportMessageSenderType $senderType,
        ?User $sender,
        string $body,
        ?string $intentSlug = null,
        ?array $metadata = null,
        ?string $externalId = null,
    ): SupportMessage {
        return DB::transaction(function () use ($conversation, $senderType, $sender, $body, $intentSlug, $metadata, $externalId) {
            $message = SupportMessage::query()->create([
                'support_conversation_id' => $conversation->id,
                'sender_type' => $senderType,
                'sender_user_id' => $sender?->id,
                'body' => $body,
                'intent_slug' => $intentSlug,
                'metadata' => $metadata,
                'external_id' => $externalId,
            ]);

            $conversation->touch();

            return $message;
        });
    }

    public function markFirstResponse(SupportConversation $conversation): void
    {
        if ($conversation->first_response_at === null) {
            $conversation->update(['first_response_at' => now()]);
        }
    }
}
