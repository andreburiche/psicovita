@php
    use App\Enums\UserProfessionalFileCategory;

    $inputBase = 'mt-1.5 block w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-violet-500';
    $selectBase = $inputBase . ' appearance-none bg-[length:1rem] bg-[right_0.75rem_center] bg-no-repeat pr-10';
    $selectBg = "background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\");";
    $maxFiles = (int) config('profile.max_files_per_upload', 10);
    $maxMb = round(((int) config('profile.max_upload_kb', 10240)) / 1024, 1);
    $files = $user->professionalFiles;
@endphp

<section class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-sm ring-1 ring-slate-100 dark:border-slate-700 dark:bg-slate-900/80 dark:ring-slate-700/60">
    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300" aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </span>
        {{ __('Documentos profissionais') }}
    </h3>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        {{ __('Anexe currículo, certificados, diplomas e outros comprovantes. Pode enviar vários ficheiros de uma vez.') }}
    </p>

    @if (session('status') === 'professional-files-uploaded')
        <x-ui.success-alert class="mt-3" :title="__('Documentos enviados com sucesso')" />
    @elseif (session('status') === 'professional-file-deleted')
        <x-ui.success-alert class="mt-3" :title="__('Documento removido')" />
    @endif

    <form method="post" action="{{ route('profile.professional-files.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-4 dark:border-slate-600 dark:bg-slate-800/30">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="file_category" :value="__('Tipo de documento')" class="text-slate-700 dark:text-slate-200" />
                <select id="file_category" name="category" class="{{ $selectBase }}" style="{{ $selectBg }}" required>
                    @foreach (UserProfessionalFileCategory::options() as $value => $label)
                        <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('category')" />
            </div>

            <div>
                <x-input-label for="file_title" :value="__('Título (opcional)')" class="text-slate-700 dark:text-slate-200" />
                <input
                    id="file_title"
                    name="title"
                    type="text"
                    class="{{ $inputBase }}"
                    value="{{ old('title') }}"
                    placeholder="{{ __('Ex.: Currículo Lattes 2026') }}"
                />
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ __('Se enviar vários ficheiros, numeramos automaticamente.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('title')" />
            </div>
        </div>

        <div>
            <x-input-label for="professional_files" :value="__('Ficheiros')" class="text-slate-700 dark:text-slate-200" />
            <label
                for="professional_files"
                class="mt-1.5 flex min-h-[8rem] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 bg-white px-4 py-6 text-center transition hover:border-violet-300 hover:bg-violet-50/40 focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900/50 dark:hover:border-violet-500/50 dark:hover:bg-violet-950/20"
            >
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-100 text-violet-600 dark:bg-violet-950 dark:text-violet-400" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                </span>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('Clique ou arraste ficheiros') }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    {{ __('Até :count ficheiros · PDF, DOC, DOCX, JPG, PNG · máx. :size MB cada', ['count' => $maxFiles, 'size' => $maxMb]) }}
                </span>
                <input
                    id="professional_files"
                    name="files[]"
                    type="file"
                    class="sr-only"
                    multiple
                    required
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                />
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('files')" />
            <x-input-error class="mt-2" :messages="$errors->get('files.*')" />
        </div>

        <div class="flex justify-end border-t border-slate-200/80 pt-4 dark:border-slate-700">
            <x-primary-button>{{ __('Enviar documentos') }}</x-primary-button>
        </div>
    </form>

    @if ($files->isNotEmpty())
        <div class="mt-6">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ __('Documentos guardados') }}</h4>
            <ul class="mt-3 space-y-2">
                @foreach ($files as $file)
                    <li class="flex flex-col gap-3 rounded-xl border border-slate-200/90 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-slate-600 dark:bg-slate-900/60">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ $file->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $file->category->label() }}
                                    · {{ $file->humanSize() }}
                                    · {{ $file->created_at->format('d/m/Y') }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-slate-400 dark:text-slate-500">{{ $file->original_name }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
                            <a
                                href="{{ route('profile.professional-files.download', $file) }}"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-violet-300 hover:text-violet-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-violet-500"
                            >
                                {{ __('Transferir') }}
                            </a>
                            <form method="post" action="{{ route('profile.professional-files.destroy', $file) }}" onsubmit="return confirm(@js(__('Remover este documento?')));">
                                @csrf
                                @method('delete')
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200"
                                >
                                    {{ __('Remover') }}
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <p class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/50 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-600 dark:bg-slate-800/30 dark:text-slate-400">
            {{ __('Ainda não há documentos anexados ao seu perfil.') }}
        </p>
    @endif
</section>
