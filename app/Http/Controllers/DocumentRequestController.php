<?php

namespace App\Http\Controllers;

use App\Enums\DocumentRequestFileCategory;
use App\Http\Requests\SendDocumentRequestEmailRequest;
use App\Http\Requests\StoreDocumentRequestRequest;
use App\Http\Requests\UpdateDocumentRequestRequest;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestAccessLog;
use App\Models\DocumentRequestFile;
use App\Models\Patient;
use App\Services\DocumentRequestAccessLogService;
use App\Services\DocumentRequestEmailService;
use App\Services\DocumentRequestPdfService;
use App\Services\DocumentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentRequestController extends Controller
{
    public function __construct(
        private readonly DocumentRequestService $service,
        private readonly DocumentRequestPdfService $pdfService,
        private readonly DocumentRequestEmailService $emailService,
        private readonly DocumentRequestAccessLogService $accessLog,
    ) {}

    public function index(Request $request, Patient $patient): View
    {
        $this->authorize('viewAny', [DocumentRequest::class, $patient]);

        $documentRequests = $this->service->paginateForPatient($patient);

        return view('document-requests.index', compact('patient', 'documentRequests'));
    }

    public function create(Patient $patient): View
    {
        $this->authorize('create', [DocumentRequest::class, $patient]);

        return view('document-requests.create', [
            'patient' => $patient,
            'documentRequest' => new DocumentRequest([
                'request_date' => now()->toDateString(),
            ]),
        ]);
    }

    public function store(StoreDocumentRequestRequest $request, Patient $patient): RedirectResponse
    {
        $documentRequest = $this->service->create($patient, $request->user(), $request->validated());

        return redirect()
            ->route('patients.document-requests.show', [$patient, $documentRequest])
            ->with('status', __('Solicitação de documentos registrada.'));
    }

    public function show(Request $request, Patient $patient, DocumentRequest $documentRequest): View
    {
        $this->authorize('view', $documentRequest);
        abort_unless((int) $documentRequest->patient_id === (int) $patient->id, 404);

        $documentRequest->load(['files.uploader', 'accessLogs.user', 'createdByUser', 'updatedByUser', 'consentRecordedByUser', 'lastEmailSentByUser']);
        $this->service->recordView($documentRequest, $request->user());

        return view('document-requests.show', compact('patient', 'documentRequest'));
    }

    public function edit(Patient $patient, DocumentRequest $documentRequest): View
    {
        $this->authorize('update', $documentRequest);
        abort_unless((int) $documentRequest->patient_id === (int) $patient->id, 404);

        $documentRequest->load('files');

        return view('document-requests.edit', compact('patient', 'documentRequest'));
    }

    public function update(UpdateDocumentRequestRequest $request, Patient $patient, DocumentRequest $documentRequest): RedirectResponse
    {
        abort_unless((int) $documentRequest->patient_id === (int) $patient->id, 404);

        $this->service->update($documentRequest, $request->user(), $request->validated());

        return redirect()
            ->route('patients.document-requests.show', [$patient, $documentRequest])
            ->with('status', __('Solicitação atualizada.'));
    }

    public function destroy(Request $request, Patient $patient, DocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorize('delete', $documentRequest);
        abort_unless((int) $documentRequest->patient_id === (int) $patient->id, 404);

        $this->service->delete($documentRequest, $request->user());

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'document-requests'])
            ->with('status', __('Solicitação removida.'));
    }

    public function pdf(Request $request, Patient $patient, DocumentRequest $documentRequest): Response
    {
        $this->authorize('downloadPdf', $documentRequest);
        abort_unless((int) $documentRequest->patient_id === (int) $patient->id, 404);

        return $this->pdfService->download($documentRequest, $request->user());
    }

    public function sendEmail(SendDocumentRequestEmailRequest $request, Patient $patient, DocumentRequest $documentRequest): RedirectResponse
    {
        abort_unless((int) $documentRequest->patient_id === (int) $patient->id, 404);

        try {
            $this->emailService->send($documentRequest, $request->user(), $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }

        return back()->with('status', __('Solicitação enviada por e-mail com o ofício em PDF anexo.'));
    }

    public function storeFile(Request $request, Patient $patient, DocumentRequest $documentRequest): RedirectResponse
    {
        $this->authorize('uploadFile', $documentRequest);
        abort_unless((int) $documentRequest->patient_id === (int) $patient->id, 404);

        $validated = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_column(DocumentRequestFileCategory::cases(), 'value'))],
            'file' => ['required', 'file', 'max:'.(int) config('document_requests.max_upload_kb', 10240), 'mimes:'.implode(',', config('document_requests.allowed_mimes', ['pdf']))],
        ]);

        $category = DocumentRequestFileCategory::from($validated['category']);
        $this->service->storeFile($documentRequest, $validated['file'], $category, $request->user());

        return back()->with('status', __('Anexo enviado.'));
    }

    public function downloadFile(Request $request, DocumentRequestFile $documentRequestFile): StreamedResponse
    {
        $documentRequest = $documentRequestFile->documentRequest;
        $this->authorize('view', $documentRequest);

        $this->accessLog->record($documentRequest, DocumentRequestAccessLog::ACTION_FILE_DOWNLOADED, $request->user());

        abort_unless(Storage::disk('local')->exists($documentRequestFile->file_path), 404);

        return Storage::disk('local')->download(
            $documentRequestFile->file_path,
            $documentRequestFile->original_name
        );
    }

    public function destroyFile(Request $request, DocumentRequestFile $documentRequestFile): RedirectResponse
    {
        $documentRequest = $documentRequestFile->documentRequest;
        $this->authorize('update', $documentRequest);

        $this->service->deleteFile($documentRequestFile, $request->user());

        return back()->with('status', __('Anexo removido.'));
    }
}
