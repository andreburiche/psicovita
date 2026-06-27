@props([
    'question',
    'namePrefix' => 'preview',
    'value' => null,
])

@php
    $q = $question;
    $baseName = $namePrefix.'['.$q->field_key.']';
    $oldKey = $namePrefix.'.'.$q->field_key;
    $displayValue = old($oldKey, $value ?? '');
    if (! is_string($displayValue) && ! is_numeric($displayValue)) {
        $displayValue = '';
    }
    $mask = $q->mask;
    $type = $q->field_type;
    $meta = $q->meta ?? [];
    $cepTargets = $meta['cep_targets'] ?? null;
@endphp

<div
    class="space-y-1.5"
    @if($cepTargets)
        data-cep-wrap="1"
        data-cep-targets="{{ json_encode($cepTargets) }}"
    @endif
    data-field-wrap
>
    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="f-{{ $q->field_key }}">
        {{ $q->label }}
        @if ($q->required)
            <span class="text-rose-600" aria-hidden="true">*</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea
            id="f-{{ $q->field_key }}"
            name="{{ $baseName }}"
            rows="3"
            @if($mask) data-mask="{{ $mask }}" @endif
            data-field-type="{{ $type }}"
            @if($q->required) required @endif
            class="mt-0 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            placeholder="{{ $q->label }}"
        >{{ $displayValue }}</textarea>
    @else
        <input
            id="f-{{ $q->field_key }}"
            name="{{ $baseName }}"
            type="{{ $type === 'email' ? 'email' : 'text' }}"
            @if($mask) data-mask="{{ $mask }}" @endif
            data-field-type="{{ $type }}"
            @if($q->required) required @endif
            @if($type === 'cep' || $mask === 'cep') data-cep-lookup="1" @endif
            value="{{ $displayValue }}"
            class="mt-0 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            placeholder="{{ match ($mask) { 'cpf' => '000.000.000-00', 'phone' => '(00) 00000-0000', 'cep' => '00000-000', 'date' => 'dd/mm/aaaa', default => $q->label } }}"
        />
    @endif
    <p class="hidden text-sm text-rose-600 dark:text-rose-400" data-field-error role="alert"></p>
</div>
