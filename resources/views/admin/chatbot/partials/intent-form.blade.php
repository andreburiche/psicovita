@php
    $isEdit = $intent !== null;
    $phrases = old('training_phrases', $intent ? implode("\n", $intent->training_phrases) : '');
    $bodyTemplate = old('body_template', $response?->body_template ?? '');
    $quickReplies = old('quick_replies', $response?->quick_replies ? implode(', ', $response->quick_replies) : '');
@endphp

<form method="post" action="{{ $action }}" class="mt-4 grid gap-4">
    @csrf
    @if (! empty($method))
        @method($method)
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label :for="$formId.'_label'" :value="__('Nome')" />
            <input id="{{ $formId }}_label" name="label" type="text" required maxlength="120" value="{{ old('label', $intent?->label) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
            <x-input-error :messages="$errors->get('label')" class="mt-1" />
        </div>
        <div>
            <x-input-label :for="$formId.'_slug'" :value="__('Slug')" />
            <input id="{{ $formId }}_slug" name="slug" type="text" required maxlength="80" pattern="[a-z0-9_]+" value="{{ old('slug', $intent?->slug) }}" class="mt-1 block w-full rounded-xl border-slate-300 font-mono text-sm dark:border-slate-600 dark:bg-slate-900" />
            <x-input-error :messages="$errors->get('slug')" class="mt-1" />
        </div>
    </div>

    <div>
        <x-input-label :for="$formId.'_phrases'" :value="__('Frases de treino (uma por linha)')" />
        <textarea id="{{ $formId }}_phrases" name="training_phrases" rows="4" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900">{{ $phrases }}</textarea>
        <x-input-error :messages="$errors->get('training_phrases')" class="mt-1" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-input-label :for="$formId.'_action'" :value="__('Acção')" />
            <select id="{{ $formId }}_action" name="route_action" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900">
                <option value="reply" @selected(old('route_action', $intent?->route_action) === 'reply')>{{ __('Responder') }}</option>
                <option value="handoff" @selected(old('route_action', $intent?->route_action) === 'handoff')>{{ __('Handoff humano') }}</option>
            </select>
        </div>
        <div>
            <x-input-label :for="$formId.'_queue'" :value="__('Fila (handoff)')" />
            <select id="{{ $formId }}_queue" name="target_queue_id" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900">
                <option value="">{{ __('—') }}</option>
                @foreach ($queues as $queue)
                    <option value="{{ $queue->id }}" @selected((string) old('target_queue_id', $intent?->target_queue_id) === (string) $queue->id)>{{ $queue->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('target_queue_id')" class="mt-1" />
        </div>
        <div>
            <x-input-label :for="$formId.'_priority'" :value="__('Prioridade')" />
            <input id="{{ $formId }}_priority" name="priority" type="number" min="0" max="999" required value="{{ old('priority', $intent?->priority ?? 50) }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
        </div>
    </div>

    <div>
        <x-input-label :for="$formId.'_body'" :value="__('Modelo de resposta')" />
        <textarea id="{{ $formId }}_body" name="body_template" rows="3" required class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900">{{ $bodyTemplate }}</textarea>
        <p class="mt-1 text-xs text-slate-500">{{ __('Placeholders: :protocol, :name') }}</p>
        <x-input-error :messages="$errors->get('body_template')" class="mt-1" />
    </div>

    <div>
        <x-input-label :for="$formId.'_quick'" :value="__('Respostas rápidas (opcional, separadas por vírgula)')" />
        <input id="{{ $formId }}_quick" name="quick_replies" type="text" value="{{ $quickReplies }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900" />
    </div>

    @if ($isEdit)
        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $intent->is_active)) class="rounded border-slate-300 text-violet-600" />
            {{ __('Intent activo') }}
        </label>
    @endif

    <div>
        <x-primary-button>{{ $isEdit ? __('Guardar alterações') : __('Criar intent') }}</x-primary-button>
    </div>
</form>
