<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ __('Exportação de dados pessoais') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; line-height: 1.45; }
        h1 { font-size: 14pt; margin-bottom: 8px; }
        h2 { font-size: 11pt; margin: 18px 0 8px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0 12px; font-size: 9pt; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
        .meta { font-size: 9pt; color: #555; margin-bottom: 16px; }
        .disclaimer { font-size: 8pt; color: #666; margin-top: 24px; padding-top: 8px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>{{ __('Exportação de dados pessoais') }}</h1>
    <p class="meta">
        <strong>{{ $company }}</strong><br>
        {{ __('Titular:') }} {{ $user->name }} ({{ $user->email }})<br>
        {{ __('Gerado em:') }} {{ \Illuminate\Support\Carbon::parse($payload['exported_at'])->format('d/m/Y H:i') }}
    </p>

    <h2>{{ __('Conta na plataforma') }}</h2>
    <table>
        <tr><th>{{ __('Campo') }}</th><th>{{ __('Valor') }}</th></tr>
        <tr><td>{{ __('Nome') }}</td><td>{{ $payload['account']['name'] ?? '—' }}</td></tr>
        <tr><td>{{ __('E-mail') }}</td><td>{{ $payload['account']['email'] ?? '—' }}</td></tr>
        <tr><td>{{ __('Telefone') }}</td><td>{{ $payload['account']['phone'] ? format_phone_br_human($payload['account']['phone']) : '—' }}</td></tr>
        <tr><td>{{ __('Cadastro') }}</td><td>{{ isset($payload['account']['created_at']) ? \Illuminate\Support\Carbon::parse($payload['account']['created_at'])->format('d/m/Y') : '—' }}</td></tr>
    </table>

    @foreach ($payload['patients'] as $patientData)
        <h2>{{ __('Ficha:') }} {{ $patientData['profile']['name'] ?? __('Paciente') }}</h2>
        @if (! empty($patientData['professional']['name']))
            <p><strong>{{ __('Profissional:') }}</strong> {{ $patientData['professional']['name'] }}</p>
        @endif

        <table>
            <tr><th>{{ __('Campo') }}</th><th>{{ __('Valor') }}</th></tr>
            <tr><td>{{ __('E-mail') }}</td><td>{{ $patientData['profile']['email'] ?? '—' }}</td></tr>
            <tr><td>{{ __('Telefone') }}</td><td>{{ ! empty($patientData['profile']['phone']) ? format_phone_br_human($patientData['profile']['phone']) : '—' }}</td></tr>
            <tr><td>{{ __('Nascimento') }}</td><td>{{ $patientData['profile']['birth_date'] ? \Illuminate\Support\Carbon::parse($patientData['profile']['birth_date'])->format('d/m/Y') : '—' }}</td></tr>
            @if (! empty($patientData['profile']['cpf']))
                <tr><td>{{ __('CPF') }}</td><td>{{ format_cpf_human($patientData['profile']['cpf']) }}</td></tr>
            @endif
        </table>

        @if (! empty($patientData['therapy_sessions']))
            <p><strong>{{ __('Sessões') }}</strong></p>
            <table>
                <tr><th>{{ __('Data') }}</th><th>{{ __('Horário') }}</th><th>{{ __('Status') }}</th><th>{{ __('Tipo') }}</th></tr>
                @foreach ($patientData['therapy_sessions'] as $session)
                    <tr>
                        <td>{{ $session['date'] ? \Illuminate\Support\Carbon::parse($session['date'])->format('d/m/Y') : '—' }}</td>
                        <td>{{ $session['time'] ?? '—' }}</td>
                        <td>{{ $session['status'] ?? '—' }}</td>
                        <td>{{ $session['type'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if (! empty($patientData['payments']))
            <p><strong>{{ __('Pagamentos') }}</strong></p>
            <table>
                <tr><th>{{ __('Valor') }}</th><th>{{ __('Status') }}</th><th>{{ __('Método') }}</th><th>{{ __('Data') }}</th></tr>
                @foreach ($patientData['payments'] as $payment)
                    <tr>
                        <td>R$ {{ number_format((float) ($payment['amount'] ?? 0), 2, ',', '.') }}</td>
                        <td>{{ $payment['status'] ?? '—' }}</td>
                        <td>{{ $payment['payment_method'] ?? '—' }}</td>
                        <td>{{ isset($payment['created_at']) ? \Illuminate\Support\Carbon::parse($payment['created_at'])->format('d/m/Y') : '—' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if (! empty($patientData['messages']))
            <p><strong>{{ __('Mensagens') }}</strong></p>
            <table>
                <tr><th>{{ __('Data') }}</th><th>{{ __('Direção') }}</th><th>{{ __('Conteúdo') }}</th></tr>
                @foreach ($patientData['messages'] as $message)
                    <tr>
                        <td>{{ isset($message['created_at']) ? \Illuminate\Support\Carbon::parse($message['created_at'])->format('d/m/Y H:i') : '—' }}</td>
                        <td>{{ ($message['direction'] ?? '') === 'sent' ? __('Enviada') : __('Recebida') }}</td>
                        <td>{{ $message['body'] ?? '' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endforeach

    <p class="disclaimer">{{ $payload['disclaimer'] ?? '' }}</p>
</body>
</html>
