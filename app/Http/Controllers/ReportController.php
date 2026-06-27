<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function index(Request $request): View
    {
        $charts = $this->reportService->dashboardCharts($request->user(), 6);

        return view('reports.index', [
            'title' => 'Relatórios',
            'charts' => $charts,
        ]);
    }
}
