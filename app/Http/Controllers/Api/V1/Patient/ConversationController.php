<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConversationController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Conversation::class);
        $inbox = $this->conversations->inboxFor($request->user());

        return response()->json([
            'data' => $inbox->map(fn (Conversation $c) => $this->serializeConversation($c, $request->user()))->values(),
        ]);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);
        $this->conversations->markAsRead($conversation, $request->user());

        $messages = $this->conversations->paginateMessages($conversation);

        return response()->json([
            'data' => [
                'conversation' => $this->serializeConversation($conversation, $request->user()),
                'messages' => $messages->getCollection()->map(
                    fn ($m) => $this->serializeMessage($m, $request->user())
                )->values(),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ],
            ],
        ]);
    }

    public function storeMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:8000'],
        ]);

        $message = $this->conversations->sendMessage(
            $conversation,
            $request->user(),
            $validated['body'],
        );

        return response()->json([
            'data' => $this->serializeMessage($message, $request->user()),
        ], 201);
    }

  public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'after_id' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $newMessages = $this->conversations->messagesSince($conversation, (int) $validated['after_id']);

        if ($newMessages->contains(fn ($m) => $m->recipient_id === $user->id)) {
            $this->conversations->markAsRead($conversation, $user);
        }

        return response()->json([
            'data' => $newMessages->map(fn ($m) => $this->serializeMessage($m, $user))->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConversation(Conversation $conversation, \App\Models\User $user): array
    {
        $conversation->loadMissing(['professional', 'patientUser', 'latestMessage']);

        return [
            'id' => $conversation->id,
            'title' => $user->isPatient()
                ? ($conversation->professional?->name ?? __('Terapeuta'))
                : ($conversation->patientUser?->name ?? $conversation->patient?->name ?? __('Paciente')),
            'unread_count' => $conversation->unreadCountFor($user),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'preview' => $conversation->latestMessage?->body,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(\App\Models\Message $message, \App\Models\User $user): array
    {
        $message->loadMissing('sender');

        return [
            'id' => $message->id,
            'body' => $message->body,
            'mine' => $message->isFrom($user),
            'sender_name' => $message->sender?->name,
            'created_at' => $message->created_at?->toIso8601String(),
            'read_at' => $message->read_at?->toIso8601String(),
        ];
    }
}
