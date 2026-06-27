<?php

namespace App\Http\Controllers;

use App\Models\TherapySession;
use App\Services\TherapySessionExportService;
use App\Services\TherapySessionReportService;
use Illuminate\Http\Request;

class SessionScheduleExportController extends Controller
{
    public function __construct(
        private readonly TherapySessionReportService $reportService,
        private readonly TherapySessionExportService $exportService,
    ) {}

    public function pdf(Request $request, string $source)
    {
        $this->authorize('viewAny', TherapySession::class);

        $context = $this->reportService->buildExportContext($request, $source);

        return $this->exportService->downloadPdf($context);
    }

    public function excel(Request $request, string $source)
    {
        $this->authorize('viewAny', TherapySession::class);

        $context = $this->reportService->buildExportContext($request, $source);

        return $this->exportService->downloadExcel($context);
    }

    public function pdfSessions(Request $request)
    {
        return $this->pdf($request, 'therapy-sessions');
    }

    public function excelSessions(Request $request)
    {
        return $this->excel($request, 'therapy-sessions');
    }

    public function pdfSchedule(Request $request)
    {
        return $this->pdf($request, 'schedule');
    }

    public function excelSchedule(Request $request)
    {
        return $this->excel($request, 'schedule');
    }
}
