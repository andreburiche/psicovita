<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DataSubjectRequestStatus;
use App\Enums\DataSubjectRequestType;
use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Services\DataSubjectRequestAdminService;
use App\Services\PatientDataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DataSubjectRequestController extends Controller
{
    public function __construct(
        private readonly DataSubjectRequestAdminService $adminService,
        private readonly PatientDataExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DataSubjectRequest::class);

        $query = DataSubjectRequest::query()
            ->with(['user:id,name,email', 'patient:id,name,professional_id', 'patient.professional:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->whereHas('user', fn ($q) => $q
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term));
        }

        return view('admin.lgpd.requests.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'statuses' => DataSubjectRequestStatus::options(),
            'types' => DataSubjectRequestType::options(),
            'filters' => $request->only(['status', 'type', 'q']),
        ]);
    }

    public function show(DataSubjectRequest $dataSubjectRequest): View
    {
        $this->authorize('view', $dataSubjectRequest);

        $dataSubjectRequest->load(['user', 'patient.professional']);

        return view('admin.lgpd.requests.show', [
            'dataSubjectRequest' => $dataSubjectRequest,
            'statuses' => DataSubjectRequestStatus::options(),
        ]);
    }

    public function update(Request $request, DataSubjectRequest $dataSubjectRequest): RedirectResponse
    {
        $this->authorize('update', $dataSubjectRequest);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(DataSubjectRequestStatus::class)],
            'response_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->adminService->update($dataSubjectRequest, $request->user(), $validated);

        return redirect()
            ->route('admin.lgpd.requests.show', $dataSubjectRequest)
            ->with('status', __('Solicitação atualizada e titular notificado quando aplicável.'));
    }

    public function exportUserData(DataSubjectRequest $dataSubjectRequest): Response
    {
        $this->authorize('view', $dataSubjectRequest);

        $user = $dataSubjectRequest->user;
        abort_if($user === null, 404);

        $json = $this->exportService->exportJson($user);

        return response($json, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$this->exportService->filename($user).'"',
        ]);
    }
}
