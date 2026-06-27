<?php

namespace App\Services\Chatbot;

use App\Enums\SupportConversationStatus;
use App\Enums\SupportSourceChannel;
use App\Models\ChatbotLog;
use App\Models\SupportConversation;
use App\Models\SupportQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatbotMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(int $days = 30): array
    {
        $since = now()->subDays($days);

        $conversations = SupportConversation::query()
            ->where('created_at', '>=', $since);

        $resolvedInPeriod = SupportConversation::query()
            ->where('resolved_at', '>=', $since);

        $firstResponseSamples = SupportConversation::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('first_response_at')
            ->get(['created_at', 'first_response_at']);

        $firstResponseAvg = $firstResponseSamples->isEmpty()
            ? null
            : round($firstResponseSamples->avg(
                fn (SupportConversation $c) => $c->created_at?->diffInMinutes($c->first_response_at) ?? 0
            ), 1);

        return [
            'days' => $days,
            'total_conversations' => (clone $conversations)->count(),
            'pending_human' => SupportConversation::query()
                ->where('status', SupportConversationStatus::PendingHuman)
                ->count(),
            'assigned' => SupportConversation::query()
                ->where('status', SupportConversationStatus::Assigned)
                ->count(),
            'resolved_period' => (clone $resolvedInPeriod)->count(),
            'avg_first_response_minutes' => $firstResponseAvg,
            'sla_breaches' => $this->slaBreachCount(),
            'by_status' => $this->countByStatus($since),
            'by_queue' => $this->countByQueue($since),
            'by_channel' => $this->countByChannel($since),
            'top_intents' => $this->topIntents($since),
            'ai_matches' => ChatbotLog::query()
                ->where('event', 'intent_ai_matched')
                ->where('created_at', '>=', $since)
                ->count(),
            'phrase_matches' => ChatbotLog::query()
                ->where('event', 'intent_matched')
                ->where('created_at', '>=', $since)
                ->count(),
            'handoffs' => ChatbotLog::query()
                ->where('event', 'handoff_requested')
                ->where('created_at', '>=', $since)
                ->count(),
        ];
    }

    private function slaBreachCount(): int
    {
        return SupportConversation::query()
            ->with('queue')
            ->whereNotIn('status', [
                SupportConversationStatus::Resolved,
                SupportConversationStatus::Closed,
            ])
            ->get()
            ->filter(function (SupportConversation $conversation) {
                $sla = (int) ($conversation->queue?->sla_first_response_minutes ?? 30);
                $created = $conversation->created_at ?? now();

                if ($conversation->first_response_at === null) {
                    return $created->diffInMinutes(now()) > $sla;
                }

                return $created->diffInMinutes($conversation->first_response_at) > $sla;
            })
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function countByStatus(\Illuminate\Support\Carbon $since): array
    {
        return SupportConversation::query()
            ->where('created_at', '>=', $since)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /**
     * @return Collection<int, object{slug: string, name: string, total: int}>
     */
    private function countByQueue(\Illuminate\Support\Carbon $since): Collection
    {
        return SupportQueue::query()
            ->withCount(['conversations as total' => fn ($q) => $q->where('created_at', '>=', $since)])
            ->orderByDesc('total')
            ->get(['id', 'slug', 'name']);
    }

    /**
     * @return array<string, int>
     */
    private function countByChannel(\Illuminate\Support\Carbon $since): array
    {
        return SupportConversation::query()
            ->where('created_at', '>=', $since)
            ->select('source_channel', DB::raw('COUNT(*) as total'))
            ->groupBy('source_channel')
            ->pluck('total', 'source_channel')
            ->mapWithKeys(function ($total, $channel) {
                $enum = $channel instanceof SupportSourceChannel
                    ? $channel
                    : SupportSourceChannel::from((string) $channel);

                return [$enum->label() => (int) $total];
            })
            ->all();
    }

    /**
     * @return Collection<int, object{intent: string, total: int}>
     */
    private function topIntents(\Illuminate\Support\Carbon $since): Collection
    {
        return ChatbotLog::query()
            ->whereIn('event', ['intent_matched', 'intent_ai_matched'])
            ->where('created_at', '>=', $since)
            ->get(['payload'])
            ->groupBy(fn ($log) => (string) data_get($log->payload, 'intent', 'unknown'))
            ->map(fn ($group, $intent) => (object) [
                'intent' => $intent,
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }
}
