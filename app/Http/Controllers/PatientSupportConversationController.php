<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Services\Chatbot\ChatOrchestratorService;
use App\Services\Chatbot\SupportConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientSupportConversationController extends Controller
{
    public function __construct(
        private readonly ChatOrchestratorService $orchestrator,
        private readonly SupportConversationService $conversations,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureEnabled();

        $user = $request->user();
        $conversation = $this->conversations->findOpenForUser($user);

        if ($conversation === null) {
            $conversation = $this->orchestrator->bootstrap($user);
        } else {
            $conversation->load(['queue', 'messages']);
        }

        return view('conversations.support', [
            'conversation' => $conversation,
            'conversationData' => $this->orchestrator->serializeConversation($conversation),
            'messages' => $conversation->messages,
            'patientPortal' => $user->usesPatientPortalExperience(),
        ]);
    }

    public function storeMessage(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $user = $request->user();
        $result = $this->orchestrator->handleUserMessage($user, $validated['body']);

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
        $conversation = $this->conversations->findOpenForUser($user);

        if ($conversation === null) {
            return response()->json([
                'conversation' => null,
                'messages' => [],
            ]);
        }

        $this->authorize('view', $conversation);

        return response()->json([
            'conversation' => $this->orchestrator->serializeConversation($conversation->fresh(['queue'])),
            'messages' => $this->orchestrator->messagesPayload(
                $conversation,
                (int) ($validated['after_id'] ?? 0),
            ),
        ]);
    }

    private function ensureEnabled(): void
    {
        abort_unless(
            config('psiconecta.chatbot.enabled') && config('psiconecta.chatbot.widget_enabled'),
            404,
        );
    }
}
