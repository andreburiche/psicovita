@php
    $lastMessageId = $messages->last()?->id ?? 0;
@endphp

<div
    class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg ring-1 ring-violet-100/70 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-violet-900/40"
    x-data="patientSupportChat({
        sendUrl: @js(route('conversations.support.messages.store')),
        pollUrl: @js(route('conversations.support.poll')),
        csrf: @js(csrf_token()),
        lastId: @js($lastMessageId),
    })"
>
    <div class="border-b border-slate-100 bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-4 text-white dark:border-slate-700">
        <p class="text-sm font-bold">{{ __('Apoio PsiConecta') }}</p>
        <p class="text-xs text-white/80">
            {{ __('Protocolo') }}: {{ $conversation->protocol_number }}
            · {{ $conversation->status->label() }}
            @if ($conversation->queue)
                · {{ $conversation->queue->name }}
            @endif
        </p>
        <p class="mt-1 text-[10px] text-white/70" x-show="conversation?.bot_active === false">
            {{ __('Um atendente humano está ou estará a tratar do seu pedido.') }}
        </p>
    </div>

    <div class="flex max-h-[28rem] min-h-[20rem] flex-col gap-2 overflow-y-auto p-4" x-ref="messages" role="log" aria-live="polite">
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
        <template x-for="message in newMessages" :key="message.id">
            <div
                class="max-w-[85%] rounded-2xl px-3 py-2 text-sm"
                :class="message.sender_type === 'user'
                    ? 'ms-auto bg-violet-600 text-white'
                    : message.sender_type === 'agent'
                        ? 'bg-emerald-100 text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100'
                        : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'"
            >
                <p class="whitespace-pre-wrap" x-text="message.body"></p>
            </div>
        </template>
    </div>

    <form @submit.prevent="send()" class="border-t border-slate-100 p-4 dark:border-slate-700">
        <div class="flex gap-2">
            <input
                type="text"
                x-model="draft"
                :disabled="sending"
                required
                maxlength="4000"
                placeholder="{{ __('Digite sua mensagem…') }}"
                class="min-w-0 flex-1 rounded-xl border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
            />
            <button
                type="submit"
                :disabled="sending || draft.trim() === ''"
                class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-bold text-white hover:bg-violet-500 disabled:opacity-50"
            >
                {{ __('Enviar') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('patientSupportChat', (config) => ({
                draft: '',
                sending: false,
                conversation: @js($conversationData),
                newMessages: [],
                lastId: config.lastId,
                pollTimer: null,
                ...config,
                init() {
                    this.startPoll();
                },
                async send() {
                    const body = this.draft.trim();
                    if (! body || this.sending) return;
                    this.sending = true;
                    try {
                        const res = await fetch(this.sendUrl, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body: JSON.stringify({ body }),
                        });
                        if (! res.ok) return;
                        const data = await res.json();
                        this.conversation = data.conversation;
                        for (const msg of data.messages ?? []) {
                            if (msg && msg.id > this.lastId) {
                                this.newMessages.push(msg);
                                this.lastId = msg.id;
                            }
                        }
                        this.draft = '';
                        this.$nextTick(() => this.scrollBottom());
                    } finally {
                        this.sending = false;
                    }
                },
                startPoll() {
                    this.pollTimer = setInterval(() => this.poll(), 4000);
                },
                async poll() {
                    const res = await fetch(`${this.pollUrl}?after_id=${this.lastId}`, {
                        headers: { Accept: 'application/json' },
                    });
                    if (! res.ok) return;
                    const data = await res.json();
                    if (data.conversation) this.conversation = data.conversation;
                    for (const msg of data.messages ?? []) {
                        if (msg.id > this.lastId) {
                            this.newMessages.push(msg);
                            this.lastId = msg.id;
                        }
                    }
                    if ((data.messages ?? []).length) {
                        this.$nextTick(() => this.scrollBottom());
                    }
                },
                scrollBottom() {
                    const el = this.$refs.messages;
                    if (el) el.scrollTop = el.scrollHeight;
                },
            }));
        });
    </script>
@endpush
