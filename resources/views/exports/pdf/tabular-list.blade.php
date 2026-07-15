<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $context['title'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #0f172a; margin: 0; }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 55%, #6366f1 100%);
            color: #fff;
            padding: 18px 20px 14px;
            margin: -12px -12px 16px;
        }
        .app-name { font-size: 14pt; font-weight: bold; }
        .report-title { font-size: 12pt; font-weight: bold; margin-top: 8px; }
        .meta { margin-top: 6px; font-size: 8pt; color: rgba(255,255,255,0.9); }
        .meta p { margin: 2px 0; }
        .subtitle {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 12px;
            font-size: 8pt;
            color: #475569;
        }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            background: #7c3aed;
            color: #fff;
            text-align: left;
            padding: 6px 8px;
            font-size: 8pt;
        }
        table.data td {
            border-bottom: 1px solid #e2e8f0;
            padding: 5px 8px;
            font-size: 8pt;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .empty { color: #64748b; font-style: italic; padding: 12px 0; }
        .logo { max-height: 36px; max-width: 160px; }
    </style>
</head>
<body>
    <div class="header">
        @if (! empty($logoDataUri))
            <img class="logo" src="{{ $logoDataUri }}" alt="{{ $appName }}">
        @else
            <div class="app-name">{{ $appName }}</div>
        @endif
        <div class="report-title">{{ $context['title'] }}</div>
        <div class="meta">
            <p>{{ __('Profissional') }}: {{ $context['professional_name'] }}</p>
            <p>{{ __('Gerado em') }}: {{ $context['generated_at']->format('d/m/Y H:i') }}</p>
            @foreach ($context['filter_summary'] ?? [] as $filter)
                <p>{{ $filter }}</p>
            @endforeach
        </div>
    </div>

    @if (filled($context['subtitle'] ?? null))
        <div class="subtitle">{{ $context['subtitle'] }}</div>
    @endif

    @if (($context['rows'] ?? []) === [])
        <p class="empty">{{ __('Nenhum registo para exportar.') }}</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach ($context['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($context['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
