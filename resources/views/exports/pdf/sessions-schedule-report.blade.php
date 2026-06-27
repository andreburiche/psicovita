<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $context['title'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #0f172a;
            line-height: 1.45;
            margin: 0;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 55%, #6366f1 100%);
            color: #fff;
            padding: 22px 24px 18px;
            margin: -12px -12px 20px;
        }
        .header-inner {
            width: 100%;
        }
        .header-top {
            width: 100%;
            margin-bottom: 10px;
        }
        .logo {
            max-height: 42px;
            max-width: 180px;
        }
        .app-name {
            font-size: 18pt;
            font-weight: bold;
            letter-spacing: -0.02em;
        }
        .tagline {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgba(255,255,255,0.82);
            margin-top: 2px;
        }
        .report-title {
            font-size: 14pt;
            font-weight: bold;
            margin: 12px 0 0;
        }
        .meta {
            margin-top: 8px;
            font-size: 9pt;
            color: rgba(255,255,255,0.9);
        }
        .meta p { margin: 2px 0; }
        .stats {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin: 0 0 18px;
        }
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: center;
            vertical-align: top;
        }
        .stat-label {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }
        .stat-value {
            font-size: 14pt;
            font-weight: bold;
            color: #4f46e5;
            margin-top: 4px;
        }
        .stat-suffix {
            font-size: 8pt;
            color: #94a3b8;
            font-weight: normal;
        }
        h2 {
            font-size: 11pt;
            color: #fff;
            background: #7c3aed;
            padding: 7px 10px;
            margin: 18px 0 0;
            border-radius: 6px 6px 0 0;
        }
        h2.blocks {
            background: #d97706;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 9pt;
        }
        table.data th {
            background: #ede9fe;
            color: #5b21b6;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #ddd6fe;
        }
        table.data.blocks th {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }
        table.data td {
            border: 1px solid #e2e8f0;
            padding: 5px 8px;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td {
            background: #f8fafc;
        }
        .empty {
            padding: 12px;
            text-align: center;
            color: #64748b;
            font-style: italic;
            border: 1px dashed #cbd5e1;
            margin-bottom: 16px;
        }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #64748b;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-scheduled { background: #dbeafe; color: #1d4ed8; }
        .badge-completed { background: #d1fae5; color: #047857; }
        .badge-cancelled { background: #ffe4e6; color: #be123c; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-top" cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align: middle;">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="{{ $appName }}" class="logo">
                    @else
                        <div class="app-name">{{ $appName }}</div>
                    @endif
                    <div class="tagline">{{ __('Área clínica') }}</div>
                </td>
            </tr>
        </table>
        <div class="report-title">{{ $context['title'] }}</div>
        <div class="meta">
            <p><strong>{{ __('Profissional') }}:</strong> {{ $context['professional']->name }}</p>
            <p><strong>{{ __('Período') }}:</strong> {{ $context['stats']['period_label'] }}</p>
            <p><strong>{{ __('Gerado em') }}:</strong> {{ $context['generated_at']->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <table class="stats">
        <tr>
            @foreach ([
                ['label' => __('Total'), 'value' => $context['stats']['total']],
                ['label' => __('Agendadas'), 'value' => $context['stats']['scheduled']],
                ['label' => __('Concluídas'), 'value' => $context['stats']['completed'], 'suffix' => $context['stats']['total'] > 0 ? $context['stats']['completion_rate'].'%' : null],
                ['label' => __('Canceladas'), 'value' => $context['stats']['cancelled'], 'suffix' => $context['stats']['total'] > 0 ? $context['stats']['cancellation_rate'].'%' : null],
            ] as $stat)
                <td class="stat-card" width="25%">
                    <div class="stat-label">{{ $stat['label'] }}</div>
                    <div class="stat-value">
                        {{ $stat['value'] }}
                        @if (! empty($stat['suffix']))
                            <span class="stat-suffix">{{ $stat['suffix'] }}</span>
                        @endif
                    </div>
                </td>
            @endforeach
        </tr>
        <tr>
            @foreach ([
                ['label' => __('Online'), 'value' => $context['stats']['online']],
                ['label' => __('Presencial'), 'value' => $context['stats']['in_person']],
                ['label' => __('Pacientes'), 'value' => $context['stats']['unique_patients']],
                ['label' => __('Bloqueios'), 'value' => $context['stats']['blocks']],
            ] as $stat)
                <td class="stat-card" width="25%">
                    <div class="stat-label">{{ $stat['label'] }}</div>
                    <div class="stat-value">{{ $stat['value'] }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <h2>{{ __('Sessões') }}</h2>
    @if ($context['sessions']->isNotEmpty())
        <table class="data">
            <thead>
                <tr>
                    <th>{{ __('Data') }}</th>
                    <th>{{ __('Horário') }}</th>
                    <th>{{ __('Paciente') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Tipo') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($context['sessions'] as $session)
                    @php
                        $timeStr = is_string($session->session_time)
                            ? substr($session->session_time, 0, 5)
                            : $session->session_time->format('H:i');
                        $badgeClass = match ($session->status) {
                            \App\Enums\TherapySessionStatus::Completed => 'badge-completed',
                            \App\Enums\TherapySessionStatus::Cancelled => 'badge-cancelled',
                            default => 'badge-scheduled',
                        };
                    @endphp
                    <tr>
                        <td>{{ $session->session_date->format('d/m/Y') }}</td>
                        <td>{{ $timeStr }}</td>
                        <td>{{ $session->displayLabel() }}</td>
                        <td><span class="badge {{ $badgeClass }}">{{ $session->status->label() }}</span></td>
                        <td>{{ $session->type->label() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">{{ __('Nenhuma sessão no período.') }}</div>
    @endif

    <h2 class="blocks">{{ __('Bloqueios de agenda') }}</h2>
    @if ($context['blocks']->isNotEmpty())
        <table class="data blocks">
            <thead>
                <tr>
                    <th>{{ __('Data') }}</th>
                    <th>{{ __('Início') }}</th>
                    <th>{{ __('Fim') }}</th>
                    <th>{{ __('Motivo') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($context['blocks'] as $block)
                    <tr>
                        <td>{{ $block->block_date->format('d/m/Y') }}</td>
                        <td>{{ substr((string) $block->start_time, 0, 5) }}</td>
                        <td>{{ substr((string) $block->end_time, 0, 5) }}</td>
                        <td>{{ $block->reason ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">{{ __('Sem bloqueios no período.') }}</div>
    @endif

    <div class="footer">
        {{ __('Documento gerado automaticamente pelo :app.', ['app' => $appName]) }}
        {{ __('Os dados respeitam os filtros aplicados na consulta.') }}
    </div>
</body>
</html>
