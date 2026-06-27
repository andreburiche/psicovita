@props([
    'id',
    'labels' => [],
    'values' => [],
    'labelHeading' => null,
    'valueHeading' => null,
    'caption' => null,
])

@php
    $labelHeading ??= __('Período');
    $valueHeading ??= __('Valor');
    $caption ??= __('Dados tabulares equivalentes ao gráfico.');
@endphp

<table id="{{ $id }}" class="sr-only">
    <caption>{{ $caption }}</caption>
    <thead>
        <tr>
            <th scope="col">{{ $labelHeading }}</th>
            <th scope="col">{{ $valueHeading }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($labels as $index => $label)
            <tr>
                <td>{{ $label }}</td>
                <td>{{ $values[$index] ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
