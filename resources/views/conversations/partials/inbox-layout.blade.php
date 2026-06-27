@php
    use App\Enums\MessageChannel;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $search = $search ?? '';
@endphp

<div class="grid min-h-[32rem] gap-6 lg:grid-cols-12 lg:items-stretch">
    {{-- Lista de conversas --}}
    <aside class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg ring-1 ring-violet-100/70 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-violet-900/40 lg:col-span-4 {{ $activeConversation ? 'hidden lg:flex' : 'flex' }}">
        <div class="border-b border-slate-100 bg-gradient-to-r from-violet-600/10 via-indigo-600/5 to-transparent px-5 py-4 dark:border-slate-700">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-800 dark:text-slate-100">{{ __('Conversas') }}</h2>
                @if ($totalUnread > 0)
                    <span id="conversations-unread-badge" class="rounded-full bg-violet-600 px-2 py-0.5 text-[10px] font-bold text-white">{{ $totalUnread }}</span>
                @endif
            </div>

            <form method="get" action="{{ $activeConversation ? route('conversations.show', $activeConversation) : route('conversations.index') }}" class="mt-3">
                <label for="inbox-search" class="sr-only">{{ __('Pesquisar conversas') }}</label>
                <input
                    id="inbox-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="{{ __('Pesquisar por nome…') }}"
                    class="block w-full rounded-xl border-slate-200 bg-white py-2 pl-3 pr-3 text-xs font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:border-violet-500 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                />
            </form>

            @if ($user->isProfessional() && $eligiblePatients->isNotEmpty())
                <form method="post" action="{{ route('conversations.start') }}" class="mt-3 flex gap-2">
                    @csrf
                    <select
                        name="patient_id"
                        class="block min-w-0 flex-1 rounded-xl border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:border-violet-500 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                        required
                    >
                        <option value="">{{ __('Nova conversa…') }}</option>
                        @foreach ($eligiblePatients as $entry)
                            @php
                                /** @var \App\Models\Patient $pickerPatient */
                                $pickerPatient = $entry['patient'];
                                $canConverse = $entry['can_converse'] ?? false;
                                $isActivePatient = ($activeConversation?->patient_id ?? null) === $pickerPatient->id;
                            @endphp
                            <option
                                value="{{ $canConverse ? $pickerPatient->id : '' }}"
                                @disabled(! $canConverse)
                                @selected($isActivePatient)
                            >
                                {{ $pickerPatient->name }}@unless($canConverse) — {{ __('sem conta no portal') }}@endunless
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="shrink-0 rounded-xl bg-violet-600 px-3 py-2 text-xs font-bold text-white hover:bg-violet-500">{{ __('Abrir') }}</button>
                </form>
            @endif
        </div>

        <ul class="flex-1 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-700/80" role="list">
            @forelse ($inbox as $conversation)
                @php
                    $peer = $conversation->peerFor($user);
                    $displayName = $user->isProfessional()
                        ? ($conversation->patient?->name ?? $peer?->name)
                        : $peer?->name;
                    $unread = $conversation->unreadCountFor($user);
                    $preview = $conversation->latestMessage?->body;
                    $isActive = $activeConversation?->id === $conversation->id;
                @endphp
                <li>
                    <a
                        href="{{ route('conversations.show', array_filter(['conversation' => $conversation, 'q' => $search ?: null])) }}"
                        @class([
                            'flex items-start gap-3 px-4 py-3 transition',
                            'bg-violet-50/80 ring-1 ring-inset ring-violet-200/60 dark:bg-violet-950/30 dark:ring-violet-800/40' => $isActive,
                            'hover:bg-slate-50 dark:hover:bg-slate-800/60' => ! $isActive,
                        ])
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 text-xs font-bold text-white shadow-md">
                            {{ $initials($displayName ?? '?') }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ $displayName }}</span>
                                @if ($conversation->last_message_at)
                                    <span class="shrink-0 text-[10px] font-medium text-slate-400">{{ $conversation->last_message_at->diffForHumans(short: true) }}</span>
                                @endif
                            </div>
                            @if ($preview)
                                <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($preview, 60) }}</p>
                            @endif
                        </div>
                        @if ($unread > 0)
                            <span class="mt-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-violet-600 px-1.5 text-[10px] font-bold text-white">{{ $unread }}</span>
                        @endif
                    </a>
                </li>
            @empty
                <li class="px-6 py-16 text-center">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('Nenhuma conversa encontrada') }}</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        @if ($search)
                            {{ __('Tente outro termo de pesquisa.') }}
                        @elseif ($user->isProfessional())
                            {{ __('Inicie uma conversa com um paciente que tenha conta na plataforma.') }}
                        @else
                            {{ __('Quando o seu terapeuta enviar a primeira mensagem, a conversa aparecerá aqui.') }}
                        @endif
                    </p>
                </li>
            @endforelse
        </ul>
    </aside>

    {{-- Thread activa --}}
    <section @class([
        'flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg ring-1 ring-violet-100/70 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-violet-900/40 lg:col-span-8',
        'hidden lg:flex' => ! $activeConversation,
        'flex' => $activeConversation,
    ]) aria-label="{{ __('Conversa activa') }}">
        @if ($activeConversation)
            @php
                $peer = $activeConversation->peerFor($user);
                $activeDisplayName = $user->isProfessional()
                    ? ($activeConversation->patient?->name ?? $peer?->name)
                    : $peer?->name;
                $lastMessageId = $messages?->last()?->id ?? 0;
            @endphp

            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <a href="{{ route('conversations.index', array_filter(['q' => $search ?: null])) }}" class="inline-flex rounded-lg p-1 text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" aria-label="{{ __('Voltar à lista') }}">
                        <x-ui.icon name="arrow-left" class="h-5 w-5" />
                    </a>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 text-sm font-bold text-white shadow-md">
                        {{ $initials($activeDisplayName ?? '?') }}
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ $activeDisplayName }}</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Conversa terapêutica privada') }}</p>
                        @if ($user->isProfessional() && ($whatsappConfigured ?? false))
                            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">
                                {{ $whatsappDriverLabel ?? 'WhatsApp' }} · {{ __('sync disponível') }}
                            </p>
                        @elseif ($user->isPatient() && ($patientWhatsappAwaitingConsent ?? false))
                            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">
                                {{ __('Acção necessária: consentimento WhatsApp') }}
                            </p>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($user->isProfessional() && $activeConversation->patient)
                        <a href="{{ route('patients.show', $activeConversation->patient) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-violet-700 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-violet-300">
                            <x-ui.icon name="user" class="h-4 w-4" />
                            {{ __('Ficha') }}
                        </a>
                    @endif

                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-900 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100">
                            {{ __('WhatsApp externo') }}
                        </a>
                    @endif

                    @if ($user->isProfessional() && $whatsappConfigured)
                        @php
                            $syncActive = $activeConversation->canSyncWhatsApp();
                            $syncAwaiting = $activeConversation->whatsapp_enabled && ! $activeConversation->hasWhatsappConsent();
                        @endphp
                        <form method="post" action="{{ route('conversations.whatsapp.toggle', $activeConversation) }}">
                            @csrf
                            <button
                                type="submit"
                                title="{{ $syncAwaiting ? __('Aguarda o paciente consentir na aplicação') : '' }}"
                                @class([
                                    'inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold transition',
                                    'border border-emerald-300 bg-emerald-600 text-white' => $syncActive,
                                    'border border-amber-300 bg-amber-500 text-white' => $syncAwaiting,
                                    'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200' => ! $activeConversation->whatsapp_enabled,
                                ])
                            >
                                @if ($syncActive)
                                    <x-ui.icon name="check-circle" class="h-4 w-4 shrink-0" />
                                    {{ __('Sync WhatsApp ON') }}
                                @elseif ($syncAwaiting)
                                    <x-ui.icon name="clock" class="h-4 w-4 shrink-0" />
                                    {{ __('Aguarda consentimento') }}
                                @else
                                    {{ __('Sync WhatsApp') }}
                                @endif
                            </button>
                        </form>
                    @endif

                    @can('export', $activeConversation)
                        <a href="{{ route('conversations.export.pdf', $activeConversation) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                            {{ __('PDF') }}
                        </a>
                    @endcan

                    @can('archiveToRecord', $activeConversation)
                        <form method="post" action="{{ route('conversations.archive.record', $activeConversation) }}" onsubmit="return confirm(@js(__('Arquivar transcrição completa no prontuário do paciente?')))">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-semibold text-violet-800 hover:bg-violet-100 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-200">
                                {{ __('Prontuário') }}
                            </button>
                        </form>
                    @endcan
                </div>
            </header>

            <p id="peer-typing-indicator" class="hidden border-b border-violet-100 bg-violet-50/80 px-5 py-2 text-xs font-medium text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300">
                {{ __(':name está a escrever…', ['name' => $peer?->name ?? '']) }}
            </p>

            @if ($user->isProfessional() && ($whatsappAwaitingConsent ?? false))
                <div class="border-b border-amber-200/80 bg-amber-50 px-5 py-4 dark:border-amber-900/50 dark:bg-amber-950/30">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-amber-950 dark:text-amber-50">
                                {{ __('Vista do profissional — aguarda consentimento do paciente') }}
                            </p>
                            <p class="mt-1 text-xs leading-relaxed text-amber-900/90 dark:text-amber-100/90">
                                @if ($activeConversation->patientUser)
                                    {{ __(':name (:email) deve entrar na área do paciente (portal), abrir «Conversas» e confirmar o pedido de sincronização WhatsApp. Não é possível registar o consentimento nesta conta profissional.', [
                                        'name' => $activeConversation->patientUser->name,
                                        'email' => $activeConversation->patientUser->email ?? __('sem e-mail'),
                                    ]) }}
                                @else
                                    {{ __('O paciente deve entrar no portal com a conta dele, abrir «Conversas» e consentir. Não é possível registar o consentimento nesta conta profissional.') }}
                                @endif
                            </p>
                        </div>
                        <form method="post" action="{{ route('conversations.whatsapp.consent-remind', $activeConversation) }}" class="flex shrink-0 flex-col gap-2 sm:items-end">
                            @csrf
                            <div class="flex flex-wrap justify-center gap-3 text-xs font-medium text-amber-950 dark:text-amber-100">
                                @if ($consentReminderCanEmail ?? false)
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="send_email" value="1" class="rounded border-amber-400 text-amber-600 focus:ring-amber-500" checked />
                                        {{ __('E-mail') }}
                                    </label>
                                @endif
                                @if (($consentReminderCanWhatsApp ?? false) && ($whatsappConfigured ?? false))
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="send_whatsapp" value="1" class="rounded border-amber-400 text-amber-600 focus:ring-amber-500" checked />
                                        {{ __('WhatsApp') }}
                                    </label>
                                @endif
                            </div>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-500 disabled:opacity-50"
                                @disabled(! ($consentReminderCanEmail ?? false) && ! ($consentReminderCanWhatsApp ?? false))
                            >
                                <x-ui.icon name="paper-airplane" class="h-4 w-4 shrink-0" />
                                {{ __('Enviar lembrete') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($user->isPatient() && ($patientWhatsappAwaitingConsent ?? false))
                <div class="border-b border-amber-300/80 bg-gradient-to-r from-amber-50 to-orange-50 px-5 py-5 dark:border-amber-900/50 dark:from-amber-950/40 dark:to-orange-950/30">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="text-base font-bold text-amber-950 dark:text-amber-50">
                                {{ __('Consentir sincronização com WhatsApp') }}
                            </p>
                            <p class="mt-2 text-sm leading-relaxed text-amber-900/95 dark:text-amber-100/95">
                                {{ __('O seu terapeuta pediu para ligar o WhatsApp a esta conversa. Ao consentir, as mensagens que trocar por WhatsApp também ficam registadas aqui, de forma segura.') }}
                            </p>
                            <p class="mt-2 text-xs text-amber-800/80 dark:text-amber-200/80">
                                {{ __('Pode revogar o consentimento a qualquer momento nesta conversa.') }}
                            </p>
                        </div>
                        <form method="post" action="{{ route('conversations.whatsapp.consent', $activeConversation) }}" class="shrink-0">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-600 px-6 py-3 text-sm font-bold text-white shadow-md shadow-amber-900/20 transition hover:bg-amber-500 sm:w-auto"
                            >
                                <x-ui.icon name="check-circle" class="h-5 w-5 shrink-0" />
                                {{ __('Consentir sincronização WhatsApp') }}
                            </button>
                        </form>
                    </div>
                </div>
            @elseif ($user->isPatient() && $activeConversation->hasWhatsappConsent())
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-emerald-200/60 bg-emerald-50/80 px-5 py-2 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                    <p class="text-[11px] font-medium text-emerald-900 dark:text-emerald-200">
                        {{ __('WhatsApp sincronizado — consentimento activo. O terapeuta envia mensagens pelo WhatsApp; as suas respostas no WhatsApp aparecem aqui.') }}
                    </p>
                    <form method="post" action="{{ route('conversations.whatsapp.revoke', $activeConversation) }}">
                        @csrf
                        <button type="submit" class="text-[11px] font-semibold text-emerald-800 underline dark:text-emerald-300">{{ __('Revogar') }}</button>
                    </form>
                </div>
            @endif

            @if ($user->isProfessional() && ($whatsappPhoneMissing ?? false) && $activeConversation->canSyncWhatsApp())
                <div class="border-b border-rose-200/80 bg-rose-50 px-5 py-3 dark:border-rose-900/50 dark:bg-rose-950/30">
                    <p class="text-xs font-medium text-rose-950 dark:text-rose-100">
                        {{ __('Sync WhatsApp activo, mas o paciente não tem telefone na ficha. Adicione o número com DDD para enviar mensagens pelo WhatsApp.') }}
                    </p>
                    @if ($activeConversation->patient_id)
                        <a href="{{ route('patients.show', $activeConversation->patient_id) }}" class="mt-2 inline-block text-xs font-bold text-rose-800 underline dark:text-rose-300">
                            {{ __('Abrir ficha do paciente') }}
                        </a>
                    @endif
                </div>
            @endif

            <form method="get" action="{{ route('conversations.show', $activeConversation) }}" class="border-b border-slate-100 px-5 py-2 dark:border-slate-700">
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="{{ __('Pesquisar nesta conversa…') }}"
                    class="block w-full rounded-lg border-slate-200 bg-slate-50 py-2 pl-3 pr-3 text-xs dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                />
            </form>

            <div class="flex-1 space-y-3 overflow-y-auto bg-slate-50/60 px-4 py-5 dark:bg-slate-950/30 sm:px-6" id="conversation-thread">
                @forelse ($messages ?? [] as $message)
                    @include('conversations.partials.message-bubble', [
                        'message' => $message,
                        'user' => $user,
                        'conversation' => $activeConversation,
                    ])
                @empty
                    <p class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ $search ? __('Nenhuma mensagem corresponde à pesquisa.') : __('Ainda não há mensagens nesta conversa.') }}
                    </p>
                @endforelse
            </div>

            @if ($messages?->hasPages())
                <div class="border-t border-slate-100 px-4 py-2 dark:border-slate-700">
                    {{ $messages->links() }}
                </div>
            @endif

            <footer class="border-t border-slate-100 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/90 sm:p-5">
                <form method="post" action="{{ route('conversations.messages.store', $activeConversation) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <label for="body" class="sr-only">{{ __('Mensagem') }}</label>
                    <textarea
                        id="body"
                        name="body"
                        rows="3"
                        maxlength="5000"
                        placeholder="{{ __('Escreva a sua mensagem…') }}"
                        class="block w-full resize-y rounded-xl border-slate-200 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:border-violet-500 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
                    >{{ old('body') }}</textarea>
                    <x-input-error :messages="$errors->get('body')" />
                    <x-input-error :messages="$errors->get('attachment')" />

                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
                            <x-ui.icon name="paper-clip" class="h-4 w-4" />
                            {{ __('Anexo') }}
                            <input type="file" name="attachment" class="sr-only" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,application/pdf,image/*" />
                        </label>
                        <span class="text-[10px] text-slate-400">{{ __('PDF, imagens ou Word · máx. 10 MB') }}</span>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3">
                        @if ($user->isProfessional() && $whatsappConfigured && $activeConversation->canSyncWhatsApp())
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400">
                                <input type="checkbox" name="mirror_whatsapp" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                {{ __('Enviar também por WhatsApp') }}
                            </label>
                        @else
                            <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ __('Conteúdo encriptado na plataforma.') }}</span>
                        @endif

                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:from-violet-500 hover:to-indigo-500">
                            <x-ui.icon name="paper-airplane" class="h-4 w-4" />
                            {{ __('Enviar') }}
                        </button>
                    </div>
                </form>
            </footer>
        @else
            <div class="flex flex-1 flex-col items-center justify-center px-6 py-20 text-center">
                <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-violet-500 to-indigo-600 text-white shadow-xl">
                    <x-ui.icon name="chat-bubble-left-right" class="h-10 w-10" />
                </div>
                <p class="mt-5 text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Seleccione uma conversa') }}</p>
                <p class="mt-2 max-w-sm text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Escolha um paciente ou terapeuta na lista para ver o histórico e continuar o acompanhamento.') }}</p>
            </div>
        @endif
    </section>
</div>

@if ($activeConversation && isset($lastMessageId))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const thread = document.getElementById('conversation-thread');
            if (thread) thread.scrollTop = thread.scrollHeight;

            let lastId = {{ (int) $lastMessageId }};
            const pollUrl = @json(route('conversations.poll', $activeConversation));
            const typingUrl = @json(route('conversations.typing', $activeConversation));
            const csrf = @json(csrf_token());
            const typingIndicator = document.getElementById('peer-typing-indicator');
            let typingTimer = null;

            const bodyField = document.getElementById('body');
            if (bodyField) {
                bodyField.addEventListener('input', () => {
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(() => {
                        fetch(typingUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        }).catch(() => {});
                    }, 400);
                });
            }

            const renderMessage = (msg) => {
                const wrap = document.createElement('div');
                wrap.className = msg.mine ? 'flex justify-end' : 'flex justify-start';
                wrap.dataset.messageId = msg.id;

                const bubble = document.createElement('div');
                bubble.className = msg.mine
                    ? 'max-w-[85%] rounded-2xl rounded-br-md bg-gradient-to-br from-violet-600 to-indigo-600 px-4 py-3 text-sm text-white shadow-sm'
                    : 'max-w-[85%] rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';

                let html = '';
                if (msg.channel === 'whatsapp' || msg.whatsapp_sent) {
                    html += '<span class="mb-1 inline-flex rounded-md bg-black/10 px-1.5 py-0.5 text-[10px] font-bold uppercase">WhatsApp</span>';
                }
                html += `<p class="whitespace-pre-wrap break-words leading-relaxed">${msg.body.replace(/</g, '&lt;')}</p>`;
                if (msg.attachments?.length) {
                    html += '<ul class="mt-2 space-y-1">';
                    msg.attachments.forEach(a => {
                        html += `<li><a href="${a.url}" class="inline-flex items-center gap-1 rounded-lg bg-white/15 px-2 py-1 text-xs font-semibold">${a.name} (${a.size})</a></li>`;
                    });
                    html += '</ul>';
                }
                html += `<p class="mt-1.5 text-[10px] font-medium opacity-70">${msg.created_at}</p>`;
                bubble.innerHTML = html;
                wrap.appendChild(bubble);
                return wrap;
            };

            setInterval(async () => {
                if (document.hidden) return;
                try {
                    const res = await fetch(`${pollUrl}?after_id=${lastId}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!data.messages?.length && !data.peer_typing) return;

                    if (data.messages?.length) {
                    data.messages.forEach(msg => {
                        if (thread.querySelector(`[data-message-id="${msg.id}"]`)) return;
                        thread.appendChild(renderMessage(msg));
                        lastId = Math.max(lastId, msg.id);
                    });
                    thread.scrollTop = thread.scrollHeight;
                    }

                    if (typingIndicator) {
                        typingIndicator.classList.toggle('hidden', !data.peer_typing);
                    }

                    const badge = document.getElementById('conversations-unread-badge');
                    if (badge && typeof data.unread_total === 'number') {
                        if (data.unread_total > 0) {
                            badge.textContent = data.unread_total;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                } catch (_) {}
            }, 10000);
        });
    </script>
@endif
