<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotIntent;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Services\AiAssistantService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConversationAiService
{
    private const MIN_INTENT_CONFIDENCE = 0.6;

    public function __construct(
        private readonly AiAssistantService $ai,
        private readonly ChatbotLogService $logs,
    ) {}

    public function isEnabled(): bool
    {
        return config('psiconecta.chatbot.ai_enabled', false)
            && config('psiconecta.chatbot.enabled', true)
            && $this->ai->llmChatEndpointReady();
    }

    public function matchIntent(string $body): ?ChatbotIntent
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $body = trim($body);
        if ($body === '') {
            return null;
        }

        $intents = $this->activeIntents();
        if ($intents->isEmpty()) {
            return null;
        }

        try {
            $result = $this->ai->completeChat(
                $this->classificationSystemPrompt(),
                $this->classificationUserPrompt($body, $intents),
                maxTokens: 120,
                temperature: 0.1,
            );

            $parsed = $this->parseJsonResponse($result['text']);
            if ($parsed === null) {
                return null;
            }

            $slug = isset($parsed['intent_slug']) ? (string) $parsed['intent_slug'] : '';
            $confidence = is_numeric($parsed['confidence'] ?? null) ? (float) $parsed['confidence'] : 0.0;

            if ($slug === '' || $slug === 'null' || $confidence < self::MIN_INTENT_CONFIDENCE) {
                return null;
            }

            $intent = ChatbotIntent::query()
                ->with(['responses', 'targetQueue'])
                ->whereKey($intents->firstWhere('slug', $slug)?->id)
                ->first();

            if ($intent === null) {
                return null;
            }

            $intent->setAttribute('ai_confidence', $confidence);

            return $intent;
        } catch (Throwable $e) {
            Log::warning('Chatbot AI intent classification failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function refreshInsights(SupportConversation $conversation): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $messages = $conversation->messages()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->sortBy('created_at')
            ->values();

        if ($messages->count() < 2) {
            return;
        }

        try {
            $result = $this->ai->completeChat(
                $this->insightsSystemPrompt(),
                $this->insightsUserPrompt($messages),
                maxTokens: 300,
                temperature: 0.2,
            );

            $parsed = $this->parseJsonResponse($result['text']);
            if ($parsed === null) {
                return;
            }

            $summary = isset($parsed['summary']) ? trim((string) $parsed['summary']) : '';
            $sentiment = is_numeric($parsed['sentiment_score'] ?? null)
                ? max(-1.0, min(1.0, (float) $parsed['sentiment_score']))
                : null;

            $context = $conversation->bot_context ?? [];
            $context['ai'] = array_merge($context['ai'] ?? [], [
                'last_insights_at' => now()->toIso8601String(),
                'tokens_used' => $result['tokens_used'] ?? null,
            ]);

            $conversation->update([
                'ai_summary' => $summary !== '' ? $summary : $conversation->ai_summary,
                'sentiment_score' => $sentiment ?? $conversation->sentiment_score,
                'bot_context' => $context,
            ]);

            $this->logs->record($conversation, 'ai_insights_refreshed', [
                'sentiment_score' => $sentiment,
                'tokens_used' => $result['tokens_used'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Chatbot AI insights refresh failed', [
                'conversation_id' => $conversation->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return Collection<int, ChatbotIntent>
     */
    private function activeIntents(): Collection
    {
        return ChatbotIntent::query()
            ->where('is_active', true)
            ->whereHas('flow', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('priority')
            ->get(['id', 'slug', 'label', 'training_phrases', 'route_action', 'target_queue_id', 'priority', 'is_active', 'chatbot_flow_id']);
    }

    private function classificationSystemPrompt(): string
    {
        return <<<'PROMPT'
Você classifica mensagens de suporte ao cliente do PsiConecta.
Responda APENAS com JSON válido no formato:
{"intent_slug":"slug_ou_null","confidence":0.0}
Use intent_slug null e confidence baixa se não tiver certeza.
confidence deve ser entre 0 e 1.
Não inclua markdown nem texto extra.
PROMPT;
    }

    /**
     * @param  Collection<int, ChatbotIntent>  $intents
     */
    private function classificationUserPrompt(string $body, Collection $intents): string
    {
        $catalog = $intents->map(function (ChatbotIntent $intent) {
            $phrases = collect($intent->training_phrases)->take(5)->implode(', ');

            return sprintf(
                '- %s (%s): exemplos: %s',
                $intent->slug,
                $intent->label,
                $phrases,
            );
        })->implode("\n");

        return "Intents disponíveis:\n{$catalog}\n\nMensagem do utilizador:\n{$body}";
    }

    private function insightsSystemPrompt(): string
    {
        return <<<'PROMPT'
Você analisa conversas de suporte do PsiConecta para atendentes humanos.
Responda APENAS com JSON válido:
{"summary":"resumo em português (máx. 2 frases)","sentiment_score":0.0}
sentiment_score de -1 (muito negativo) a 1 (muito positivo).
Não inclua markdown nem texto extra.
PROMPT;
    }

    /**
     * @param  Collection<int, SupportMessage>  $messages
     */
    private function insightsUserPrompt(Collection $messages): string
    {
        $lines = $messages->map(function (SupportMessage $message) {
            return sprintf(
                '[%s] %s: %s',
                $message->created_at?->format('H:i') ?? '--:--',
                $message->sender_type->label(),
                $message->body,
            );
        })->implode("\n");

        return "Transcrição recente:\n{$lines}";
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonResponse(string $text): ?array
    {
        $text = trim($text);

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
            $text = trim($matches[1]);
        }

        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }
}
