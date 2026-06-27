<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Support\ContrastChecker;
use Illuminate\View\View;

class AccessibilityReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', DataSubjectRequest::class);

        $pairs = config('compliance.accessibility.contrast_pairs', []);
        $results = [];

        foreach ($pairs as $pair) {
            $evaluation = ContrastChecker::evaluate(
                (string) ($pair['foreground'] ?? '#000000'),
                (string) ($pair['background'] ?? '#ffffff'),
            );

            $results[] = array_merge($pair, $evaluation);
        }

        return view('admin.lgpd.accessibility', [
            'results' => $results,
            'wcagNote' => __('Referência WCAG 2.1 nível AA: texto normal ≥ 4,5:1; texto grande ≥ 3:1.'),
        ]);
    }
}
