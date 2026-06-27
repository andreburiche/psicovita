<?php

namespace App\Services;

use App\Enums\TherapySessionStatus;
use App\Enums\TherapySessionType;
use App\Models\ScheduleBlock;
use App\Models\TherapySession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TherapySessionReportService
{
    /**
     * @return array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}
     */
    public function parseFilters(Request $request): array
    {
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();

        return [
            'status' => in_array($status, array_column(TherapySessionStatus::cases(), 'value'), true) ? $status : null,
            'type' => in_array($type, array_column(TherapySessionType::cases(), 'value'), true) ? $type : null,
            'patient_id' => $request->integer('patient_id') ?: null,
            'from' => $this->parseDate($request->string('from')->toString()),
            'to' => $this->parseDate($request->string('to')->toString()),
            'q' => ($q = trim($request->string('q')->toString())) !== '' ? $q : null,
        ];
    }

    /**
     * @param  array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}  $filters
     */
    public function filtersActive(array $filters): bool
    {
        return collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
    }

    /**
     * @param  array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}  $filters
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['patient_id'] ?? null, fn (Builder $q, int $patientId) => $q->where('patient_id', $patientId))
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->whereDate('session_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, string $to) => $q->whereDate('session_date', '<=', $to))
            ->when($filters['q'] ?? null, function (Builder $q, string $search) {
                $q->whereHas('patient', fn (Builder $patientQuery) => $patientQuery->where('name', 'like', '%'.$search.'%'));
            });
    }

    /**
     * @param  array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}  $filters
     * @return array{from: Carbon, to: Carbon, label: string}
     */
    public function resolvePeriod(array $filters, Carbon $month): array
    {
        $from = ($filters['from'] ?? null)
            ? Carbon::parse($filters['from'])->startOfDay()
            : $month->copy()->startOfMonth();

        $to = ($filters['to'] ?? null)
            ? Carbon::parse($filters['to'])->endOfDay()
            : $month->copy()->endOfMonth();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $label = $from->isSameDay($to)
            ? $from->format('d/m/Y')
            : $from->format('d/m/Y').' — '.$to->format('d/m/Y');

        return compact('from', 'to', 'label');
    }

    /**
     * @param  array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}  $filters
     * @return array{
     *     total: int,
     *     scheduled: int,
     *     completed: int,
     *     cancelled: int,
     *     online: int,
     *     in_person: int,
     *     unique_patients: int,
     *     blocks: int,
     *     completion_rate: float,
     *     cancellation_rate: float,
     *     period_label: string,
     * }
     */
    public function computeStats(int $professionalId, array $filters, Carbon $month): array
    {
        $period = $this->resolvePeriod($filters, $month);

        $sessions = $this->applyFilters(
            TherapySession::query()->where('professional_id', $professionalId),
            $filters
        )
            ->whereBetween('session_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->get(['id', 'patient_id', 'status', 'type']);

        $total = $sessions->count();
        $scheduled = $sessions->where('status', TherapySessionStatus::Scheduled)->count();
        $completed = $sessions->where('status', TherapySessionStatus::Completed)->count();
        $cancelled = $sessions->where('status', TherapySessionStatus::Cancelled)->count();
        $online = $sessions->where('type', TherapySessionType::Online)->count();
        $inPerson = $sessions->where('type', TherapySessionType::InPerson)->count();

        $blocks = ScheduleBlock::query()
            ->where('professional_id', $professionalId)
            ->whereBetween('block_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->count();

        return [
            'total' => $total,
            'scheduled' => $scheduled,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'online' => $online,
            'in_person' => $inPerson,
            'unique_patients' => $sessions->pluck('patient_id')->unique()->count(),
            'blocks' => $blocks,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0.0,
            'cancellation_rate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0,
            'period_label' => $period['label'],
        ];
    }

    public function monthFromRequest(Request $request): Carbon
    {
        $monthQuery = $request->string('month')->toString();

        if ($monthQuery !== '' && preg_match('/^\d{4}-\d{2}$/', $monthQuery)) {
            return Carbon::parse($monthQuery)->startOfMonth();
        }

        return now()->startOfMonth();
    }

    /**
     * @return array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}
     */
    public function validateFilters(Request $request, int $professionalId): array
    {
        $filters = $this->parseFilters($request);

        validator($filters, [
            'status' => ['nullable', Rule::enum(TherapySessionStatus::class)],
            'type' => ['nullable', Rule::enum(TherapySessionType::class)],
            'patient_id' => ['nullable', 'integer', Rule::exists('patients', 'id')->where('professional_id', $professionalId)],
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ])->validate();

        return $filters;
    }

    /**
     * @param  array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}  $filters
     * @return \Illuminate\Support\Collection<int, TherapySession>
     */
    public function sessionsForExport(int $professionalId, array $filters, Carbon $month): \Illuminate\Support\Collection
    {
        $period = $this->resolvePeriod($filters, $month);

        return $this->applyFilters(
            TherapySession::query()->where('professional_id', $professionalId),
            $filters
        )
            ->whereBetween('session_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->with('patient:id,name')
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->get();
    }

    /**
     * @param  array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}  $filters
     * @return \Illuminate\Support\Collection<int, ScheduleBlock>
     */
    public function blocksForExport(int $professionalId, array $filters, Carbon $month): \Illuminate\Support\Collection
    {
        $period = $this->resolvePeriod($filters, $month);

        return ScheduleBlock::query()
            ->where('professional_id', $professionalId)
            ->whereBetween('block_date', [$period['from']->toDateString(), $period['to']->toDateString()])
            ->orderBy('block_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  array{status: ?string, type: ?string, patient_id: ?int, from: ?string, to: ?string, q: ?string}  $filters
     * @return array{
     *     source: string,
     *     title: string,
     *     professional: \App\Models\User,
     *     month: Carbon,
     *     filters: array,
     *     stats: array,
     *     sessions: \Illuminate\Support\Collection,
     *     blocks: \Illuminate\Support\Collection,
     *     generated_at: Carbon,
     * }
     */
    public function buildExportContext(Request $request, string $source): array
    {
        $user = $request->user();
        $filters = $this->validateFilters($request, $user->id);
        $month = $this->monthFromRequest($request);

        return [
            'source' => $source,
            'title' => $source === 'schedule' ? __('Relatório da Agenda') : __('Relatório de Sessões'),
            'professional' => $user,
            'month' => $month,
            'filters' => $filters,
            'stats' => $this->computeStats($user->id, $filters, $month),
            'sessions' => $this->sessionsForExport($user->id, $filters, $month),
            'blocks' => $this->blocksForExport($user->id, $filters, $month),
            'generated_at' => now(),
        ];
    }

    private function parseDate(string $value): ?string
    {
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
