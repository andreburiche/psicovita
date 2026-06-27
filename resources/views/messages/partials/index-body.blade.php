    <div class="{{ $patientLayout ? 'mx-auto max-w-5xl' : 'mx-auto max-w-7xl' }} space-y-8 pb-8">
        <x-page-hero
            :title="__('Mensagens')"
            :subtitle="$heroSubtitle"
            icon="chat-bubble-left-right"
        />

        @if (isset($fichaExternalContacts) && $fichaExternalContacts->isNotEmpty())
            <div class="overflow-hidden rounded-2xl border border-teal-200/80 bg-gradient-to-br from-teal-50/90 via-white to-emerald-50/60 p-5 shadow-md shadow-teal-900/5 ring-1 ring-teal-100/80 dark:border-teal-800/50 dark:from-teal-950/40 dark:via-slate-900/80 dark:to-emerald-950/30 dark:ring-teal-900/30">
                <h3 class="text-base font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Contacto directo pela ficha') }}</h3>
                <p class="mt-1 text-xs font-medium leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ __('E-mail e WhatsApp usam os dados da ficha clínica. Estas conversas não ficam registadas no histórico abaixo.') }}
                </p>
                <ul class="mt-4 divide-y divide-teal-100/80 dark:divide-teal-900/40" role="list">
                    @foreach ($fichaExternalContacts as $row)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0">
                            <span class="min-w-0 flex-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $row['name'] }}</span>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if (! empty($row['mailtoUrl']))
                                    <a
                                        href="{{ $row['mailtoUrl'] }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-800 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-violet-500"
                                    >
                                        <svg class="h-4 w-4 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.125a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                        </svg>
                                        {{ __('E-mail') }}
                                    </a>
                                @endif
                                @if (! empty($row['whatsappUrl']))
                                    <a
                                        href="{{ $row['whatsappUrl'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-900 shadow-sm transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-100 dark:hover:bg-emerald-900/40"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                        </svg>
                                        {{ __('WhatsApp') }}
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
            <aside
                class="space-y-4 lg:col-span-4"
                x-data="{
                    rid: @js((string) (old('recipient_id') ?? '')),
                    links: @js($recipientExternalLinks ?? []),
                }"
            >
                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/70 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-violet-900/40">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-violet-600/10 via-indigo-600/5 to-transparent px-5 py-4 dark:border-slate-700 dark:from-violet-500/15">
                        <h3 class="flex items-center gap-2 text-base font-bold tracking-tight text-slate-900 dark:text-slate-100">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-600 text-white shadow-md shadow-violet-600/30 dark:bg-violet-500" aria-hidden="true">
                                <x-ui.icon name="chat-bubble-left-right" class="h-5 w-5" />
                            </span>
                            {{ __('Nova mensagem') }}
                        </h3>
                        <p class="mt-1 text-xs font-medium leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Escolha o destinatário e escreva a mensagem. Pode também abrir e-mail ou WhatsApp para o mesmo contacto.') }}</p>
                    </div>

                    <div class="p-5">
                        @if ($recipients->isEmpty())
                            <div class="mb-5 rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/35 dark:text-amber-100">
                                <p class="font-semibold">{{ __('Sem destinatários para mensagens') }}</p>
                                @if (($patientsCount ?? 0) > 0)
                                    <p class="mt-1 text-xs font-medium leading-relaxed opacity-90">
                                        @if ($patientsCount === 1)
                                            {{ __('Tem 1 paciente na ficha clínica, mas ainda não existe utilizador na plataforma com o mesmo e-mail.') }}
                                        @else
                                            {{ __('Tem :count pacientes na ficha clínica, mas ainda não existe utilizador na plataforma com o mesmo e-mail para nenhum deles.', ['count' => $patientsCount]) }}
                                        @endif
                                        {{ __('Peça ao paciente para criar conta com o mesmo e-mail da ficha, ou confirme o e-mail na ficha do paciente.') }}
                                    </p>
                                @else
                                    <p class="mt-1 text-xs font-medium leading-relaxed opacity-90">{{ __('Cadastre pacientes na ficha e, quando tiverem utilizador com o mesmo e-mail, poderá enviar mensagens aqui.') }}</p>
                                @endif
                                @if (! $patientLayout)
                                    <a href="{{ route('patients.index') }}" class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-amber-900 underline decoration-amber-700/50 underline-offset-2 transition hover:text-amber-800 dark:text-amber-200 dark:hover:text-amber-50">
                                        {{ __('Ir para pacientes') }}
                                        <span aria-hidden="true">→</span>
                                    </a>
                                @endif
                            </div>
                        @endif

                        <form method="post" action="{{ route('messages.store') }}" class="space-y-5">
                            @csrf
                            <div>
                                <x-input-label for="recipient_id" :value="__('Destinatário')" class="text-slate-700 dark:text-slate-300" />
                                @if ($recipients->isNotEmpty())
                                    <select
                                        id="recipient_id"
                                        name="recipient_id"
                                        x-model="rid"
                                        class="mt-2 block w-full rounded-xl border-slate-200 bg-white py-2.5 pl-3 pr-10 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 transition focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                                        required
                                    >
                                        <option value="" disabled @if (old('recipient_id') === null || old('recipient_id') === '') selected @endif>{{ __('Selecione uma pessoa…') }}</option>
                                        @foreach ($recipients as $r)
                                            <option value="{{ $r->id }}" @selected((string) old('recipient_id') === (string) $r->id)>
                                                {{ $r->name }} @if ($r->email) — {{ $r->email }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <p class="mt-2 rounded-xl border border-dashed border-slate-200 bg-slate-50/80 px-3 py-3 text-xs text-slate-500 dark:border-slate-600 dark:bg-slate-800/50 dark:text-slate-400">{{ __('Não é possível enviar até existir pelo menos um paciente com utilizador na plataforma.') }}</p>
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('recipient_id')" />
                            </div>

                            <div
                                x-cloak
                                x-show="rid !== '' && links[rid] && (links[rid].mailtoUrl || links[rid].whatsappUrl)"
                                class="rounded-xl border border-teal-200/80 bg-teal-50/80 p-4 dark:border-teal-800/50 dark:bg-teal-950/25"
                            >
                                <p class="text-xs font-bold uppercase tracking-wide text-teal-800 dark:text-teal-300">{{ __('Abrir noutra aplicação') }}</p>
                                <p class="mt-1 text-[11px] leading-relaxed text-teal-900/80 dark:text-teal-200/90">{{ __('O texto da caixa acima não é copiado automaticamente; pode colar depois de abrir o WhatsApp ou o e-mail.') }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template x-if="links[rid]?.mailtoUrl">
                                        <a
                                            x-bind:href="links[rid].mailtoUrl"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-800 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                                        >
                                            <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.125a2.25 2.25 0 01-2.25 2.25H4.5a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                            </svg>
                                            {{ __('E-mail') }}
                                        </a>
                                    </template>
                                    <template x-if="links[rid]?.whatsappUrl">
                                        <a
                                            x-bind:href="links[rid].whatsappUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-900 shadow-sm transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-100"
                                        >
                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                            </svg>
                                            {{ __('WhatsApp') }}
                                        </a>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <x-input-label for="body" :value="__('Texto')" class="text-slate-700 dark:text-slate-300" />
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('Máx. 5000 caracteres') }}</span>
                                </div>
                                <textarea
                                    id="body"
                                    name="body"
                                    rows="5"
                                    maxlength="5000"
                                    placeholder="{{ __('Escreva aqui a sua mensagem…') }}"
                                    class="mt-2 block w-full resize-y rounded-xl border-slate-200 bg-white px-3 py-3 text-sm leading-relaxed text-slate-900 shadow-sm ring-1 ring-slate-200/80 transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:ring-slate-600"
                                    required
                                >{{ old('body') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('body')" />
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-700">
                                <button
                                    type="submit"
                                    @disabled($recipients->isEmpty())
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                                >
                                    <x-ui.icon name="paper-airplane" class="h-5 w-5 shrink-0" />
                                    {{ __('Enviar mensagem') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <p class="text-center text-[11px] font-medium leading-relaxed text-slate-500 dark:text-slate-500 lg:text-left">
                    @if ($patientLayout)
                        {{ __('As mensagens recebidas ficam aqui depois de iniciar sessão. Não recebe alerta por SMS ou e-mail da plataforma para cada mensagem nova.') }}
                    @else
                        {{ __('As mensagens enviadas pelo formulário são internas ao PsiConecta. O paciente vê-as em Mensagens na conta dele (não são enviadas por e-mail nem WhatsApp automaticamente). E-mail e WhatsApp abrem aplicações externas. Para o WhatsApp reconhecer o número, use na ficha o indicativo internacional (ex.: 351…) ou defina o prefixo por defeito na configuração da aplicação.') }}
                    @endif
                </p>
            </aside>

            <section class="space-y-4 lg:col-span-8" aria-labelledby="messages-history-title">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 id="messages-history-title" class="text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100">{{ __('Histórico') }}</h2>
                        <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ __('Mensagens mais recentes primeiro.') }}</p>
                    </div>
                    @if ($messages->total() > 0)
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200/80 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600">
                            {{ number_format($messages->total()) }} {{ $messages->total() === 1 ? __('mensagem') : __('mensagens') }}
                        </span>
                    @endif
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-violet-900/5 ring-1 ring-violet-100/70 dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-black/20 dark:ring-violet-900/40">
                    <ul class="divide-y divide-slate-100 dark:divide-slate-700/80" role="list">
                        @forelse ($messages as $message)
                            @php
                                $sent = $message->sender_id === auth()->id();
                                $peerName = $sent ? $message->recipient->name : $message->sender->name;
                                $peerInitials = $initials($peerName);
                            @endphp
                            <li class="px-4 py-4 sm:px-6">
                                <div class="flex gap-3 sm:gap-4">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-xs font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-sm {{ $sent ? 'bg-gradient-to-br from-violet-500 to-indigo-600 shadow-violet-500/30' : 'bg-gradient-to-br from-slate-500 to-slate-700 shadow-slate-900/20 dark:from-slate-600 dark:to-slate-800' }}"
                                        aria-hidden="true"
                                    >
                                        {{ $peerInitials }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                            @if ($sent)
                                                <x-ui.badge variant="violet">{{ __('Enviada') }}</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="info">{{ __('Recebida') }}</x-ui.badge>
                                            @endif
                                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $peerName }}</span>
                                            <span class="text-xs font-medium text-slate-400 dark:text-slate-500" title="{{ $message->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}">
                                                {{ $message->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                        <p class="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-700 dark:text-slate-300">{{ $message->body }}</p>
                                        <p class="mt-2 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                                            {{ $sent ? __('Para') : __('De') }}
                                            :
                                            <span class="text-slate-600 dark:text-slate-400">{{ $sent ? $message->recipient->name : $message->sender->name }}</span>
                                        </p>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="px-6 py-20 text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-violet-500 to-indigo-600 text-white shadow-xl shadow-violet-500/25" aria-hidden="true">
                                    <x-ui.icon name="chat-bubble-left-right" class="h-10 w-10" />
                                </div>
                                <p class="mt-5 text-base font-semibold text-slate-900 dark:text-slate-100">{{ __('Nenhuma mensagem ainda') }}</p>
                                <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ __('Quando enviar ou receber mensagens, elas aparecem aqui com contexto claro de envio e receção.') }}</p>
                            </li>
                        @endforelse
                    </ul>
                </div>

                @if ($messages->hasPages())
                    <div class="flex justify-center border-t border-transparent pt-2 text-sm text-slate-500 dark:text-slate-400 sm:justify-end">
                        {{ $messages->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
