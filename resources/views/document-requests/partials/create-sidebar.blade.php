@props(['patient'])

<aside class="space-y-5 lg:sticky lg:top-24">
    <div class="overflow-hidden rounded-2xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/80 via-white to-violet-50/40 p-5 shadow-sm ring-1 ring-indigo-100 dark:border-indigo-900/40 dark:from-indigo-950/30 dark:via-slate-900/80 dark:to-violet-950/20 dark:ring-indigo-950">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-300">{{ __('Antes de registrar') }}</p>
        <ul class="mt-3 space-y-2.5">
            @foreach ([
                __('Confirme o consentimento LGPD antes de compartilhar dados com a instituição.'),
                __('Após salvar, gere o ofício em PDF e envie por e-mail direto da ficha da solicitação.'),
                __('Anexe autorizações e documentos recebidos para manter o histórico completo.'),
            ] as $tip)
                <li class="flex gap-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                    <x-ui.icon name="check" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-indigo-500" />
                    <span>{{ $tip }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Paciente') }}</p>
        <div class="mt-3 flex items-center gap-3">
            <x-patient-avatar :patient="$patient" size="sm" class="shrink-0 ring-2 ring-violet-100 dark:ring-violet-900/50" />
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ $patient->name }}</p>
                @if ($patient->birth_date)
                    <p class="text-xs text-slate-500">{{ __('Nasc.') }} {{ $patient->birth_date->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-900/40">
        <p class="flex items-start gap-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
            <x-ui.icon name="document-text" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
            {{ __('A solicitação aparece na aba Documentos da ficha. Você poderá acompanhar status, anexos e envio do ofício.') }}
        </p>
    </div>
</aside>
