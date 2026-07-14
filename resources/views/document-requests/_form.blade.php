@props([
    'documentRequest',
    'submit' => null,
    'cancelHref' => null,
    'showActions' => true,
])

@php
    $dr = $documentRequest;
    $suggested = config('document_requests.suggested_documents', []);
    $selectedDocs = old('requested_documents', $dr->requested_documents ?? []);

    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
    $selectBase = $inputBase . ' appearance-none bg-[length:1rem] bg-[right_0.75rem_center] bg-no-repeat pr-10';
    $selectBg = "background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\");";
    $checkboxCard = 'group flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200/90 bg-white p-3.5 text-sm text-slate-700 shadow-sm transition hover:border-violet-300/70 hover:bg-violet-50/40 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50/80 has-[:checked]:ring-1 has-[:checked]:ring-violet-500/30 dark:border-slate-600 dark:bg-slate-900/60 dark:text-slate-200 dark:hover:border-violet-500/40 dark:hover:bg-violet-950/30 dark:has-[:checked]:border-violet-500 dark:has-[:checked]:bg-violet-950/40';
@endphp

<div class="space-y-6">
    <x-clinical-documents.partials.section-card
        :title="__('Instituição solicitante')"
        :description="__('Identifique quem pediu os documentos e o estado atual da solicitação.')"
        icon="building-office"
        tone="violet"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="institution_name" :value="__('Nome da instituição')" class="text-slate-700 dark:text-slate-200" />
                <input
                    id="institution_name"
                    name="institution_name"
                    type="text"
                    class="{{ $inputBase }}"
                    value="{{ old('institution_name', $dr->institution_name) }}"
                    required
                    autofocus
                    placeholder="{{ __('Ex.: Escola Municipal, INSS, empresa…') }}"
                />
                <x-input-error :messages="$errors->get('institution_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="institution_type" :value="__('Tipo de instituição')" class="text-slate-700 dark:text-slate-200" />
                <select id="institution_type" name="institution_type" class="{{ $selectBase }}" style="{{ $selectBg }}" required>
                    @foreach (\App\Enums\InstitutionType::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('institution_type', $dr->institution_type?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('institution_type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status" :value="__('Status')" class="text-slate-700 dark:text-slate-200" />
                <select id="status" name="status" class="{{ $selectBase }}" style="{{ $selectBg }}" required>
                    @foreach (\App\Enums\DocumentRequestStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $dr->status?->value ?? \App\Enums\DocumentRequestStatus::Pending->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>
        </div>
    </x-clinical-documents.partials.section-card>

    <x-clinical-documents.partials.section-card
        :title="__('Contato na instituição')"
        :description="__('Pessoa ou canal de referência para envio e acompanhamento.')"
        icon="users"
        tone="indigo"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="contact_name" :value="__('Nome do contato')" class="text-slate-700 dark:text-slate-200" />
                <input id="contact_name" name="contact_name" type="text" class="{{ $inputBase }}" value="{{ old('contact_name', $dr->contact_name) }}" placeholder="{{ __('Nome completo ou setor') }}" />
                <x-input-error :messages="$errors->get('contact_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="contact_email" :value="__('E-mail do contato')" class="text-slate-700 dark:text-slate-200" />
                <input id="contact_email" name="contact_email" type="email" class="{{ $inputBase }}" value="{{ old('contact_email', $dr->contact_email) }}" autocomplete="email" placeholder="contato@instituicao.org" />
                <x-input-error :messages="$errors->get('contact_email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="contact_phone" :value="__('Telefone do contato')" class="text-slate-700 dark:text-slate-200" />
                <input
                    id="contact_phone"
                    name="contact_phone"
                    type="text"
                    data-mask="phone"
                    class="{{ $inputBase }}"
                    value="{{ old('contact_phone', $dr->contact_phone ? format_phone_br_human($dr->contact_phone) : '') }}"
                    placeholder="(00) 00000-0000"
                    autocomplete="tel"
                />
                <x-input-error :messages="$errors->get('contact_phone')" class="mt-2" />
            </div>
        </div>
    </x-clinical-documents.partials.section-card>

    <x-clinical-documents.partials.section-card
        :title="__('Prazos')"
        :description="__('Registe quando a solicitação foi feita e a previsão de retorno.')"
        icon="calendar"
        tone="teal"
    >
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="request_date" :value="__('Data da solicitação')" class="text-slate-700 dark:text-slate-200" />
                <input id="request_date" name="request_date" type="date" class="{{ $inputBase }}" value="{{ old('request_date', optional($dr->request_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                <x-input-error :messages="$errors->get('request_date')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="expected_return_date" :value="__('Previsão de retorno')" class="text-slate-700 dark:text-slate-200" />
                <input id="expected_return_date" name="expected_return_date" type="date" class="{{ $inputBase }}" value="{{ old('expected_return_date', optional($dr->expected_return_date)->format('Y-m-d')) }}" />
                <x-input-error :messages="$errors->get('expected_return_date')" class="mt-2" />
            </div>
        </div>
    </x-clinical-documents.partials.section-card>

    <x-clinical-documents.partials.section-card
        :title="__('Documentos solicitados')"
        :description="__('Selecione os itens pedidos pela instituição ou descreva outro documento.')"
        icon="clipboard-list"
        tone="slate"
    >
        <fieldset>
            <legend class="sr-only">{{ __('Documentos solicitados') }}</legend>
            <div class="grid gap-2.5 sm:grid-cols-2">
                @foreach ($suggested as $doc)
                    <label class="{{ $checkboxCard }}">
                        <input
                            type="checkbox"
                            name="requested_documents[]"
                            value="{{ $doc }}"
                            @checked(in_array($doc, $selectedDocs, true))
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-violet-600 focus:ring-violet-500/30 dark:border-slate-500 dark:bg-slate-800"
                        />
                        <span class="leading-snug">{{ $doc }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-4">
            <x-input-label for="requested_documents_other" :value="__('Outro documento')" class="text-slate-700 dark:text-slate-200" />
            <input id="requested_documents_other" name="requested_documents_other" type="text" class="{{ $inputBase }}" value="{{ old('requested_documents_other') }}" placeholder="{{ __('Descreva se não estiver na lista acima') }}" />
            <x-input-error :messages="$errors->get('requested_documents')" class="mt-2" />
        </div>
    </x-clinical-documents.partials.section-card>

    <x-clinical-documents.partials.section-card
        :title="__('Finalidade e observações')"
        :description="__('Explique o motivo do pedido. Observações internas ficam apenas no prontuário.')"
        icon="chat-bubble-left-right"
        tone="slate"
    >
        <div class="space-y-4">
            <div>
                <x-input-label for="request_reason" :value="__('Finalidade da solicitação')" class="text-slate-700 dark:text-slate-200" />
                <textarea id="request_reason" name="request_reason" rows="4" class="{{ $inputBase }} min-h-[6rem] resize-y" required placeholder="{{ __('Ex.: encaminhamento escolar, benefício, laudo complementar…') }}">{{ old('request_reason', $dr->request_reason) }}</textarea>
                <x-input-error :messages="$errors->get('request_reason')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="notes" :value="__('Observações internas')" class="text-slate-700 dark:text-slate-200" />
                <textarea id="notes" name="notes" rows="3" class="{{ $inputBase }} min-h-[5rem] resize-y" placeholder="{{ __('Notas visíveis apenas para a equipa clínica…') }}">{{ old('notes', $dr->notes) }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </div>
    </x-clinical-documents.partials.section-card>

    <section class="rounded-2xl border border-amber-200/90 bg-gradient-to-br from-amber-50/90 via-white to-amber-50/50 p-5 shadow-sm ring-1 ring-amber-100 dark:border-amber-900/60 dark:from-amber-950/40 dark:via-slate-900/80 dark:to-amber-950/20 dark:ring-amber-900/40">
        <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-900 dark:text-amber-200">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-200/80 text-amber-900 dark:bg-amber-900/60 dark:text-amber-100" aria-hidden="true">
                <x-ui.icon name="shield-check" class="h-4 w-4" />
            </span>
            {{ __('Consentimento LGPD') }}
        </h3>

        <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200/80 bg-white/70 p-4 transition hover:border-amber-300 dark:border-amber-800/60 dark:bg-slate-900/50 dark:hover:border-amber-700">
            <input
                type="checkbox"
                name="patient_consent_confirmed"
                value="1"
                @checked(old('patient_consent_confirmed', $dr->patient_consent_at !== null))
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-amber-300 text-violet-600 focus:ring-violet-500/30 dark:border-amber-700 dark:bg-slate-800"
                required
            />
            <span class="text-sm leading-relaxed text-amber-950 dark:text-amber-100">
                {{ __('Confirmo que o paciente ou responsável legal autorizou o compartilhamento dos dados necessários (LGPD).') }}
            </span>
        </label>
        <x-input-error :messages="$errors->get('patient_consent_confirmed')" class="mt-2" />
    </section>

    <x-clinical-documents.partials.section-card
        :title="__('Anexos')"
        :description="__('PDF ou imagem (JPG, PNG, WebP). Máximo conforme política do servidor.')"
        icon="paper-clip"
        tone="indigo"
    >
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                'authorization_file' => __('Autorização assinada'),
                'institution_file' => __('Documento da instituição'),
                'complementary_file' => __('Relatório complementar'),
            ] as $field => $label)
                <div>
                    <x-input-label :for="$field" :value="$label" class="text-slate-700 dark:text-slate-200" />
                    <label
                        for="{{ $field }}"
                        class="mt-1.5 flex min-h-[7.5rem] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/80 px-3 py-4 text-center transition hover:border-violet-300 hover:bg-violet-50/40 focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-800/50 dark:hover:border-violet-500/50 dark:hover:bg-violet-950/20"
                    >
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-violet-500 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-600" aria-hidden="true">
                            <x-ui.icon name="upload" class="h-5 w-5" />
                        </span>
                        <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ __('Clique para escolher') }}</span>
                        <span class="text-[0.65rem] uppercase tracking-wide text-slate-400 dark:text-slate-500">PDF · JPG · PNG</span>
                        <input type="file" id="{{ $field }}" name="{{ $field }}" accept=".pdf,.jpg,.jpeg,.png,.webp" class="sr-only" />
                    </label>
                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                </div>
            @endforeach
        </div>

        <label class="{{ $checkboxCard }} mt-4">
            <input
                type="checkbox"
                name="authorization_attached"
                value="1"
                @checked(old('authorization_attached', $dr->authorization_attached))
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-violet-600 focus:ring-violet-500/30 dark:border-slate-500 dark:bg-slate-800"
            />
            <span>{{ __('Autorização já anexada (marcação manual)') }}</span>
        </label>
    </x-clinical-documents.partials.section-card>

    @if ($showActions && isset($submit))
        <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60 sm:flex-row sm:items-center sm:justify-between">
            @if (! empty($cancelHref))
                <a href="{{ $cancelHref }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">{{ __('Cancelar') }}</a>
            @else
                <span></span>
            @endif
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500">
                <x-ui.icon name="check" class="h-4 w-4" />
                {{ $submit }}
            </button>
        </div>
    @endif
</div>
