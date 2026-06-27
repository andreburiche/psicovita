@component('clinical-documents.pdf.layout', [
    'document' => $document,
    'professional' => $professional,
    'patient' => $patient,
    'payload' => $payload,
    'title' => filled($payload['subject'] ?? null) ? mb_strtoupper($payload['subject']) : $document->type->pdfTitle(),
    'logoDataUri' => $logoDataUri,
    'institutionName' => $institutionName,
])
    <div class="content">
        <p>{!! nl2br(e($payload['body'] ?? '')) !!}</p>
    </div>
@endcomponent
