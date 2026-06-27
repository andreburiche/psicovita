<section id="precos" class="border-t border-sky-100/80 bg-gradient-to-b from-white to-sky-50/40 py-16 dark:from-slate-900 dark:to-slate-950 sm:py-20" x-data="{ cycle: 'monthly' }">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('Planos transparentes') }}</h2>
            <p class="mx-auto mt-3 max-w-2xl text-slate-600 dark:text-slate-400">
                {{ __('Comece com :days dias de teste grátis. Depois escolha o plano que combina com o seu consultório.', ['days' => $trialDays]) }}
            </p>

            <div class="mt-8 inline-flex rounded-2xl border border-slate-200 bg-white p-1 shadow-sm dark:border-slate-700 dark:bg-slate-900" role="group" aria-label="{{ __('Periodicidade') }}">
                <button
                    type="button"
                    class="rounded-xl px-5 py-2.5 text-sm font-semibold transition"
                    :class="cycle === 'monthly' ? 'bg-violet-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                    @click="cycle = 'monthly'"
                >
                    {{ __('Mensal') }}
                </button>
                <button
                    type="button"
                    class="rounded-xl px-5 py-2.5 text-sm font-semibold transition"
                    :class="cycle === 'yearly' ? 'bg-violet-600 text-white shadow-md' : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'"
                    @click="cycle = 'yearly'"
                >
                    {{ __('Anual') }}
                    @if (($maxAnnualSavingsPercent ?? 0) > 0)
                        <span class="ms-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                            -{{ $maxAnnualSavingsPercent }}%
                        </span>
                    @endif
                </button>
            </div>
        </div>

        <div class="mt-12 grid gap-6 lg:grid-cols-3">
            @foreach ($plans as $plan)
                @php
                    $highlight = $plan->slug === \App\Enums\SubscriptionPlanSlug::Premium;
                @endphp
                <article @class([
                    'relative flex flex-col rounded-3xl border p-6 shadow-soft',
                    'border-violet-300/80 bg-white ring-2 ring-violet-200/60 dark:border-violet-700 dark:bg-slate-900 dark:ring-violet-900/50' => $highlight,
                    'border-slate-200/80 bg-white dark:border-slate-700 dark:bg-slate-900/90' => ! $highlight,
                ])>
                    @if ($highlight)
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 px-3 py-1 text-xs font-bold text-white shadow-md">
                            {{ __('Mais popular') }}
                        </span>
                    @endif
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ $plan->name }}</h3>

                    <div class="mt-2" x-show="cycle === 'monthly'" x-cloak>
                        <p>
                            <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $plan->formattedPrice() }}</span>
                            <span class="text-sm text-slate-500 dark:text-slate-400">/{{ __('mês') }}</span>
                        </p>
                    </div>

                    <div class="mt-2" x-show="cycle === 'yearly'" x-cloak>
                        <p>
                            <span class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $plan->formattedAnnualPrice() }}</span>
                            <span class="text-sm text-slate-500 dark:text-slate-400">/{{ __('ano') }}</span>
                        </p>
                        <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                            {{ __('Equivalente a :price/mês', ['price' => $plan->formattedAnnualMonthlyEquivalent()]) }}
                        </p>
                        @if ($plan->annualSavingsPercent() > 0)
                            <p class="mt-1 text-xs font-semibold text-violet-700 dark:text-violet-300">
                                {{ __('Economize :percent% vs. mensal', ['percent' => $plan->annualSavingsPercent()]) }}
                            </p>
                        @endif
                    </div>

                    <ul class="mt-6 flex-1 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <li>✓ {{ __('Pacientes, sessões e prontuários') }}</li>
                        @if ($plan->hasFeature('use_ai'))
                            <li>✓ {{ __('IA clínica (transcrição e apoio)') }}</li>
                        @else
                            <li class="text-slate-400">— {{ __('Sem IA clínica') }}</li>
                        @endif
                        @if ($plan->hasFeature('multi_user'))
                            <li>✓ {{ __('Multi-utilizador (equipa)') }}</li>
                        @endif
                        @if ($plan->max_patients)
                            <li>✓ {{ __('Até :n pacientes', ['n' => $plan->max_patients]) }}</li>
                        @else
                            <li>✓ {{ __('Pacientes ilimitados') }}</li>
                        @endif
                    </ul>
                    @if (Route::has('register'))
                        <a
                            href="{{ route('register') }}"
                            @class([
                                'mt-8 inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold transition',
                                'bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-lg hover:from-violet-500 hover:to-indigo-500' => $highlight,
                                'border-2 border-slate-200 bg-white text-slate-800 hover:border-violet-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100' => ! $highlight,
                            ])
                        >
                            {{ __('Começar teste grátis') }}
                        </a>
                    @endif
                </article>
            @endforeach
        </div>

        <p class="mt-8 text-center text-xs text-slate-500 dark:text-slate-400">
            {{ __('Pagamentos de sessões pelos pacientes (PIX/cartão) disponíveis no portal do utente, com repasse opcional via Asaas.') }}
        </p>
    </div>
</section>
