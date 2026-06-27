<?php

namespace App\Http\Controllers;

use App\Enums\DataSubjectRequestType;
use App\Models\DataSubjectRequest;
use App\Services\DataSubjectRequestService;
use App\Services\PatientDataExportPdfService;
use App\Services\PatientDataExportService;
use App\Support\PatientAccountResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PatientLgpdController extends Controller
{
    public function __construct(
        private readonly PatientAccountResolver $accountResolver,
        private readonly DataSubjectRequestService $requestService,
        private readonly PatientDataExportService $exportService,
        private readonly PatientDataExportPdfService $exportPdfService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $patients = $this->accountResolver->patientsForUser($user);

        $requests = DataSubjectRequest::query()
            ->where('user_id', $user->id)
            ->with('patient:id,name')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('patient.lgpd.index', [
            'user' => $user,
            'patients' => $patients,
            'requests' => $requests,
            'requestTypes' => DataSubjectRequestType::options(),
            'dpoEmail' => config('compliance.lgpd.dpo_email'),
            'dpoName' => config('compliance.lgpd.dpo_name'),
            'slaDays' => (int) config('compliance.lgpd.response_sla_days', 15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $allowedPatientIds = $this->accountResolver->patientsForUser($user)->pluck('id')->all();

        $validated = $request->validate([
            'type' => ['required', Rule::enum(DataSubjectRequestType::class)],
            'details' => ['nullable', 'string', 'max:5000'],
            'patient_id' => ['nullable', 'integer', Rule::in($allowedPatientIds)],
        ]);

        try {
            $this->requestService->create($user, $validated, $request);
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['patient_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('patient.lgpd.index')
            ->with('status', __('Solicitação registrada. O encarregado foi notificado e responderá em até :days dias úteis.', [
                'days' => config('compliance.lgpd.response_sla_days', 15),
            ]));
    }

    public function export(Request $request): Response
    {
        $user = $request->user();
        $json = $this->exportService->exportJson($user);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->exportService->filename($user).'"',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        return $this->exportPdfService->download($request->user());
    }
}
