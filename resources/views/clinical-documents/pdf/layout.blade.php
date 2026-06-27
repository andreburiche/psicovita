<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; color: #111; line-height: 1.55; }
        .letterhead { text-align: center; margin-bottom: 22px; padding-bottom: 16px; border-bottom: 1px solid #e5e7eb; }
        .logo { max-height: 56px; max-width: 220px; }
        .institution-name { font-size: 13pt; font-weight: bold; color: #1f2937; letter-spacing: 0.02em; }
        h1 { font-size: 15pt; text-align: center; margin: 0 0 28px; letter-spacing: 0.04em; }
        .meta { margin-bottom: 22px; font-size: 11pt; }
        .meta p { margin: 3px 0; }
        .content { margin: 24px 0; text-align: justify; }
        .content p { margin: 0 0 14px; }
        .block-title { font-weight: bold; margin: 18px 0 8px; }
        .medications { white-space: pre-wrap; border: 1px solid #ccc; padding: 12px 14px; border-radius: 4px; }
        .signature { margin-top: 56px; }
        .line { border-top: 1px solid #333; width: 300px; margin-top: 64px; padding-top: 8px; font-size: 11pt; }
        .footer { margin-top: 36px; font-size: 8.5pt; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        .legal { font-size: 9pt; color: #555; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="letterhead">
        @if ($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="{{ $institutionName }}" class="logo">
        @else
            <div class="institution-name">{{ $institutionName }}</div>
        @endif
    </div>

    <h1>{{ $title }}</h1>

    <div class="meta">
        @if (filled($payload['place'] ?? null))
            <p><strong>{{ __('Local') }}:</strong> {{ $payload['place'] }}, {{ $document->issued_at->format('d/m/Y') }}</p>
        @else
            <p><strong>{{ __('Data') }}:</strong> {{ $document->issued_at->format('d/m/Y') }}</p>
        @endif
        <p><strong>{{ __('Paciente') }}:</strong> {{ $patient->name }}
            @if ($patient->birth_date)
                — {{ __('nascido(a) em') }} {{ $patient->birth_date->format('d/m/Y') }}
            @endif
            @if ($patient->cpf)
                — CPF {{ format_cpf_human($patient->cpf) }}
            @endif
        </p>
    </div>

    {{ $slot }}

    @include('clinical-documents.pdf.partials.signature')

    <div class="footer">
        {{ __('Documento gerado pelo') }} {{ config('app.name') }}
        @if ($document->exists)
            — ID {{ $document->id }}
        @else
            — {{ __('Pré-visualização') }}
        @endif
        · {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
