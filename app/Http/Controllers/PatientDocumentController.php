<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Services\PatientDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientDocumentController extends Controller
{
    public function __construct(
        private readonly PatientDocumentService $service,
    ) {}

    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorize('create', [PatientDocument::class, $patient]);

        $maxKb = (int) config('document_requests.max_upload_kb', 10240);
        $mimes = implode(',', config('document_requests.allowed_mimes', ['pdf']));

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', array_column(\App\Enums\DocumentRequestFileCategory::cases(), 'value'))],
            'document_request_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($patient) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $exists = \App\Models\DocumentRequest::query()
                        ->where('patient_id', $patient->id)
                        ->whereKey($value)
                        ->exists();
                    if (! $exists) {
                        $fail(__('Solicitação inválida para este paciente.'));
                    }
                },
            ],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:'.$maxKb, 'mimes:'.$mimes],
        ]);

        $this->service->attach($patient, $request->user(), $validated['file'], $validated);

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'document-requests'])
            ->with('status', __('Documento anexado à ficha do paciente.'));
    }

    public function download(PatientDocument $patientDocument): StreamedResponse
    {
        $this->authorize('viewAny', [PatientDocument::class, $patientDocument->patient]);

        abort_unless(Storage::disk('local')->exists($patientDocument->file_path), 404);

        return Storage::disk('local')->download(
            $patientDocument->file_path,
            $patientDocument->original_name
        );
    }

    public function destroy(Request $request, PatientDocument $patientDocument): RedirectResponse
    {
        $this->authorize('delete', $patientDocument);

        $patient = $patientDocument->patient;
        $this->service->delete($patientDocument, $request->user());

        return redirect()
            ->route('patients.show', ['patient' => $patient, 'tab' => 'document-requests'])
            ->with('status', __('Documento removido da ficha.'));
    }
}
