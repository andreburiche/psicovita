@props([
    'patient',
    'showListLink' => false,
])

<section
    class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-900 via-violet-950 to-indigo-950 shadow-xl shadow-violet-950/20 ring-1 ring-white/10 dark:border-slate-700/50"
    aria-label="{{ __('Identificação do paciente') }}"
>
    <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-violet-500/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-16 -left-16 h-48 w-48 rounded-full bg-indigo-500/15 blur-3xl" aria-hidden="true"></div>

    <div class="relative p-5 sm:p-6 lg:p-8">
        {{-- Identidade: avatar + dados — sempre primeiro --}}
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:gap-6">
            <x-patient-avatar
                :patient="$patient"
                size="hero"
                class="mx-auto shrink-0 shadow-inner shadow-black/20 ring-2 ring-white/20 sm:mx-0"
            />

            <div class="min-w-0 flex-1 text-center sm:text-left">
                @if ($showListLink)
                    <a
                        href="{{ route('patients.index') }}"
                        class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-violet-200/90 transition hover:text-white"
                    >
                        <x-ui.icon name="arrow-left" class="h-3.5 w-3.5" />
                        {{ __('Lista de pacientes') }}
                    </a>
                @endif

                <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-violet-200/80">{{ __('Ficha clínica') }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ $patient->name }}</h1>

                <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                    @if ($patient->email)
                        <a
                            href="mailto:{{ $patient->email }}"
                            class="inline-flex max-w-full items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/15 backdrop-blur-sm transition hover:bg-white/15 sm:text-sm"
                        >
                            <x-ui.icon name="mail" class="h-3.5 w-3.5 shrink-0 text-violet-200" />
                            <span class="truncate">{{ $patient->email }}</span>
                        </a>
                    @endif
                    @if ($patient->phone)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/15 backdrop-blur-sm sm:text-sm">
                            <x-ui.icon name="phone" class="h-3.5 w-3.5 text-violet-200" />
                            {{ format_phone_br_human($patient->phone) ?: $patient->phone }}
                        </span>
                    @endif
                    @if ($patient->birth_date)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/15 backdrop-blur-sm sm:text-sm">
                            <x-ui.icon name="calendar" class="h-3.5 w-3.5 text-violet-200" />
                            {{ optional($patient->birth_date)->format('d/m/Y') }}
                        </span>
                    @endif
                    @if ($patient->cpf)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white ring-1 ring-white/15 backdrop-blur-sm sm:text-sm">
                            <x-ui.icon name="id-card" class="h-3.5 w-3.5 shrink-0 text-violet-200" />
                            {{ format_cpf_human($patient->cpf) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Ações: barra inferior, nunca sobre a identidade --}}
        @isset($actions)
            <div class="mt-6 border-t border-white/10 pt-5">
                {{ $actions }}
            </div>
        @endisset
    </div>
</section>
