<?php

namespace App\Services;

use App\Enums\ClinicalScaleType;
use App\Models\Patient;
use App\Models\PatientScaleAssessment;
use App\Models\User;
use Illuminate\Support\Collection;

class PatientScaleAssessmentService
{
    /** @return Collection<int, PatientScaleAssessment> */
    public function listForPatient(Patient $patient, ?ClinicalScaleType $scale = null, int $limit = 50): Collection
    {
        $query = PatientScaleAssessment::query()
            ->where('patient_id', $patient->id)
            ->with('professional')
            ->orderByDesc('assessed_at')
            ->orderByDesc('id');

        if ($scale !== null) {
            $query->where('scale_type', $scale);
        }

        return $query->limit($limit)->get();
    }

    /**
     * @param  array<string, int>  $responses
     */
    public function create(
        Patient $patient,
        User $actor,
        ClinicalScaleType $scale,
        string $assessedAt,
        array $responses,
        ?string $notes = null,
    ): PatientScaleAssessment {
        $scored = \App\Support\ClinicalScaleCatalog::score($scale, $responses);

        return PatientScaleAssessment::query()->create([
            'patient_id' => $patient->id,
            'professional_id' => $actor->clinicalPracticeId(),
            'scale_type' => $scale,
            'assessed_at' => $assessedAt,
            'total_score' => $scored['total'],
            'severity' => $scored['severity'],
            'severity_label' => $scored['severity_label'],
            'is_risk' => $scored['is_risk'],
            'responses' => $responses,
            'notes' => $notes,
        ]);
    }

    /**
     * @return array<string, array{labels: list<string>, scores: list<int>, latest: ?PatientScaleAssessment}>
     */
    public function chartData(Patient $patient): array
    {
        $data = [];

        foreach (ClinicalScaleType::cases() as $scale) {
            $assessments = PatientScaleAssessment::query()
                ->where('patient_id', $patient->id)
                ->where('scale_type', $scale)
                ->orderBy('assessed_at')
                ->orderBy('id')
                ->get();

            $data[$scale->value] = [
                'labels' => $assessments->map(fn ($a) => $a->assessed_at->format('d/m/Y'))->values()->all(),
                'scores' => $assessments->map(fn ($a) => $a->total_score)->values()->all(),
                'latest' => $assessments->last(),
                'max_score' => \App\Support\ClinicalScaleCatalog::maxScore($scale),
            ];
        }

        return $data;
    }

    /** @return array<string, ?PatientScaleAssessment> */
    public function latestByScale(Patient $patient): array
    {
        $latest = [];

        foreach (ClinicalScaleType::cases() as $scale) {
            $latest[$scale->value] = PatientScaleAssessment::query()
                ->where('patient_id', $patient->id)
                ->where('scale_type', $scale)
                ->orderByDesc('assessed_at')
                ->orderByDesc('id')
                ->first();
        }

        return $latest;
    }
}
