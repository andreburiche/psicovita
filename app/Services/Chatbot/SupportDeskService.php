<?php

namespace App\Services\Chatbot;

use App\Enums\SupportConversationStatus;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\SupportQueue;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SupportDeskService
{
    public function __construct(
        private readonly SupportConversationService $conversations,
        private readonly SupportWhatsAppOutboundService $whatsAppOutbound,
        private readonly ChatbotLogService $logs,
    ) {}

    /**
     * @return Collection<int, SupportConversation>
     */
    public function inbox(
        ?int $queueId = null,
        ?string $status = null,
        bool $mineOnly = false,
        ?User $agent = null,
        ?string $search = null,
    ): Collection {
        $query = SupportConversation::query()
            ->with(['user', 'queue', 'assignedAgent'])
            ->withCount('messages')
            ->withMax('messages', 'created_at')
            ->orderByRaw("CASE status
                WHEN 'pending_human' THEN 0
                WHEN 'assigned' THEN 1
                WHEN 'open' THEN 2
                ELSE 3 END")
            ->orderByDesc('updated_at');

        if ($queueId !== null) {
            $query->where('support_queue_id', $queueId);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        } else {
            $query->whereNotIn('status', [
                SupportConversationStatus::Closed->value,
            ]);
        }

        if ($mineOnly && $agent !== null) {
            $query->where('assigned_agent_id', $agent->id);
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('protocol_number', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term));
            });
        }

        return $query->get();
    }

    /**
     * @return array{first_response: string, resolution: string, first_sla_minutes: int, resolution_sla_minutes: int}
     */
    public function slaMeta(SupportConversation $conversation): array
    {
        $firstSla = (int) ($conversation->queue?->sla_first_response_minutes ?? 30);
        $resolutionSla = (int) ($conversation->queue?->sla_resolution_minutes ?? 480);
        $created = $conversation->created_at ?? now();

        $firstResponse = 'ok';
        if ($conversation->first_response_at === null) {
            $elapsed = $created->diffInMinutes(now());
            $firstResponse = match (true) {
                $elapsed >= $firstSla => 'breached',
                $elapsed >= (int) ($firstSla * 0.75) => 'warning',
                default => 'pending',
            };
        }

        $resolution = 'ok';
        if (! in_array($conversation->status, [
            SupportConversationStatus::Resolved,
            SupportConversationStatus::Closed,
        ], true)) {
            $elapsed = $created->diffInMinutes(now());
            $resolution = match (true) {
                $elapsed >= $resolutionSla => 'breached',
                $elapsed >= (int) ($resolutionSla * 0.75) => 'warning',
                default => 'pending',
            };
        }

        return [
            'first_response' => $firstResponse,
            'resolution' => $resolution,
            'first_sla_minutes' => $firstSla,
            'resolution_sla_minutes' => $resolutionSla,
        ];
    }

    public function pendingCount(): int
    {
        return SupportConversation::query()
            ->where('status', SupportConversationStatus::PendingHuman)
            ->count();
    }

    public function assign(SupportConversation $conversation, User $agent): SupportConversation
    {
        return DB::transaction(function () use ($conversation, $agent) {
            $conversation->update([
                'assigned_agent_id' => $agent->id,
                'status' => SupportConversationStatus::Assigned,
                'bot_active' => false,
            ]);

            $this->conversations->sendSystemMessage(
                $conversation,
                __(':agent assumiu o atendimento.', ['agent' => $agent->name]),
            );

            $this->logs->record($conversation, 'agent_assigned', [
                'agent_id' => $agent->id,
            ]);

            return $conversation->fresh(['user', 'queue', 'assignedAgent']);
        });
    }

    public function transfer(SupportConversation $conversation, SupportQueue $queue, User $agent): SupportConversation
    {
        $conversation->update([
            'support_queue_id' => $queue->id,
            'status' => SupportConversationStatus::PendingHuman,
            'assigned_agent_id' => null,
            'bot_active' => false,
        ]);

        $this->conversations->sendSystemMessage(
            $conversation,
            __('Conversa transferida para :queue por :agent.', [
                'queue' => $queue->name,
                'agent' => $agent->name,
            ]),
        );

        $this->logs->record($conversation, 'queue_transferred', [
            'queue' => $queue->slug,
            'agent_id' => $agent->id,
        ]);

        return $conversation->fresh(['user', 'queue', 'assignedAgent']);
    }

    public function sendAgentMessage(SupportConversation $conversation, User $agent, string $body): SupportMessage
    {
        $message = $this->conversations->sendAgentMessage($conversation, $agent, $body);
        $this->conversations->markFirstResponse($conversation->fresh());

        if ($conversation->source_channel === \App\Enums\SupportSourceChannel::Whatsapp) {
            $phone = data_get($conversation->bot_context, 'whatsapp_phone');
            $this->whatsAppOutbound->sendToConversation(
                $conversation->fresh(),
                $body,
                is_string($phone) ? $phone : null,
            );
        }

        $this->logs->record($conversation, 'agent_message', [
            'message_id' => $message->id,
            'agent_id' => $agent->id,
        ]);

        return $message;
    }

    public function resolve(SupportConversation $conversation, User $agent): SupportConversation
    {
        $conversation->update([
            'status' => SupportConversationStatus::Resolved,
            'resolved_at' => now(),
            'bot_active' => false,
        ]);

        $this->conversations->sendSystemMessage(
            $conversation,
            __('Atendimento marcado como resolvido por :agent.', ['agent' => $agent->name]),
        );

        $this->logs->record($conversation, 'conversation_resolved', [
            'agent_id' => $agent->id,
        ]);

        return $conversation->fresh(['user', 'queue', 'assignedAgent']);
    }

    public function close(SupportConversation $conversation, User $agent): SupportConversation
    {
        $conversation->update([
            'status' => SupportConversationStatus::Closed,
            'resolved_at' => $conversation->resolved_at ?? now(),
            'bot_active' => false,
        ]);

        $this->conversations->sendSystemMessage(
            $conversation,
            __('Conversa encerrada por :agent.', ['agent' => $agent->name]),
        );

        $this->logs->record($conversation, 'conversation_closed', [
            'agent_id' => $agent->id,
        ]);

        return $conversation->fresh(['user', 'queue', 'assignedAgent']);
    }

    /**
     * @return Collection<int, SupportMessage>
     */
    public function messagesSince(SupportConversation $conversation, int $afterId): Collection
    {
        return $conversation->messages()
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderBy('created_at')
            ->get();
    }
}
