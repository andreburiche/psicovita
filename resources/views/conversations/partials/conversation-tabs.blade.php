@if (config('psiconecta.chatbot.enabled') && config('psiconecta.chatbot.widget_enabled'))
    <nav class="flex gap-2 border-b border-slate-200 pb-1 dark:border-slate-700" aria-label="{{ __('Tipo de conversa') }}">
        <a
            href="{{ route('conversations.index') }}"
            @class([
                'rounded-t-lg px-4 py-2 text-sm font-semibold transition',
                'bg-white text-violet-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-violet-300 dark:ring-slate-700' => request()->routeIs('conversations.index', 'conversations.show'),
                'text-slate-500 hover:text-violet-600' => ! request()->routeIs('conversations.index', 'conversations.show'),
            ])
        >
            {{ __('Terapia') }}
        </a>
        <a
            href="{{ route('conversations.support.index') }}"
            @class([
                'rounded-t-lg px-4 py-2 text-sm font-semibold transition',
                'bg-white text-violet-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-violet-300 dark:ring-slate-700' => request()->routeIs('conversations.support.*'),
                'text-slate-500 hover:text-violet-600' => ! request()->routeIs('conversations.support.*'),
            ])
        >
            {{ __('Apoio') }}
        </a>
    </nav>
@endif
