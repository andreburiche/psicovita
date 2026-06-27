@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
])

@php
    $fieldId = $attributes->get('id') ?? ($name.'_'.uniqid());
@endphp

<div>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</label>
    <select
        name="{{ $name }}"
        id="{{ $fieldId }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-indigo-400']) }}
    >
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected(old($name, $value) == $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p>
    @enderror
</div>
