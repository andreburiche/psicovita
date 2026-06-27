<?php

namespace App\Http\Controllers;

use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Models\Patient;
use App\Services\TherapySessionReportService;
use App\Support\MonthGridCalendar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly TherapySessionReportService $reportService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->reportService->parseFilters($request);

        validator($filters, [
            'status' => ['nullable', Rule::enum(TherapySessionStatus::class)],
            'type' => ['nullable', Rule::enum(TherapySessionType::class)],
            'patient_id' => ['nullable', 'integer', Rule::exists('patients', 'id')->where('professional_id', $request->user()->clinicalPracticeId())],
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ])->validate();

        $data = MonthGridCalendar::forProfessional($request->user()->clinicalPracticeId(), $request->query('month'), $filters);

        $patients = Patient::query()
            ->where('professional_id', $request->user()->clinicalPracticeId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('schedule.index', [
            'month' => $data['month'],
            'weeks' => $data['weeks'],
            'blocks' => $data['blocksInMonth'],
            'filters' => $filters,
            'filtersActive' => $this->reportService->filtersActive($filters),
            'stats' => $this->reportService->computeStats($request->user()->clinicalPracticeId(), $filters, $data['month']),
            'patients' => $patients,
        ]);
    }
}
