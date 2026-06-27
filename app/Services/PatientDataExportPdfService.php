<?php

namespace App\Services;

use App\Models\User;
use App\Support\AuditTrail;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Http\Response;

class PatientDataExportPdfService
{
    public function __construct(
        private readonly PatientDataExportService $exportService,
    ) {}

    public function download(User $user): Response
    {
        $payload = $this->exportService->buildPayload($user);

        AuditTrail::entity('export', 'patient_data_pdf', $user, [
            'patients_count' => count($payload['patients']),
        ], $user);

        return $this->buildPdf($payload, $user)->download($this->filename($user));
    }

    public function filename(User $user): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($user->normalizedEmail())) ?: 'conta';

        return 'dados-pessoais-'.$slug.'-'.now()->format('Y-m-d').'.pdf';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildPdf(array $payload, User $user): DomPdfDocument
    {
        return PdfFacade::loadView('patient.lgpd.pdf.export', [
            'payload' => $payload,
            'user' => $user,
            'company' => config('compliance.lgpd.company_name', config('app.name')),
        ])->setPaper('a4');
    }
}
