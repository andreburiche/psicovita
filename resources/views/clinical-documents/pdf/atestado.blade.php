@component('clinical-documents.pdf.layout', [
    'document' => $document,
    'professional' => $professional,
    'patient' => $patient,
    'payload' => $payload,
    'title' => $document->type->pdfTitle(),
    'logoDataUri' => $logoDataUri,
    'institutionName' => $institutionName,
])
    <div class="content">
        @if (($payload['kind'] ?? '') === 'afastamento')
            <p class="block-title">{{ __('Tipo') }}: {{ __('Afastamento') }}</p>
        @else
            <p class="block-title">{{ __('Tipo') }}: {{ __('Comparecimento') }}</p>
        @endif

        <p>{!! nl2br(e($payload['body'] ?? '')) !!}</p>

        @if (filled($payload['cid'] ?? null))
            <p class="legal"><strong>CID:</strong> {{ $payload['cid'] }} — {{ __('Informação clínica sigilosa, fornecida apenas quando legalmente necessária.') }}</p>
        @endif
    </div>
@endcomponent
