<?php

namespace App\Services;

use App\Models\DocumentRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Http\Response;

class DocumentRequestPdfService
{
    public function __construct(
        private readonly DocumentRequestAccessLogService $accessLog,
    ) {}

    public function download(DocumentRequest $documentRequest, User $actor): Response
    {
        $this->accessLog->record($documentRequest, \App\Models\DocumentRequestAccessLog::ACTION_PDF, $actor);

        return $this->buildPdf($documentRequest, $actor)->download($this->filename($documentRequest));
    }

    public function rawOutput(DocumentRequest $documentRequest, User $actor): string
    {
        return $this->buildPdf($documentRequest, $actor)->output();
    }

    public function filename(DocumentRequest $documentRequest): string
    {
        return sprintf('oficio-solicitacao-%d.pdf', $documentRequest->id);
    }

    private function buildPdf(DocumentRequest $documentRequest, User $actor): DomPdfDocument
    {
        $documentRequest->loadMissing(['patient', 'professional']);

        return PdfFacade::loadView('document-requests.pdf.officio', [
            'documentRequest' => $documentRequest,
            'professional' => $actor,
            'patient' => $documentRequest->patient,
        ])->setPaper('a4');
    }
}
