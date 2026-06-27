<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Services\LgpdMetricsService;
use Illuminate\View\View;

class LgpdMetricsController extends Controller
{
    public function __construct(
        private readonly LgpdMetricsService $metrics,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', DataSubjectRequest::class);

        return view('admin.lgpd.metrics', [
            'metrics' => $this->metrics->dashboard(),
        ]);
    }
}
