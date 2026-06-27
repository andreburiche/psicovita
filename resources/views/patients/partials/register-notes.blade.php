@props(['patient'])

<div class="grid gap-6 lg:grid-cols-12 lg:gap-8">
    <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60 lg:col-span-5">
        <div class="border-b border-slate-100 bg-gradient-to-r from-violet-50/80 to-indigo-50/50 px-5 py-4 dark:border-slate-700 dark:from-violet-950/40 dark:to-indigo-950/30">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-violet-900 dark:text-violet-200">
                <x-ui.icon name="id-card" class="h-4 w-4" />
                {{ __('Dados cadastrais') }}
            </h2>
        </div>
        <dl class="divide-y divide-slate-100 dark:divide-slate-700/80">
            @foreach ([
                ['label' => __('E-mail'), 'value' => $patient->email],
                ['label' => __('Telefone'), 'value' => $patient->phone ? (format_phone_br_human($patient->phone) ?: $patient->phone) : null],
                ['label' => __('CPF'), 'value' => $patient->cpf ? format_cpf_human($patient->cpf) : null],
                ['label' => __('Nascimento'), 'value' => optional($patient->birth_date)->format('d/m/Y')],
            ] as $row)
                <div class="flex gap-4 px-5 py-3.5">
                    <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $row['label'] }}</dt>
                    <dd class="min-w-0 flex-1 text-sm font-medium text-slate-900 dark:text-slate-100">{{ $row['value'] ?? '—' }}</dd>
                </div>
            @endforeach
            <div class="flex gap-4 px-5 py-3.5">
                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Endereço') }}</dt>
                <dd class="min-w-0 flex-1 text-sm font-medium text-slate-900 dark:text-slate-100">
                    @php
                        $line1 = trim(implode(', ', array_filter([$patient->address_street, $patient->address_number, $patient->address_complement])));
                        $line2 = trim(implode(' — ', array_filter([$patient->address_district, $patient->address_city && $patient->address_state ? $patient->address_city.'/'.$patient->address_state : ($patient->address_city ?? $patient->address_state)])));
                        $cep = $patient->address_postal_code ? format_cep_human($patient->address_postal_code) : '';
                    @endphp
                    @if ($line1 || $line2 || $cep)
                        @if ($line1)<span class="block">{{ $line1 }}</span>@endif
                        @if ($line2)<span class="block text-slate-600 dark:text-slate-300">{{ $line2 }}</span>@endif
                        @if ($cep)<span class="mt-1 block text-xs text-slate-500">{{ __('CEP') }} {{ $cep }}</span>@endif
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>
        <div class="border-t border-slate-100 px-5 py-3 dark:border-slate-700">
            <a href="{{ route('patients.edit', $patient) }}" class="text-xs font-semibold text-violet-600 hover:text-violet-500 dark:text-violet-400">
                {{ __('Editar dados') }} →
            </a>
        </div>
    </section>

    <section class="relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg shadow-slate-900/5 ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60 lg:col-span-7">
        <div class="absolute left-0 top-0 h-full w-1 bg-gradient-to-b from-violet-500 to-indigo-600" aria-hidden="true"></div>
        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 pl-7 dark:border-slate-700 dark:from-slate-900 dark:to-slate-900/90">
            <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                <x-ui.icon name="document-text" class="h-4 w-4 text-violet-500" />
                {{ __('Observações internas') }}
            </h2>
            <p class="mt-1 pl-6 text-xs text-slate-500 dark:text-slate-400">{{ __('Notas encriptadas — visíveis só na equipa clínica.') }}</p>
        </div>
        <div class="px-5 py-5 pl-7">
            <p class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800 dark:text-slate-100">{{ $patient->notes ?: __('Sem observações registadas.') }}</p>
        </div>
    </section>
</div>
