@props([
    'patient',
    'documentRequest' => null,
    'current' => null,
])

@php
    $documentsTabUrl = route('patients.show', ['patient' => $patient, 'tab' => 'document-requests']);

    $items = [
        ['label' => __('Pacientes'), 'href' => route('patients.index')],
        ['label' => $patient->name, 'href' => route('patients.show', $patient)],
        ['label' => __('Documentos'), 'href' => $documentsTabUrl],
    ];

    if ($documentRequest?->exists) {
        $showUrl = route('patients.document-requests.show', [$patient, $documentRequest]);

        if ($current === __('Editar')) {
            $items[] = ['label' => $documentRequest->institution_name, 'href' => $showUrl];
            $items[] = ['label' => __('Editar')];
        } else {
            $items[] = ['label' => $documentRequest->institution_name];
        }
    } elseif ($current) {
        $items[] = ['label' => $current];
    }
@endphp

<x-patient-breadcrumb :items="$items" />
