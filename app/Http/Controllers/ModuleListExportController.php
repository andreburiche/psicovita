<?php

namespace App\Http\Controllers;

use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\Payment;
use App\Services\Exports\ModuleListExportBuilder;
use App\Services\Exports\TabularListExportService;
use Illuminate\Http\Request;

class ModuleListExportController extends Controller
{
    public function __construct(
        private readonly ModuleListExportBuilder $builder,
        private readonly TabularListExportService $exporter,
    ) {}

    public function patientsPdf(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        return $this->exporter->downloadPdf($this->builder->build('patients', $request, $request->user()));
    }

    public function patientsExcel(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        return $this->exporter->downloadExcel($this->builder->build('patients', $request, $request->user()));
    }

    public function paymentsPdf(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        return $this->exporter->downloadPdf($this->builder->build('payments', $request, $request->user()));
    }

    public function paymentsExcel(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        return $this->exporter->downloadExcel($this->builder->build('payments', $request, $request->user()));
    }

    public function clinicalRecordsPdf(Request $request)
    {
        $this->authorize('viewAny', ClinicalRecord::class);

        return $this->exporter->downloadPdf($this->builder->build('clinical-records', $request, $request->user()));
    }

    public function clinicalRecordsExcel(Request $request)
    {
        $this->authorize('viewAny', ClinicalRecord::class);

        return $this->exporter->downloadExcel($this->builder->build('clinical-records', $request, $request->user()));
    }

    public function reportsPdf(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        return $this->exporter->downloadPdf($this->builder->build('reports', $request, $request->user()));
    }

    public function reportsExcel(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        return $this->exporter->downloadExcel($this->builder->build('reports', $request, $request->user()));
    }
}
