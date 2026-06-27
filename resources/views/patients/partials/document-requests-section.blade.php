@props(['patient', 'documentRequests', 'patientDocuments', 'clinicalDocuments' => collect()])

<section class="space-y-6">
    @include('patients.partials.clinical-documents-generate', ['patient' => $patient])
    @include('patients.partials.clinical-documents-history', [
        'patient' => $patient,
        'clinicalDocuments' => $clinicalDocuments ?? collect(),
    ])

    {{-- Anexar documento na ficha (devolutiva, laudo, etc.) --}}
    @can('create', [\App\Models\PatientDocument::class, $patient])
        <div class="overflow-hidden rounded-2xl border border-emerald-200/90 bg-white shadow-lg ring-1 ring-emerald-100 dark:border-emerald-900/50 dark:bg-slate-900/80 dark:ring-emerald-950">
            <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50/90 to-teal-50/60 px-5 py-4 dark:border-emerald-900/40 dark:from-emerald-950/40">
                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">{{ __('Anexar documento na ficha') }}</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Use para arquivar devolutivas, laudos e arquivos recebidos — com ou sem solicitação formal.') }}</p>
            </div>
            <form method="post" action="{{ route('patients.documents.store', $patient) }}" enctype="multipart/form-data" class="space-y-4 p-5 sm:p-6">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="doc_title">{{ __('Título / descrição') }}</label>
                        <input type="text" id="doc_title" name="title" required placeholder="{{ __('Ex.: Devolutiva — Escola Municipal') }}"
                            class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900"
                            value="{{ old('title') }}" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="doc_category">{{ __('Tipo') }}</label>
                        <select id="doc_category" name="category" required class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900">
                            @foreach (\App\Enums\DocumentRequestFileCategory::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', 'resposta_instituicao') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="doc_received_at">{{ __('Data de recebimento') }}</label>
                        <input type="date" id="doc_received_at" name="received_at" class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900"
                            value="{{ old('received_at', now()->format('Y-m-d')) }}" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="doc_request_id">{{ __('Vincular à solicitação (opcional)') }}</label>
                        <select id="doc_request_id" name="document_request_id" class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900">
                            <option value="">{{ __('— Documento avulso na ficha —') }}</option>
                            @foreach ($documentRequests as $req)
                                <option value="{{ $req->id }}" @selected((int) old('document_request_id') === (int) $req->id)>
                                    {{ $req->request_date->format('d/m/Y') }} — {{ $req->institution_name }} ({{ $req->status->label() }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">{{ __('Ao vincular uma devolutiva, o status da solicitação pode ser atualizado para Respondido.') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="doc_file">{{ __('Arquivo') }}</label>
                        <input type="file" id="doc_file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="doc_notes">{{ __('Observações') }}</label>
                        <textarea id="doc_notes" name="notes" rows="2" class="mt-1 block w-full rounded-xl border-slate-200 text-sm dark:border-slate-600 dark:bg-slate-900">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <button type="submit" class="inline-flex rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500">{{ __('Anexar à ficha') }}</button>
            </form>
        </div>
    @endcan

    {{-- Documentos arquivados na ficha --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900/80">
        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-4 dark:border-slate-700 dark:from-slate-900">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Documentos na ficha do paciente') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ __('Todos os arquivos anexados (devolutivas, autorizações, relatórios).') }}</p>
        </div>
        <ul class="divide-y divide-slate-100 dark:divide-slate-700">
            @forelse ($patientDocuments as $doc)
                <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $doc->title }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $doc->category->label() }}
                            · {{ __('Recebido em') }} {{ optional($doc->received_at)->format('d/m/Y') ?? $doc->created_at->format('d/m/Y') }}
                            @if ($doc->documentRequest)
                                · {{ __('Solicitação:') }} {{ $doc->documentRequest->institution_name }}
                            @endif
                        </p>
                        <p class="text-xs text-slate-400">{{ $doc->original_name }}</p>
                    </div>
                    <div class="flex shrink-0 gap-3">
                        <a href="{{ route('patient-documents.download', $doc) }}" class="text-sm font-semibold text-violet-600 hover:text-violet-500">{{ __('Baixar') }}</a>
                        @can('delete', $doc)
                            <x-confirm-form
                                method="post"
                                action="{{ route('patient-documents.destroy', $doc) }}"
                                :title="__('Remover documento?')"
                                :message="__('O arquivo será excluído da ficha do paciente.')"
                                :confirm-label="__('Sim, remover')"
                                variant="danger"
                                :validate="false"
                                class="inline"
                            >
                                @csrf
                                @method('delete')
                                <button type="submit" class="text-sm font-semibold text-red-600">{{ __('Remover') }}</button>
                            </x-confirm-form>
                        @endcan
                    </div>
                </li>
            @empty
                <li class="rounded-xl border border-dashed border-emerald-200/70 bg-emerald-50/40 px-6 py-10 text-center dark:border-emerald-900/50 dark:bg-emerald-950/20">
                    <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400" aria-hidden="true">
                        <x-ui.icon name="document-text" class="h-5 w-5" />
                    </span>
                    <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Nenhum documento anexado') }}</p>
                    <p class="mx-auto mt-1 max-w-xs text-xs text-slate-500 dark:text-slate-400">{{ __('Use o formulário acima para arquivar devolutivas e laudos.') }}</p>
                </li>
            @endforelse
        </ul>
    </div>

    {{-- Solicitações formais --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900/80">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-gradient-to-r from-sky-50/90 to-violet-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:from-sky-950/40">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-sky-900 dark:text-sky-200">{{ __('Solicitações enviadas') }}</h3>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ __('Pedidos formais a instituições (ofício PDF).') }}</p>
            </div>
            @can('create', [\App\Models\DocumentRequest::class, $patient])
                <a href="{{ route('patients.document-requests.create', $patient) }}" class="inline-flex rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-500">{{ __('Nova solicitação') }}</a>
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700">
                <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-5 py-3">{{ __('Data') }}</th>
                        <th class="px-5 py-3">{{ __('Instituição') }}</th>
                        <th class="px-5 py-3">{{ __('Status') }}</th>
                        <th class="px-5 py-3">{{ __('Anexos') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80">
                    @forelse ($documentRequests as $req)
                        @php $attachCount = $patientDocuments->where('document_request_id', $req->id)->count(); @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50">
                            <td class="px-5 py-3 whitespace-nowrap">{{ $req->request_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900 dark:text-white">{{ $req->institution_name }}</p>
                                <p class="text-xs text-slate-500">{{ $req->institution_type->label() }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $req->status->badgeClass() }}">{{ $req->status->label() }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $attachCount }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('patients.document-requests.show', [$patient, $req]) }}" class="font-semibold text-violet-600">{{ __('Abrir') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12">
                                <div class="mx-auto max-w-sm text-center">
                                    <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400" aria-hidden="true">
                                        <x-ui.icon name="document-text" class="h-5 w-5" />
                                    </span>
                                    <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('Nenhuma solicitação registrada') }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Crie um pedido formal para escolas, empresas ou instituições.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
