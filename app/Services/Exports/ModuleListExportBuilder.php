<?php

namespace App\Services\Exports;

use App\Enums\PaymentStatus;
use App\Enums\TherapySessionStatus;
use App\Models\ClinicalRecord;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\ReportService;
use App\Support\ContactHasher;
use App\Support\CpfHasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ModuleListExportBuilder
{
    public const MAX_ROWS = 2000;

    public function __construct(
        private readonly ReportService $reports,
    ) {}

    /**
     * @return array{
     *   title: string,
     *   filename_prefix: string,
     *   professional_name: string,
     *   subtitle?: string|null,
     *   filter_summary?: list<string>,
     *   columns: list<string>,
     *   rows: list<list<string|int|float|null>>,
     *   generated_at: \Carbon\CarbonInterface,
     * }
     */
    public function build(string $module, Request $request, User $professional): array
    {
        return match ($module) {
            'patients' => $this->patients($request, $professional),
            'payments' => $this->payments($request, $professional),
            'clinical-records' => $this->clinicalRecords($request, $professional),
            'reports' => $this->reports($professional),
            default => throw new \InvalidArgumentException(__('Módulo de exportação inválido.')),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function patients(Request $request, User $professional): array
    {
        $search = $request->filled('q') ? trim((string) $request->input('q')) : null;
        $practiceId = $professional->clinicalPracticeId();

        $query = Patient::query()->where('professional_id', $practiceId);

        if (filled($search)) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%');

                $email = Str::lower(trim($search));
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $q->orWhere('email_hash', ContactHasher::emailHash($email));
                }

                $phoneDigits = only_digits($search);
                if (strlen($phoneDigits) >= 10) {
                    $q->orWhere('phone_hash', ContactHasher::phoneHash($phoneDigits));
                }

                $cpfDigits = only_digits($search);
                if (strlen($cpfDigits) === 11 && is_valid_cpf($cpfDigits)) {
                    $q->orWhere('cpf_hash', CpfHasher::hash($cpfDigits));
                }
            });
        }

        $rows = $query->orderBy('name')->limit(self::MAX_ROWS)->get()->map(function (Patient $patient) {
            return [
                $patient->id,
                $patient->name,
                $patient->email ?: '—',
                $patient->phone ? (format_phone_br_human($patient->phone) ?: $patient->phone) : '—',
                $patient->birth_date?->format('d/m/Y') ?: '—',
                $patient->created_at->format('d/m/Y H:i'),
            ];
        })->all();

        $filters = [];
        if (filled($search)) {
            $filters[] = __('Busca').': '.$search;
        }

        return $this->context(
            title: __('Lista de pacientes'),
            prefix: 'pacientes',
            professional: $professional,
            columns: [__('ID'), __('Nome'), __('E-mail'), __('Telefone'), __('Nascimento'), __('Cadastrado em')],
            rows: $rows,
            filters: $filters,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payments(Request $request, User $professional): array
    {
        $practiceId = $professional->clinicalPracticeId();

        $filters = [
            'status' => $request->filled('status') ? (string) $request->input('status') : null,
            'patient_id' => $request->filled('patient_id') ? (int) $request->input('patient_id') : null,
            'q' => $request->filled('q') ? trim((string) $request->input('q')) : null,
            'from' => $request->filled('from') ? (string) $request->input('from') : null,
            'to' => $request->filled('to') ? (string) $request->input('to') : null,
        ];

        $validated = validator($filters, [
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'patient_id' => ['nullable', 'integer', Rule::exists('patients', 'id')->where('professional_id', $practiceId)],
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ])->validate();

        $query = Payment::query()
            ->whereHas('patient', fn ($q) => $q->where('professional_id', $practiceId))
            ->with(['patient', 'therapySession']);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['patient_id'])) {
            $query->where('patient_id', $validated['patient_id']);
        }
        if (! empty($validated['q'])) {
            $term = '%'.$validated['q'].'%';
            $query->whereHas('patient', fn ($q) => $q->where('name', 'like', $term));
        }
        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }
        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $rows = $query->latest()->limit(self::MAX_ROWS)->get()->map(function (Payment $payment) {
            return [
                $payment->id,
                $payment->patient?->name ?? '—',
                number_format((float) $payment->amount, 2, ',', '.'),
                $payment->status->label(),
                $payment->payment_method?->label() ?? '—',
                $payment->gateway?->label() ?? '—',
                $payment->paid_at?->format('d/m/Y H:i') ?: '—',
                $payment->created_at->format('d/m/Y H:i'),
                $payment->therapy_session_id
                    ? '#'.$payment->therapy_session_id
                    : '—',
            ];
        })->all();

        $summary = [];
        if (! empty($validated['status'])) {
            $summary[] = __('Status').': '.(PaymentStatus::from($validated['status'])->label());
        }
        if (! empty($validated['patient_id'])) {
            $name = Patient::query()->whereKey($validated['patient_id'])->value('name');
            $summary[] = __('Paciente').': '.($name ?: '#'.$validated['patient_id']);
        }
        if (! empty($validated['q'])) {
            $summary[] = __('Busca').': '.$validated['q'];
        }
        if (! empty($validated['from']) || ! empty($validated['to'])) {
            $summary[] = __('Período').': '.($validated['from'] ?? '…').' → '.($validated['to'] ?? '…');
        }

        return $this->context(
            title: __('Lista financeira'),
            prefix: 'pagamentos',
            professional: $professional,
            columns: [
                __('ID'), __('Paciente'), __('Valor (R$)'), __('Status'), __('Método'),
                __('Gateway'), __('Pago em'), __('Criado em'), __('Sessão'),
            ],
            rows: $rows,
            filters: $summary,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function clinicalRecords(Request $request, User $professional): array
    {
        $practiceId = $professional->clinicalPracticeId();

        $rows = ClinicalRecord::query()
            ->where('professional_id', $practiceId)
            ->with('patient')
            ->latest()
            ->limit(self::MAX_ROWS)
            ->get()
            ->map(fn (ClinicalRecord $record) => [
                $record->id,
                $record->patient?->name ?? '—',
                $record->created_at->format('d/m/Y H:i'),
                $record->updated_at->format('d/m/Y H:i'),
            ])
            ->all();

        return $this->context(
            title: __('Índice de prontuário'),
            prefix: 'prontuario',
            professional: $professional,
            columns: [__('ID'), __('Paciente'), __('Criado em'), __('Actualizado em')],
            rows: $rows,
            subtitle: __('Exportação de metadados apenas — o conteúdo clínico sensível não é incluído (LGPD).'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function reports(User $professional): array
    {
        $charts = $this->reports->dashboardCharts($professional, 6);
        $rows = [];

        foreach ($charts['labels'] as $i => $label) {
            $rows[] = [
                $label,
                $charts['sessions_per_month'][$i] ?? 0,
                number_format((float) ($charts['revenue_per_month'][$i] ?? 0), 2, ',', '.'),
            ];
        }

        return $this->context(
            title: __('Relatórios do consultório'),
            prefix: 'relatorios',
            professional: $professional,
            columns: [__('Mês'), __('Sessões'), __('Receita paga (R$)')],
            rows: $rows,
            subtitle: __('Pacientes activos (sessão nos últimos 90 dias): :count', [
                'count' => $charts['active_patients'],
            ]).'. '.__('Exclui sessões canceladas (:status).', [
                'status' => TherapySessionStatus::Cancelled->label(),
            ]),
        );
    }

    /**
     * @param  list<string>  $columns
     * @param  list<list<string|int|float|null>>  $rows
     * @param  list<string>  $filters
     * @return array<string, mixed>
     */
    private function context(
        string $title,
        string $prefix,
        User $professional,
        array $columns,
        array $rows,
        array $filters = [],
        ?string $subtitle = null,
    ): array {
        return [
            'title' => $title,
            'filename_prefix' => $prefix,
            'professional_name' => $professional->name,
            'subtitle' => $subtitle,
            'filter_summary' => $filters,
            'columns' => $columns,
            'rows' => $rows,
            'generated_at' => now(),
        ];
    }
}
