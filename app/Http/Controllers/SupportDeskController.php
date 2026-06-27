<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Models\SupportQueue;
use App\Services\Chatbot\ChatOrchestratorService;
use App\Services\Chatbot\SupportDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportDeskController extends Controller
{
    public function __construct(
        private readonly SupportDeskService $desk,
        private readonly ChatOrchestratorService $orchestrator,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SupportConversation::class);

        $filters = [
            'queue' => $request->integer('queue') ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'mine' => $request->boolean('mine'),
            'q' => $request->string('q')->trim()->toString(),
        ];

        $inbox = $this->desk->inbox(
            $filters['queue'],
            $filters['status'],
            $filters['mine'],
            $request->user(),
            $filters['q'] !== '' ? $filters['q'] : null,
        );

        return view('support-desk.index', [
            'inbox' => $inbox,
            'queues' => SupportQueue::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'filters' => $filters,
            'pendingCount' => $this->desk->pendingCount(),
            'activeConversation' => null,
            'messages' => collect(),
        ]);
    }

    public function show(Request $request, SupportConversation $supportConversation): View
    {
        $this->authorize('view', $supportConversation);

        $filters = [
            'queue' => $request->integer('queue') ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'mine' => $request->boolean('mine'),
            'q' => $request->string('q')->trim()->toString(),
        ];

        $supportConversation->load(['user', 'queue', 'assignedAgent']);
        $messages = $supportConversation->messages()->with('sender')->get();

        $inbox = $this->desk->inbox(
            $filters['queue'],
            $filters['status'],
            $filters['mine'],
            $request->user(),
            $filters['q'] !== '' ? $filters['q'] : null,
        );

        return view('support-desk.index', [
            'inbox' => $inbox,
            'queues' => SupportQueue::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'filters' => $filters,
            'pendingCount' => $this->desk->pendingCount(),
            'activeConversation' => $supportConversation,
            'messages' => $messages,
            'sla' => $this->desk->slaMeta($supportConversation),
        ]);
    }

    public function poll(Request $request, SupportConversation $supportConversation): JsonResponse
    {
        $this->authorize('view', $supportConversation);

        $validated = $request->validate([
            'after_id' => ['required', 'integer', 'min:0'],
        ]);

        $messages = $this->desk->messagesSince($supportConversation, (int) $validated['after_id']);

        return response()->json([
            'conversation' => $this->orchestrator->serializeConversation($supportConversation->fresh(['queue'])),
            'messages' => $messages->map(fn ($message) => $this->orchestrator->serializeMessage($message))->values(),
        ]);
    }

    public function assign(Request $request, SupportConversation $supportConversation): RedirectResponse
    {
        $this->authorize('assign', $supportConversation);

        $this->desk->assign($supportConversation, $request->user());

        return back()->with('status', __('Conversa assumida com sucesso.'));
    }

    public function storeMessage(Request $request, SupportConversation $supportConversation): RedirectResponse|JsonResponse
    {
        $this->authorize('message', $supportConversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $message = $this->desk->sendAgentMessage(
            $supportConversation,
            $request->user(),
            $validated['body'],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->orchestrator->serializeMessage($message),
            ]);
        }

        return back()->with('status', __('Mensagem enviada.'));
    }

    public function transfer(Request $request, SupportConversation $supportConversation): RedirectResponse
    {
        $this->authorize('transfer', $supportConversation);

        $validated = $request->validate([
            'support_queue_id' => ['required', 'exists:support_queues,id'],
        ]);

        $queue = SupportQueue::query()->findOrFail($validated['support_queue_id']);
        $this->desk->transfer($supportConversation, $queue, $request->user());

        return back()->with('status', __('Conversa transferida para :queue.', ['queue' => $queue->name]));
    }

    public function resolve(Request $request, SupportConversation $supportConversation): RedirectResponse
    {
        $this->authorize('resolve', $supportConversation);

        $this->desk->resolve($supportConversation, $request->user());

        return redirect()
            ->route('admin.support.index')
            ->with('status', __('Atendimento marcado como resolvido.'));
    }

    public function close(Request $request, SupportConversation $supportConversation): RedirectResponse
    {
        $this->authorize('close', $supportConversation);

        $this->desk->close($supportConversation, $request->user());

        return redirect()
            ->route('admin.support.index')
            ->with('status', __('Conversa encerrada.'));
    }
}
