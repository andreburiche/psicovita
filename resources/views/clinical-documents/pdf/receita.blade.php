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
        <p class="block-title">{{ __('Prescrição') }}</p>
        <div class="medications">{{ $payload['medications'] ?? '' }}</div>

        @if (filled($payload['observations'] ?? null))
            <p class="block-title">{{ __('Observações') }}</p>
            <p>{!! nl2br(e($payload['observations'])) !!}</p>
        @endif

        <p class="legal">{{ __('Uso conforme orientação profissional. Em caso de dúvida, consulte o prescritor.') }}</p>
    </div>
@endcomponent
