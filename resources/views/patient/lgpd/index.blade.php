<x-patient-layout>
    <x-slot name="header">{{ __('Privacidade') }}</x-slot>

    <x-patient-portal-shell>
    <x-patient-portal-breadcrumb :items="[
        ['label' => __('Início'), 'href' => route('patient.home')],
        ['label' => __('Privacidade')],
    ]" />

    <x-patient-portal-hero
        :title="__('Privacidade e direitos LGPD')"
        :subtitle="__('Exercite os seus direitos como titular de dados — exportação, correção, eliminação e mais. Resposta em até :days dias úteis.', ['days' => $slaDays])"
        icon="shield-check"
    >
        <x-slot name="actions">
            <a
                href="{{ route('legal.privacy') }}"
                class="inline-flex items-center rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/15"
            >
                {{ __('Política de privacidade') }}
            </a>
        </x-slot>
    </x-patient-portal-hero>

    <section class="overflow-hidden rounded-2xl border border-emerald-200/80 bg-white shadow-sm ring-1 ring-emerald-100 dark:border-emerald-800/40 dark:bg-slate-900/80 dark:ring-emerald-900/30">
        <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 dark:border-emerald-900/40 dark:from-emerald-950/40 dark:to-slate-900">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Exportar os seus dados') }}</h2>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                {{ __('Inclui conta, sessões, pagamentos e mensagens. Prontuário clínico requer solicitação formal abaixo.') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-3 p-5">
            <a
                href="{{ route('patient.lgpd.export') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
            >
                <x-ui.icon name="download" class="h-4 w-4" />
                {{ __('JSON') }}
            </a>
            <a
                href="{{ route('patient.lgpd.export.pdf') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
            >
                <x-ui.icon name="document-text" class="h-4 w-4" />
                {{ __('PDF') }}
            </a>
        </div>
        <p class="border-t border-emerald-100 px-5 py-3 text-xs text-slate-500 dark:border-emerald-900/40 dark:text-slate-400">
            {{ __('Encarregado (DPO):') }}
            <a href="mailto:{{ $dpoEmail }}" class="font-semibold text-emerald-700 hover:underline dark:text-emerald-400">{{ $dpoName }} — {{ $dpoEmail }}</a>
        </p>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50">
        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Nova solicitação') }}</h2>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Art. 18 LGPD — confirmação, acesso, correção, portabilidade, eliminação, oposição ou revogação.') }}</p>
        </div>

        <form method="POST" action="{{ route('patient.lgpd.store') }}" class="space-y-5 p-5">
            @csrf

            <div>
                <x-input-label for="type" :value="__('Tipo de solicitação')" />
                <select
                    id="type"
                    name="type"
                    required
                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                >
                    <option value="">{{ __('Selecione…') }}</option>
                    @foreach ($requestTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('type')" />
            </div>

            @if ($patients->count() > 1)
                <div>
                    <x-input-label for="patient_id" :value="__('Ficha vinculada')" />
                    <select
                        id="patient_id"
                        name="patient_id"
                        required
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    >
                        <option value="">{{ __('Selecione o consultório…') }}</option>
                        @foreach ($patients as $patient)
                            <option value="{{ $patient->id }}" @selected((int) old('patient_id') === $patient->id)>
                                {{ $patient->professional?->name ?? __('Profissional') }} — {{ $patient->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('patient_id')" />
                </div>
            @endif

            <div>
                <x-input-label for="details" :value="__('Detalhes (opcional)')" />
                <textarea
                    id="details"
                    name="details"
                    rows="4"
                    maxlength="5000"
                    class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
                    placeholder="{{ __('Descreva o que deseja solicitar ou corrigir…') }}"
                >{{ old('details') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('details')" />
            </div>

            <x-primary-button class="!bg-emerald-600 hover:!bg-emerald-500">
                {{ __('Enviar solicitação') }}
            </x-primary-button>
        </form>
    </section>

    @if ($requests->isNotEmpty())
        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/50">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Suas solicitações recentes') }}</h2>
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                @foreach ($requests as $item)
                    <li class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item->type->label() }}</p>
                            @if ($item->patient)
                                <p class="mt-0.5 text-xs text-slate-500">{{ $item->patient->name }}</p>
                            @endif
                            <p class="mt-1 text-xs text-slate-500">{{ $item->created_at->translatedFormat('d M Y H:i') }}</p>
                            @if ($item->response_notes && in_array($item->status, [\App\Enums\DataSubjectRequestStatus::Completed, \App\Enums\DataSubjectRequestStatus::Rejected], true))
                                <p class="mt-2 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    <span class="font-semibold">{{ __('Resposta:') }}</span> {{ $item->response_notes }}
                                </p>
                            @endif
                        </div>
                        <span @class([
                            'inline-flex shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            $item->status->badgeClass(),
                        ])>{{ $item->status->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
    </x-patient-portal-shell>
</x-patient-layout>
