<?php

namespace App\Http\Controllers;

use App\Enums\PatientClinicalDocumentType;
use App\Http\Requests\StorePatientClinicalDocumentRequest;
use App\Models\Patient;
use App\Models\PatientClinicalDocument;
use App\Services\PatientClinicalDocumentPdfService;
use App\Support\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PatientClinicalDocumentController extends Controller
{
    public function __construct(
        private readonly PatientClinicalDocumentPdfService $pdfService,
    ) {}

    public function create(Request $request, Patient $patient, string $type): View
    {
        $documentType = PatientClinicalDocumentType::tryFromRoute($type);
        abort_if($documentType === null, 404);

        $this->authorize('create', [PatientClinicalDocument::class, $patient]);

        $defaultBody = $this->pdfService->defaultBody($documentType, $patient, [
            'kind' => old('atestado_kind', 'comparecimento'),
            'days' => old('days', 1),
            'start_date' => old('start_date') ? \Illuminate\Support\Carbon::parse(old('start_date'))->format('d/m/Y') : now()->format('d/m/Y'),
            'end_date' => old('end_date') ? \Illuminate\Support\Carbon::parse(old('end_date'))->format('d/m/Y') : now()->format('d/m/Y'),
            'session_date' => old('session_date') ? \Illuminate\Support\Carbon::parse(old('session_date'))->format('d/m/Y') : now()->format('d/m/Y'),
        ]);

        return view('clinical-documents.create', [
            'patient' => $patient,
            'documentType' => $documentType,
            'defaultBody' => $defaultBody,
        ]);
    }

    public function preview(StorePatientClinicalDocumentRequest $request, Patient $patient): RedirectResponse
    {
        $this->authorize('create', [PatientClinicalDocument::class, $patient]);

        $token = Str::uuid()->toString();

        Cache::put($this->previewCacheKey($token), [
            'patient_id' => $patient->id,
            'user_id' => $request->user()->id,
            'type' => $request->input('type'),
            'issued_at' => $request->input('issued_at'),
            'payload' => $request->payload(),
        ], now()->addMinutes(15));

        return redirect()->route('patients.clinical-documents.preview.show', [$patient, $token]);
    }

    public function showPreview(Request $request, Patient $patient, string $token): Response|RedirectResponse
    {
        $this->authorize('create', [PatientClinicalDocument::class, $patient]);

        $data = Cache::get($this->previewCacheKey($token));

        if (
            ! is_array($data)
            || (int) ($data['patient_id'] ?? 0) !== (int) $patient->id
            || (int) ($data['user_id'] ?? 0) !== (int) $request->user()->id
        ) {
            return redirect()
                ->route('patients.show', ['patient' => $patient, 'tab' => 'document-requests'])
                ->with('error', __('Pré-visualização indisponível ou expirada. Abra o formulário e clique em «Pré-visualizar» novamente.'));
        }

        return $this->pdfService->preview(
            $patient,
            PatientClinicalDocumentType::from((string) $data['type']),
            (string) $data['issued_at'],
            $data['payload'],
            $request->user(),
        );
    }

    public function previewUnavailable(Patient $patient): RedirectResponse
    {
        $this->authorize('create', [PatientClinicalDocument::class, $patient]);

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'document-requests'])
            ->with('error', __('Use o botão «Pré-visualizar» no formulário do documento para gerar a pré-visualização.'));
    }

    public function store(StorePatientClinicalDocumentRequest $request, Patient $patient): RedirectResponse
    {
        $this->authorize('create', [PatientClinicalDocument::class, $patient]);

        $document = PatientClinicalDocument::query()->create([
            'patient_id' => $patient->id,
            'professional_id' => $request->user()->clinicalPracticeId(),
            'type' => $request->input('type'),
            'issued_at' => $request->input('issued_at'),
            'payload' => $request->payload(),
        ]);

        AuditTrail::entity('create', 'patient_clinical_documents', $document, null, $request->user());

        return redirect()
            ->route('patients.clinical-documents.pdf', [$patient, $document])
            ->with('status', __(':type gerado com sucesso.', ['type' => $document->type->label()]));
    }

    public function pdf(Request $request, Patient $patient, PatientClinicalDocument $clinicalDocument): Response
    {
        abort_unless((int) $clinicalDocument->patient_id === (int) $patient->id, 404);

        $this->authorize('view', $clinicalDocument);

        AuditTrail::entity('view', 'patient_clinical_documents', $clinicalDocument, null, $request->user());

        return $this->pdfService->download($clinicalDocument, $request->user());
    }

    private function previewCacheKey(string $token): string
    {
        return 'clinical-document-preview:'.$token;
    }
}
