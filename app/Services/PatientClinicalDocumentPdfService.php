<?php

namespace App\Services;

use App\Support\ClinicalPracticeBrand;
use App\Enums\PatientClinicalDocumentType;
use App\Models\Patient;
use App\Models\PatientClinicalDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Http\Response;

class PatientClinicalDocumentPdfService
{
    public function download(PatientClinicalDocument $document, User $actor): Response
    {
        $document->loadMissing(['patient', 'professional']);

        return $this->buildPdf($document, $actor)->download($this->filename($document));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function preview(
        Patient $patient,
        PatientClinicalDocumentType $type,
        string $issuedAt,
        array $payload,
        User $actor,
    ): Response {
        $document = new PatientClinicalDocument([
            'type' => $type,
            'issued_at' => $issuedAt,
            'payload' => $payload,
        ]);
        $document->setRelation('patient', $patient);

        return $this->buildPdf($document, $actor)
            ->stream(sprintf('preview-%s.pdf', $type->value));
    }

    public function filename(PatientClinicalDocument $document): string
    {
        return sprintf('%s-%d.pdf', $document->type->value, $document->id);
    }

    private function buildPdf(PatientClinicalDocument $document, User $actor): DomPdfDocument
    {
        $view = match ($document->type) {
            PatientClinicalDocumentType::Atestado => 'clinical-documents.pdf.atestado',
            PatientClinicalDocumentType::Declaracao => 'clinical-documents.pdf.declaracao',
            PatientClinicalDocumentType::Receita => 'clinical-documents.pdf.receita',
        };

        return PdfFacade::loadView($view, [
            'document' => $document,
            'professional' => $actor,
            'patient' => $document->patient,
            'payload' => $document->payload ?? [],
            'logoDataUri' => ClinicalPracticeBrand::logoDataUri($actor),
            'institutionName' => ClinicalPracticeBrand::institutionName($actor),
        ])->setPaper('a4');
    }

    public function defaultBody(PatientClinicalDocumentType $type, Patient $patient, array $context = []): string
    {
        return match ($type) {
            PatientClinicalDocumentType::Atestado => $this->defaultAtestadoBody($patient, $context),
            PatientClinicalDocumentType::Declaracao => $this->defaultDeclaracaoBody($patient),
            PatientClinicalDocumentType::Receita => '',
        };
    }

    /** @param  array<string, mixed>  $context */
    private function defaultAtestadoBody(Patient $patient, array $context): string
    {
        $kind = $context['kind'] ?? 'comparecimento';

        if ($kind === 'afastamento') {
            $days = (int) ($context['days'] ?? 1);
            $start = $context['start_date'] ?? now()->format('d/m/Y');
            $end = $context['end_date'] ?? now()->format('d/m/Y');

            return __('Atesto, para os devidos fins, que o(a) paciente :name necessita de afastamento de suas atividades por :days dia(s), no período de :start a :end, em razão de acompanhamento psicológico.', [
                'name' => $patient->name,
                'days' => $days,
                'start' => $start,
                'end' => $end,
            ]);
        }

        $date = $context['session_date'] ?? now()->format('d/m/Y');

        return __('Atesto, para os devidos fins, que o(a) paciente :name compareceu a consulta psicológica na data de :date, no horário regular de atendimento.', [
            'name' => $patient->name,
            'date' => $date,
        ]);
    }

    private function defaultDeclaracaoBody(Patient $patient): string
    {
        return __('Declaro, para os devidos fins, que o(a) paciente :name encontra-se em acompanhamento psicológico neste consultório, mantendo comparecimento regular conforme plano terapêutico acordado.', [
            'name' => $patient->name,
        ]);
    }
}
