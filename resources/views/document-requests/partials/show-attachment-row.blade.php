@props(['file', 'documentRequest'])

@php
    $extension = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
    $categoryBadge = match ($file->category) {
        \App\Enums\DocumentRequestFileCategory::Authorization => 'bg-violet-100 text-violet-800 ring-violet-200/80 dark:bg-violet-950/50 dark:text-violet-200 dark:ring-violet-800',
        \App\Enums\DocumentRequestFileCategory::InstitutionResponse => 'bg-emerald-100 text-emerald-800 ring-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-800',
        \App\Enums\DocumentRequestFileCategory::ComplementaryReport => 'bg-sky-100 text-sky-800 ring-sky-200/80 dark:bg-sky-950/50 dark:text-sky-200 dark:ring-sky-800',
    };
    $fileIcon = match ($extension) {
        'pdf' => 'text-rose-500 dark:text-rose-400',
        'jpg', 'jpeg', 'png', 'webp' => 'text-sky-500 dark:text-sky-400',
        default => 'text-slate-400 dark:text-slate-500',
    };
@endphp

<li class="group px-4 py-4 sm:px-5">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 p-4 transition hover:border-slate-300/80 hover:bg-white hover:shadow-sm dark:border-slate-700/80 dark:bg-slate-800/30 dark:hover:border-slate-600 dark:hover:bg-slate-800/60 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-600 {{ $fileIcon }}" aria-hidden="true">
                @if ($extension === 'pdf')
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                @elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true))
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                @else
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01L8.25 18.75m7.688-7.688l-3.375 3.375" />
                    </svg>
                @endif
            </span>
            <div class="min-w-0">
                <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $file->original_name }}</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1 {{ $categoryBadge }}">
                        {{ $file->category->label() }}
                    </span>
                    <span class="text-xs text-slate-500">{{ $file->created_at->format('d/m/Y H:i') }}</span>
                    @if ($file->uploader)
                        <span class="text-xs text-slate-400">· {{ $file->uploader->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
            <a
                href="{{ route('document-request-files.download', $file) }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-50 px-3.5 py-2 text-xs font-semibold text-violet-700 ring-1 ring-violet-200/80 transition hover:bg-violet-100 dark:bg-violet-950/50 dark:text-violet-300 dark:ring-violet-800 dark:hover:bg-violet-900/50"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                {{ __('Baixar') }}
            </a>
            @can('update', $documentRequest)
                <x-confirm-form
                    method="post"
                    action="{{ route('document-request-files.destroy', $file) }}"
                    :title="__('Remover anexo?')"
                    :message="__('O arquivo será excluído desta solicitação.')"
                    :details="[['label' => __('Arquivo'), 'value' => $file->original_name]]"
                    :confirm-label="__('Sim, remover')"
                    variant="danger"
                    :validate="false"
                    class="inline"
                >
                    @csrf
                    @method('delete')
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-700 ring-1 ring-rose-200/80 transition hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900 dark:hover:bg-rose-950/70"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.038-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        {{ __('Remover') }}
                    </button>
                </x-confirm-form>
            @endcan
        </div>
    </div>
</li>
