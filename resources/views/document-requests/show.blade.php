<x-app-layout>
    <x-slot name="header">{{ __('Solicitação de documentos') }}</x-slot>

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('document-requests.partials.patient-breadcrumb-trail', [
                'patient' => $patient,
                'documentRequest' => $documentRequest,
            ])

            @if (session('status'))
                <x-ui.success-alert :title="session('status')" />
            @endif

            @include('document-requests.partials.show-header', [
                'patient' => $patient,
                'documentRequest' => $documentRequest,
            ])

            <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
            <div class="space-y-6 lg:col-span-7">
                <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50/90 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900/80">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Contato na instituição') }}</h3>
                    </div>
                    <dl class="grid gap-4 p-5 text-sm sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-800/40">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Responsável') }}</dt>
                            <dd class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $documentRequest->contact_name ?? '—' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-800/40">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('E-mail') }}</dt>
                            <dd class="mt-1 break-all font-medium text-slate-800 dark:text-slate-200">{{ $documentRequest->contact_email ?? '—' }}</dd>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 sm:col-span-2 dark:border-slate-700 dark:bg-slate-800/40">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Telefone') }}</dt>
                            <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">
                                {{ $documentRequest->contact_phone ? format_phone_br_human($documentRequest->contact_phone) : '—' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-violet-50/70 to-indigo-50/50 px-5 py-4 dark:border-slate-700 dark:from-violet-950/40 dark:to-indigo-950/30">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Documentos solicitados') }}</h3>
                    </div>
                    <ul class="flex flex-wrap gap-2 p-5">
                        @foreach ($documentRequest->requested_documents as $doc)
                            <li class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-3 py-1.5 text-sm font-medium text-violet-900 ring-1 ring-violet-200/80 dark:bg-violet-950/40 dark:text-violet-200 dark:ring-violet-800">
                                <svg class="h-3.5 w-3.5 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.9.393 2.592 1.022M7.5 4.5v15" />
                                </svg>
                                {{ $doc }}
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <aside class="space-y-6 lg:col-span-5">
                <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700">
                    <div class="border-b border-slate-100 bg-gradient-to-r from-amber-50/70 to-orange-50/50 px-5 py-4 dark:border-slate-700 dark:from-amber-950/30 dark:to-orange-950/20">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Finalidade do pedido') }}</h3>
                    </div>
                    <p class="whitespace-pre-wrap p-5 text-sm leading-relaxed text-slate-800 dark:text-slate-200">{{ $documentRequest->request_reason }}</p>
                </section>

                @if ($documentRequest->patient_consent_at)
                    <div class="flex gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800/50 dark:bg-emerald-950/30 dark:text-emerald-100">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <p class="pt-0.5 text-xs leading-relaxed">
                            <span class="font-semibold">{{ __('Consentimento LGPD') }}</span><br>
                            {{ __('Registrado em') }} {{ $documentRequest->patient_consent_at->format('d/m/Y H:i') }}
                            @if ($documentRequest->consentRecordedByUser)
                                {{ __('por') }} {{ $documentRequest->consentRecordedByUser->name }}
                            @endif
                        </p>
                    </div>
                @endif

                <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 px-4 py-3 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
                    {{ __('Criado por') }} <span class="font-medium text-slate-700 dark:text-slate-300">{{ $documentRequest->createdByUser?->name ?? '—' }}</span>
                    · {{ $documentRequest->created_at->format('d/m/Y H:i') }}
                    @if ($documentRequest->updatedByUser)
                        <br>{{ __('Atualizado por') }} <span class="font-medium text-slate-700 dark:text-slate-300">{{ $documentRequest->updatedByUser->name }}</span>
                    @endif
                </div>
            </aside>
        </div>

        @can('sendEmail', $documentRequest)
            <section
                x-data="{
                    openSendEmailConfirm() {
                        const form = this.$refs.sendEmailForm;
                        if (! form.reportValidity()) {
                            return;
                        }
                        const to = form.querySelector('[name=to]').value.trim();
                        const cc = form.querySelector('[name=cc]').value.trim();
                        const details = [
                            { label: @js(__('Instituição')), value: @js($documentRequest->institution_name) },
                            { label: @js(__('Paciente')), value: @js($patient->name) },
                            { label: @js(__('Destinatário')), value: to },
                        ];
                        if (cc) {
                            details.push({ label: @js(__('Cópia (CC)')), value: cc });
                        }
                        window.dispatchEvent(new CustomEvent('confirm-dialog:open', {
                            detail: {
                                title: @js(__('Enviar ofício por e-mail?')),
                                message: @js(__('Revise os dados antes de confirmar. Esta ação registra o envio na solicitação.')),
                                hint: @js(__('O PDF do ofício será anexado ao e-mail e o status passará para «Enviado».')),
                                eyebrow: @js(__('Confirmar envio')),
                                confirmLabel: @js(__('Confirmar e enviar')),
                                cancelLabel: @js(__('Cancelar')),
                                variant: 'primary',
                                details,
                                formId: 'document-request-send-email-form',
                            },
                        }));
                    },
                }"
                class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900/80"
            >
                <div class="border-b border-slate-100 bg-gradient-to-r from-emerald-50/80 to-sky-50/50 px-5 py-4 dark:border-slate-700 dark:from-emerald-950/30 dark:to-sky-950/30">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">{{ __('Enviar por e-mail') }}</h3>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Envia o ofício em PDF diretamente para a instituição. O status da solicitação será atualizado para «Enviado».') }}</p>
                </div>

                @if ($documentRequest->last_email_sent_at)
                    <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
                        {{ __('Último envio:') }}
                        <strong class="text-slate-800 dark:text-slate-200">{{ $documentRequest->last_email_sent_at->format('d/m/Y H:i') }}</strong>
                        {{ __('para') }}
                        <strong class="text-slate-800 dark:text-slate-200">{{ $documentRequest->last_email_sent_to }}</strong>
                        @if ($documentRequest->lastEmailSentByUser)
                            · {{ __('por') }} {{ $documentRequest->lastEmailSentByUser->name }}
                        @endif
                    </div>
                @endif

                <form
                    x-ref="sendEmailForm"
                    id="document-request-send-email-form"
                    method="post"
                    action="{{ route('patients.document-requests.send-email', [$patient, $documentRequest]) }}"
                    class="grid gap-4 p-5"
                >
                    @csrf
                    <x-input-error :messages="$errors->get('email')" class="col-span-full" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="email_to" class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Destinatário') }} <span class="text-red-500">*</span></label>
                            <input
                                type="email"
                                id="email_to"
                                name="to"
                                value="{{ old('to', $documentRequest->contact_email) }}"
                                class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900"
                                placeholder="contato@instituicao.org"
                                required
                            />
                            <x-input-error :messages="$errors->get('to')" class="mt-1" />
                        </div>
                        <div>
                            <label for="email_cc" class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Cópia (CC)') }}</label>
                            <input
                                type="email"
                                id="email_cc"
                                name="cc"
                                value="{{ old('cc') }}"
                                class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900"
                                placeholder="{{ __('Opcional') }}"
                            />
                            <x-input-error :messages="$errors->get('cc')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <label for="email_message" class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Mensagem personalizada') }}</label>
                        <textarea
                            id="email_message"
                            name="message"
                            rows="3"
                            class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900"
                            placeholder="{{ __('Opcional — texto adicional no corpo do e-mail.') }}"
                        >{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-1" />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            x-on:click="openSendEmailConfirm()"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/25 transition hover:bg-emerald-500"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 00-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 00-1.07-1.916V6.75" />
                            </svg>
                            {{ __('Enviar ofício por e-mail') }}
                        </button>
                        <p class="text-xs text-slate-500">{{ __('O PDF do ofício será anexado automaticamente.') }}</p>
                    </div>
                </form>
            </section>
        @endcan

        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900/80">
            <div class="flex flex-col gap-1 border-b border-slate-100 bg-gradient-to-r from-sky-50/80 to-violet-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-sky-950/30 dark:to-violet-950/30">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-sky-900 dark:text-sky-200">{{ __('Anexos da solicitação') }}</h3>
                    <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Autorizações, devolutivas e relatórios vinculados a este pedido.') }}</p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-sky-800 ring-1 ring-sky-200/80 dark:bg-slate-900/80 dark:text-sky-200 dark:ring-sky-800">
                    {{ trans_choice(':count arquivo|:count arquivos', $documentRequest->files->count(), ['count' => $documentRequest->files->count()]) }}
                </span>
            </div>

            <ul class="space-y-1 py-2">
                @forelse ($documentRequest->files as $file)
                    @include('document-requests.partials.show-attachment-row', ['file' => $file, 'documentRequest' => $documentRequest])
                @empty
                    <li class="px-5 py-12 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500" aria-hidden="true">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </span>
                        <p class="mt-4 text-sm font-medium text-slate-600 dark:text-slate-400">{{ __('Nenhum anexo nesta solicitação.') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Envie autorizações ou devolutivas usando o formulário abaixo.') }}</p>
                    </li>
                @endforelse
            </ul>

            @can('uploadFile', $documentRequest)
                <form
                    method="post"
                    action="{{ route('patients.document-requests.files.store', [$patient, $documentRequest]) }}"
                    enctype="multipart/form-data"
                    class="border-t border-slate-100 bg-gradient-to-b from-slate-50/80 to-white p-5 dark:border-slate-700 dark:from-slate-800/40 dark:to-slate-900/80"
                >
                    @csrf
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('Adicionar anexo') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                        <div class="lg:col-span-3">
                            <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Categoria') }}</label>
                            <select name="category" class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900" required>
                                @foreach (\App\Enums\DocumentRequestFileCategory::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-6">
                            <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('Arquivo') }}</label>
                            <div class="mt-1 rounded-xl border border-dashed border-slate-300 bg-white px-3 py-2.5 dark:border-slate-600 dark:bg-slate-900">
                                <input type="file" name="file" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-violet-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-violet-700 hover:file:bg-violet-100 dark:text-slate-300 dark:file:bg-violet-950/50 dark:file:text-violet-300" required />
                            </div>
                        </div>
                        <div class="lg:col-span-3">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-600/20 transition hover:bg-violet-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                {{ __('Enviar anexo') }}
                            </button>
                        </div>
                    </div>
                </form>
            @endcan
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900/80">
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Histórico de acesso (LGPD)') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('Registro de visualizações, downloads e envios desta solicitação.') }}</p>
            </div>
            <ul class="max-h-52 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-700">
                @php
                    $actionLabels = [
                        'viewed' => __('Visualização'),
                        'created' => __('Criação'),
                        'updated' => __('Atualização'),
                        'deleted' => __('Exclusão'),
                        'pdf_downloaded' => __('Download do ofício PDF'),
                        'file_uploaded' => __('Upload de anexo'),
                        'file_downloaded' => __('Download de anexo'),
                        'email_sent' => __('Envio por e-mail'),
                    ];
                @endphp
                @forelse ($documentRequest->accessLogs->take(20) as $log)
                    <li class="flex items-start gap-3 px-5 py-3 text-xs">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400" aria-hidden="true">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-800 dark:text-slate-200">{{ $actionLabels[$log->action] ?? $log->action }}</p>
                            <p class="mt-0.5 text-slate-500">{{ $log->user?->name ?? __('Sistema') }} · {{ $log->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">{{ __('Sem registros de acesso.') }}</li>
                @endforelse
            </ul>
        </section>
        </div>
    </div>
</x-app-layout>
