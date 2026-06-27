<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotFlow;
use App\Models\ChatbotIntent;
use App\Models\ChatbotResponse;
use App\Models\SupportQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatbotIntentAdminController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        abort_unless(config('psiconecta.chatbot.enabled', true), 404);

        $flow = $this->defaultFlow();
        $intents = ChatbotIntent::query()
            ->where('chatbot_flow_id', $flow->id)
            ->with(['targetQueue', 'responses'])
            ->orderByDesc('priority')
            ->orderBy('label')
            ->get();

        $queues = SupportQueue::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.chatbot.intents', compact('flow', 'intents', 'queues'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $flow = $this->defaultFlow();
        $validated = $this->validateIntent($request);

        $intent = ChatbotIntent::query()->create([
            'chatbot_flow_id' => $flow->id,
            'slug' => $validated['slug'],
            'label' => $validated['label'],
            'training_phrases' => $validated['training_phrases'],
            'route_action' => $validated['route_action'],
            'target_queue_id' => $validated['target_queue_id'],
            'priority' => $validated['priority'],
            'is_active' => true,
        ]);

        $this->syncResponse($intent, $validated['body_template'], $validated['quick_replies']);

        return redirect()
            ->route('admin.chatbot.intents.index')
            ->with('status', __('Intent criado.'));
    }

    public function update(Request $request, ChatbotIntent $chatbotIntent): RedirectResponse
    {
        $this->ensureAdmin($request);

        $validated = $this->validateIntent($request, $chatbotIntent);

        $chatbotIntent->update([
            'slug' => $validated['slug'],
            'label' => $validated['label'],
            'training_phrases' => $validated['training_phrases'],
            'route_action' => $validated['route_action'],
            'target_queue_id' => $validated['target_queue_id'],
            'priority' => $validated['priority'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncResponse($chatbotIntent, $validated['body_template'], $validated['quick_replies']);

        return redirect()
            ->route('admin.chatbot.intents.index')
            ->with('status', __('Intent atualizado.'));
    }

    public function destroy(Request $request, ChatbotIntent $chatbotIntent): RedirectResponse
    {
        $this->ensureAdmin($request);

        $chatbotIntent->responses()->delete();
        $chatbotIntent->delete();

        return redirect()
            ->route('admin.chatbot.intents.index')
            ->with('status', __('Intent removido.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateIntent(Request $request, ?ChatbotIntent $intent = null): array
    {
        $flow = $this->defaultFlow();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
            'training_phrases' => ['required', 'string', 'max:5000'],
            'route_action' => ['required', 'in:reply,handoff'],
            'target_queue_id' => ['nullable', 'exists:support_queues,id'],
            'priority' => ['required', 'integer', 'min:0', 'max:999'],
            'body_template' => ['required', 'string', 'max:2000'],
            'quick_replies' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = Str::lower($validated['slug']);
        $slugTaken = ChatbotIntent::query()
            ->where('chatbot_flow_id', $flow->id)
            ->where('slug', $slug)
            ->when($intent, fn ($q) => $q->where('id', '!=', $intent->id))
            ->exists();

        if ($slugTaken) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'slug' => __('Este slug já existe neste fluxo.'),
            ]);
        }

        $phrases = collect(preg_split('/\r\n|\r|\n/', $validated['training_phrases']) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();

        if ($phrases === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'training_phrases' => __('Informe pelo menos uma frase de treino.'),
            ]);
        }

        if ($validated['route_action'] === 'handoff' && empty($validated['target_queue_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'target_queue_id' => __('Selecione a fila de destino para handoff.'),
            ]);
        }

        $quickReplies = collect(explode(',', (string) ($validated['quick_replies'] ?? '')))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values()
            ->all();

        return [
            'slug' => $slug,
            'label' => $validated['label'],
            'training_phrases' => $phrases,
            'route_action' => $validated['route_action'],
            'target_queue_id' => $validated['route_action'] === 'handoff' ? $validated['target_queue_id'] : null,
            'priority' => (int) $validated['priority'],
            'body_template' => $validated['body_template'],
            'quick_replies' => $quickReplies,
        ];
    }

    private function syncResponse(ChatbotIntent $intent, string $body, array $quickReplies): void
    {
        ChatbotResponse::query()->updateOrCreate(
            [
                'chatbot_intent_id' => $intent->id,
                'locale' => 'pt_BR',
            ],
            [
                'body_template' => $body,
                'quick_replies' => $quickReplies !== [] ? $quickReplies : null,
            ],
        );
    }

    private function defaultFlow(): ChatbotFlow
    {
        return ChatbotFlow::query()->firstOrCreate(
            ['slug' => 'support-default'],
            ['name' => __('Atendimento geral'), 'is_active' => true],
        );
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
