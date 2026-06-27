<?php

namespace App\Http\Controllers;

use App\Services\Chatbot\ChatOrchestratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotWidgetController extends Controller
{
    public function __construct(
        private readonly ChatOrchestratorService $orchestrator,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $user = $request->user();
        $conversation = $this->orchestrator->bootstrap($user);

        return response()->json([
            'conversation' => $this->orchestrator->serializeConversation($conversation),
            'messages' => $this->orchestrator->messagesPayload($conversation),
        ]);
    }

    public function storeMessage(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $result = $this->orchestrator->handleUserMessage(
            $request->user(),
            $validated['body'],
        );

        return response()->json([
            'conversation' => $this->orchestrator->serializeConversation($result['conversation']),
            'messages' => array_filter([
                $this->orchestrator->serializeMessage($result['user_message']),
                $result['bot_message']
                    ? $this->orchestrator->serializeMessage($result['bot_message'])
                    : null,
            ]),
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $conversation = app(\App\Services\Chatbot\SupportConversationService::class)
            ->findOpenForUser($user);

        if ($conversation === null) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
            ]);
        }

        return response()->json([
            'conversation' => $this->orchestrator->serializeConversation($conversation),
            'messages' => $this->orchestrator->messagesPayload(
                $conversation,
                (int) ($validated['after_id'] ?? 0),
            ),
        ]);
    }

    private function ensureEnabled(): void
    {
        if (! config('psiconecta.chatbot.enabled') || ! config('psiconecta.chatbot.widget_enabled')) {
            abort(404);
        }
    }
}
