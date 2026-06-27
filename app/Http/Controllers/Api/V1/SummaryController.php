<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService): JsonResponse
    {
        return response()->json($dashboardService->summary($request->user()));
    }
}
