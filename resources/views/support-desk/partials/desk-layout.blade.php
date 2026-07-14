@php
    $user = auth()->user();
    $activeConversation = $activeConversation ?? null;
    $messages = $messages ?? collect();
    $sla = $sla ?? null;
@endphp

<div class="grid min-h-[32rem] gap-6 lg:grid-cols-12 lg:items-stretch">
    <aside class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg ring-1 ring-violet-100/70 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-violet-900/40 lg:col-span-4 {{ $activeConversation ? 'hidden lg:flex' : 'flex' }}">
        <div class="border-b border-slate-100 bg-gradient-to-r from-violet-600/10 via-indigo-600/5 to-transparent px-5 py-4 dark:border-slate-700">
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-800 dark:text-slate-100">{{ __('Fila de atendimento') }}</h2>

            <form method="get" action="{{ $activeConversation ? route('admin.support.show', array_merge(['supportConversation' => $activeConversation], $filterParams)) : route('admin.support.index', $filterParams) }}" class="mt-3 space-y-2">
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="{{ __('Protocolo ou nome…') }}"
                    class="block w-full rounded-xl border-slate-200 bg-white py-2 px-3 text-xs font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:border-violet-500 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                />

                <div class="grid grid-cols-2 gap-2">
                    <select name="queue" class="rounded-xl border-slate-200 bg-white py-2 px-2 text-xs dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">{{ __('Todas filas') }}</option>
                        @foreach ($queues as $queue)
                            <option value="{{ $queue->id }}" @selected(($filters['queue'] ?? null) == $queue->id)>{{ $queue->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="rounded-xl border-slate-200 bg-white py-2 px-2 text-xs dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100">
                        <option value="">{{ __('Activas') }}</option>
                        <option value="pending_human" @selected(($filters['status'] ?? '') === 'pending_human')>{{ __('Aguarda atendente') }}</option>
                        <option value="assigned" @selected(($filters['status'] ?? '') === 'assigned')>{{ __('Em atendimento') }}</option>
                        <option value="open" @selected(($filters['status'] ?? '') === 'open')>{{ __('Abertas') }}</option>
                        <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>{{ __('Resolvidas') }}</option>
                    </select>
                </div>

                <label class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300">
                    <input type="checkbox" name="mine" value="1" @checked($filters['mine'] ?? false) class="rounded border-slate-300 text-violet-600 focus:ring-violet-500" />
                    {{ __('Só minhas conversas') }}
                </label>

                <button type="submit" class="w-full rounded-xl bg-violet-600 py-2 text-xs font-bold text-white hover:bg-violet-500">{{ __('Filtrar') }}</button>
            </form>
        </div>

        <ul class="flex-1 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-700/80" role="list">
            @forelse ($inbox as $conversation)
                @php
                    $isActive = $activeConversation?->id === $conversation->id;
                    $itemSla = app(\App\Services\Chatbot\SupportDeskService::class)->slaMeta($conversation);
                @endphp
                <li>
                    <a
                        href="{{ route('admin.support.show', array_merge(['supportConversation' => $conversation], $filterParams)) }}"
                        @class([
                            'block px-4 py-3 transition',
                            'bg-violet-50/80 ring-1 ring-inset ring-violet-200/60 dark:bg-violet-950/30 dark:ring-violet-800/40' => $isActive,
                            'hover:bg-slate-50 dark:hover:bg-slate-800/60' => ! $isActive,
                        ])
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ $conversation->user?->name ?? __('Visitante WhatsApp') }}</p>
                                <p class="text-[10px] font-semibold text-violet-600 dark:text-violet-300">{{ $conversation->protocol_number }}</p>
                            </div>
                            <span class="shrink-0 text-[10px] text-slate-400">{{ $conversation->updated_at?->diffForHumans(short: true) }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $conversation->status->label() }}</span>
                            @if ($conversation->queue)
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-semibold text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200">{{ $conversation->queue->name }}</span>
                            @endif
                            @if ($itemSla['first_response'] !== 'ok')
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $slaBadge($itemSla['first_response']) }}">SLA</span>
                            @endif
                        </div>
                    </a>
                </li>
            @empty
                <li class="px-6 py-16 text-center text-sm text-slate-500 dark:text-slate-400">{{ __('Nenhuma conversa na fila.') }}</li>
            @endforelse
        </ul>
    </aside>

    <section @class([
        'flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg ring-1 ring-violet-100/70 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-violet-900/40 lg:col-span-8',
        'hidden lg:flex' => ! $activeConversation,
        'flex' => $activeConversation,
    ])>
        @if ($activeConversation)
            @php
                $lastMessageId = $messages->last()?->id ?? 0;
                $canMessage = $user->can('message', $activeConversation);
                $canAssign = $user->can('assign', $activeConversation)
                    && ($activeConversation->assigned_agent_id === null || $activeConversation->assigned_agent_id === $user->id);
            @endphp

            <header class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.support.index', $filterParams) }}" class="lg:hidden rounded-lg p-1 text-slate-500 hover:bg-slate-100" aria-label="{{ __('Voltar') }}">
                            <x-ui.icon name="arrow-left" class="h-5 w-5" />
                        </a>
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 text-sm font-bold text-white">
                            {{ $initials($activeConversation->user?->name ?? '?') }}
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ $activeConversation->user?->name ?? __('Visitante WhatsApp') }}</h2>
                            <p class="text-xs text-slate-500">{{ $activeConversation->protocol_number }} · {{ $activeConversation->source_channel->label() }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if ($canAssign)
                            <form method="post" action="{{ route('admin.support.assign', $activeConversation) }}">
                                @csrf
                                <button type="submit" class="rounded-xl bg-violet-600 px-3 py-2 text-xs font-bold text-white hover:bg-violet-500">{{ __('Assumir') }}</button>
                            </form>
                        @endif

                        @if ($user->can('transfer', $activeConversation))
                            <form method="post" action="{{ route('admin.support.transfer', $activeConversation) }}" class="flex gap-1">
                                @csrf
                                <select name="support_queue_id" class="rounded-xl border-slate-200 py-2 pl-2 pr-7 text-xs dark:border-slate-600 dark:bg-slate-950" required>
                                    @foreach ($queues as $queue)
                                        <option value="{{ $queue->id }}" @selected($activeConversation->support_queue_id === $queue->id)>{{ $queue->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200">{{ __('Transferir') }}</button>
                            </form>
                        @endif

                        @if ($user->can('resolve', $activeConversation))
                            <form method="post" action="{{ route('admin.support.resolve', $activeConversation) }}" onsubmit="return confirm(@js(__('Marcar como resolvido?')))">
                                @csrf
                                <button type="submit" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-500">{{ __('Resolver') }}</button>
                            </form>
                        @endif
                    </div>
                </div>

                @if ($sla)
                    <div class="mt-3 flex flex-wrap gap-2 text-[10px] font-semibold">
                        <span class="rounded-full px-2 py-0.5 ring-1 {{ $slaBadge($sla['first_response']) }}">
                            {{ __('1.ª resposta') }}: {{ $sla['first_sla_minutes'] }} min
                        </span>
                        <span class="rounded-full px-2 py-0.5 ring-1 {{ $slaBadge($sla['resolution']) }}">
                            {{ __('Resolução') }}: {{ $sla['resolution_sla_minutes'] }} min
                        </span>
                        @if ($activeConversation->assignedAgent)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ __('Atendente') }}: {{ $activeConversation->assignedAgent->name }}
                            </span>
                        @endif
                    </div>
                @endif

                @if ($activeConversation->ai_summary)
                    <div class="mt-3 rounded-xl border border-violet-200/80 bg-violet-50/80 px-3 py-2 text-xs text-violet-900 dark:border-violet-800 dark:bg-violet-950/30 dark:text-violet-100">
                        <p class="font-bold">{{ __('Resumo IA') }}</p>
                        <p class="mt-1">{{ $activeConversation->ai_summary }}</p>
                        @if ($activeConversation->sentiment_score !== null)
                            <p class="mt-1 text-[10px] opacity-80">{{ __('Sentimento') }}: {{ number_format((float) $activeConversation->sentiment_score, 2) }}</p>
                        @endif
                    </div>
                @endif
            </header>

            <div
                id="support-desk-messages"
                class="flex max-h-[28rem] min-h-[20rem] flex-1 flex-col gap-2 overflow-y-auto p-4"
                data-poll-url="{{ route('admin.support.poll', $activeConversation) }}"
                data-last-id="{{ $lastMessageId }}"
            >
                @foreach ($messages as $message)
                    @php
                        $isUser = $message->sender_type->value === 'user';
                        $isAgent = $message->sender_type->value === 'agent';
                        $isSystem = $message->sender_type->value === 'system';
                    @endphp
                    <div @class([
                        'max-w-[85%] rounded-2xl px-3 py-2 text-sm',
                        'ms-auto bg-violet-600 text-white' => $isUser,
                        'bg-emerald-100 text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100' => $isAgent,
                        'mx-auto bg-slate-100 text-center text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300' => $isSystem,
                        'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100' => ! $isUser && ! $isAgent && ! $isSystem,
                    ])>
                        @unless($isSystem)
                            <p class="mb-0.5 text-[10px] font-bold opacity-70">{{ $message->sender_type->label() }}</p>
                        @endunless
                        <p class="whitespace-pre-wrap">{{ $message->body }}</p>
                    </div>
                @endforeach
            </div>

            @if ($canMessage)
                <form method="post" action="{{ route('admin.support.messages.store', $activeConversation) }}" class="border-t border-slate-100 p-4 dark:border-slate-700">
                    @csrf
                    <div class="flex gap-2">
                        <input
                            type="text"
                            name="body"
                            required
                            maxlength="4000"
                            placeholder="{{ __('Responder ao utilizador…') }}"
                            class="min-w-0 flex-1 rounded-xl border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        />
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-500">{{ __('Enviar') }}</button>
                    </div>
                </form>
            @elseif ($activeConversation->isOpen())
                <p class="border-t border-slate-100 px-4 py-3 text-center text-xs text-slate-500 dark:border-slate-700">{{ __('Assuma a conversa para responder.') }}</p>
            @endif

            @push('scripts')
                <script>
                    (function () {
                        const box = document.getElementById('support-desk-messages');
                        if (! box) return;
                        const pollUrl = box.dataset.pollUrl;
                        let lastId = parseInt(box.dataset.lastId || '0', 10);

                        setInterval(async () => {
                            try {
                                const res = await fetch(`${pollUrl}?after_id=${lastId}`, {
                                    headers: { Accept: 'application/json' },
                                });
                                if (! res.ok) return;
                                const data = await res.json();
                                for (const msg of data.messages ?? []) {
                                    if (msg.id <= lastId) continue;
                                    lastId = msg.id;
                                    const div = document.createElement('div');
                                    const isUser = msg.sender_type === 'user';
                                    const isAgent = msg.sender_type === 'agent';
                                    const isSystem = msg.sender_type === 'system';
                                    div.className = isUser
                                        ? 'max-w-[85%] ms-auto rounded-2xl bg-violet-600 px-3 py-2 text-sm text-white'
                                        : isAgent
                                            ? 'max-w-[85%] rounded-2xl bg-emerald-100 px-3 py-2 text-sm text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100'
                                            : isSystem
                                                ? 'mx-auto max-w-[85%] rounded-2xl bg-slate-100 px-3 py-2 text-center text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                                                : 'max-w-[85%] rounded-2xl bg-slate-100 px-3 py-2 text-sm text-slate-800 dark:bg-slate-800 dark:text-slate-100';
                                    div.innerHTML = `<p class="whitespace-pre-wrap">${msg.body}</p>`;
                                    box.appendChild(div);
                                }
                                if ((data.messages ?? []).length) {
                                    box.scrollTop = box.scrollHeight;
                                }
                            } catch (e) {}
                        }, 5000);
                    })();
                </script>
            @endpush
        @else
            <div class="flex flex-1 flex-col items-center justify-center gap-2 p-12 text-center text-slate-500 dark:text-slate-400">
                <x-ui.icon name="messages" class="h-12 w-12 opacity-40" />
                <p class="text-sm font-semibold">{{ __('Seleccione uma conversa na fila') }}</p>
            </div>
        @endif
    </section>
</div>
