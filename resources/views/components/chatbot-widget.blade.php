@if (config('psiconecta.chatbot.enabled') && config('psiconecta.chatbot.widget_enabled') && auth()->check())
    <div
        class="fixed bottom-5 end-5 z-50"
        x-data="chatbotWidget({
            showUrl: @js(route('chatbot.widget.show')),
            sendUrl: @js(route('chatbot.widget.messages.store')),
            pollUrl: @js(route('chatbot.widget.poll')),
            csrf: @js(csrf_token()),
        })"
        x-cloak
    >
        <button
            type="button"
            @click="toggle()"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-violet-600 to-indigo-600 text-white shadow-xl shadow-violet-600/30 transition hover:scale-105 focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-2"
            :aria-expanded="open"
            aria-controls="chatbot-widget-panel"
            aria-label="{{ __('Abrir assistente') }}"
        >
            <x-ui.icon name="messages" class="h-6 w-6" />
        </button>

        <div
            id="chatbot-widget-panel"
            x-show="open"
            x-transition
            class="absolute bottom-16 end-0 flex w-[min(100vw-2rem,22rem)] flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            style="display: none;"
            role="dialog"
            aria-label="{{ __('Assistente PsiConecta') }}"
        >
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3 text-white dark:border-slate-700">
                <div class="min-w-0">
                    <p class="text-sm font-bold">{{ __('Apoio PsiConecta') }}</p>
                    <p class="truncate text-[10px] text-white/80" x-text="conversation?.protocol_number ? @js(__('Protocolo')) + ': ' + conversation.protocol_number : ''"></p>
                </div>
                <button type="button" @click="open = false" class="rounded-lg p-1 hover:bg-white/10" aria-label="{{ __('Fechar') }}">
                    <x-ui.icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2 text-[10px] dark:border-slate-700 dark:bg-slate-800/80">
                <span class="inline-flex h-2 w-2 rounded-full" :class="conversation?.bot_active ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                <span class="text-slate-600 dark:text-slate-300" x-text="conversation?.bot_active ? @js(__('Assistente virtual activo')) : @js(__('Aguarda atendente humano'))"></span>
            </div>

            <div class="flex max-h-80 min-h-64 flex-col gap-2 overflow-y-auto p-3" x-ref="messages" role="log" aria-live="polite">
                <template x-for="message in messages" :key="message.id">
                    <div
                        class="max-w-[90%] rounded-2xl px-3 py-2 text-sm"
                        :class="message.sender_type === 'user'
                            ? 'ms-auto bg-violet-600 text-white'
                            : message.sender_type === 'agent'
                                ? 'bg-emerald-100 text-emerald-950 dark:bg-emerald-950/40 dark:text-emerald-100'
                                : 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'"
                    >
                        <p class="whitespace-pre-wrap" x-text="message.body"></p>
                    </div>
                </template>
                <p x-show="loading" class="text-center text-xs text-slate-400">{{ __('A carregar…') }}</p>
            </div>

            <form @submit.prevent="send()" class="border-t border-slate-100 p-3 dark:border-slate-700">
                <div class="flex gap-2">
                    <label class="sr-only" for="chatbot-input">{{ __('Mensagem') }}</label>
                    <input
                        id="chatbot-input"
                        type="text"
                        x-model="draft"
                        :disabled="sending"
                        class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800"
                        placeholder="{{ __('Digite sua mensagem…') }}"
                        autocomplete="off"
                    />
                    <button
                        type="submit"
                        :disabled="sending || draft.trim() === ''"
                        class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-2 text-white transition hover:bg-emerald-500 disabled:opacity-50"
                    >
                        <x-ui.icon name="paper-airplane" class="h-4 w-4" />
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('chatbotWidget', (config) => ({
                    open: false,
                    loading: false,
                    sending: false,
                    draft: '',
                    messages: [],
                    conversation: null,
                    lastId: 0,
                    pollTimer: null,
                    ...config,
                    toggle() {
                        this.open = ! this.open;
                        if (this.open) {
                            this.bootstrap();
                        } else {
                            this.stopPoll();
                        }
                    },
                    async bootstrap() {
                        this.loading = true;
                        try {
                            const res = await fetch(this.showUrl, {
                                headers: { Accept: 'application/json' },
                            });
                            if (! res.ok) return;
                            const data = await res.json();
                            this.conversation = data.conversation;
                            this.messages = data.messages ?? [];
                            this.lastId = this.messages.length ? this.messages[this.messages.length - 1].id : 0;
                            this.$nextTick(() => this.scrollBottom());
                            this.startPoll();
                        } finally {
                            this.loading = false;
                        }
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
                                if (msg && ! this.messages.find((m) => m.id === msg.id)) {
                                    this.messages.push(msg);
                                    this.lastId = Math.max(this.lastId, msg.id);
                                }
                            }
                            this.draft = '';
                            this.$nextTick(() => this.scrollBottom());
                        } finally {
                            this.sending = false;
                        }
                    },
                    startPoll() {
                        this.stopPoll();
                        this.pollTimer = setInterval(() => this.poll(), 4000);
                    },
                    stopPoll() {
                        if (this.pollTimer) {
                            clearInterval(this.pollTimer);
                            this.pollTimer = null;
                        }
                    },
                    async poll() {
                        const res = await fetch(`${this.pollUrl}?after_id=${this.lastId}`, {
                            headers: { Accept: 'application/json' },
                        });
                        if (! res.ok) return;
                        const data = await res.json();
                        if (data.conversation) this.conversation = data.conversation;
                        for (const msg of data.messages ?? []) {
                            if (! this.messages.find((m) => m.id === msg.id)) {
                                this.messages.push(msg);
                                this.lastId = Math.max(this.lastId, msg.id);
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
@endif
