<button
    type="button"
    x-data="{ visible: false }"
    x-init="window.addEventListener('scroll', () => { visible = window.scrollY > 400 }, { passive: true })"
    x-show="visible"
    x-cloak
    x-transition
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    class="fixed bottom-6 left-6 z-40 inline-flex h-12 w-12 items-center justify-center rounded-full border border-slate-200/90 bg-white/95 text-slate-700 shadow-lg backdrop-blur-sm transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 dark:border-slate-600 dark:bg-slate-900/95 dark:text-slate-200 dark:hover:border-sky-600"
    aria-label="{{ __('Voltar ao topo') }}"
    title="{{ __('Voltar ao topo') }}"
>
    <x-ui.icon name="arrow-up" class="h-5 w-5" />
</button>
