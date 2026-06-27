<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Ofício — Solicitação de Documentos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; color: #111; line-height: 1.5; }
        h1 { font-size: 16pt; text-align: center; margin-bottom: 24px; }
        .meta { margin-bottom: 20px; }
        .meta p { margin: 4px 0; }
        ul { margin: 8px 0 16px 20px; }
        .signature { margin-top: 48px; }
        .line { border-top: 1px solid #333; width: 280px; margin-top: 56px; padding-top: 8px; }
        .footer { margin-top: 32px; font-size: 9pt; color: #555; }
    </style>
</head>
<body>
    <h1>OFÍCIO — SOLICITAÇÃO DE DOCUMENTOS</h1>

    <div class="meta">
        <p><strong>Local e data:</strong> {{ $documentRequest->request_date->format('d/m/Y') }}</p>
        <p><strong>Destinatário:</strong> {{ $documentRequest->institution_name }} ({{ $documentRequest->institution_type->label() }})</p>
        @if ($documentRequest->contact_name)
            <p><strong>A/C:</strong> {{ $documentRequest->contact_name }}</p>
        @endif
    </div>

    <p>Prezados(as),</p>

    <p>
        Eu, <strong>{{ $professional->name }}</strong>,
        @if ($professional->crp_number)
            inscrito(a) no CRP sob nº <strong>{{ $professional->crp_number }}</strong>,
        @endif
        na qualidade de profissional responsável pelo acompanhamento psicológico, solicito cordialmente o envio dos documentos abaixo relacionados,
        referentes ao(à) paciente <strong>{{ $patient->name }}</strong>
        @if ($patient->birth_date)
            , nascido(a) em {{ $patient->birth_date->format('d/m/Y') }}
        @endif
        @if ($patient->cpf)
            , CPF {{ format_cpf_human($patient->cpf) }}
        @endif
        .
    </p>

    <p><strong>Documentos solicitados:</strong></p>
    <ul>
        @foreach ($documentRequest->requested_documents as $doc)
            <li>{{ $doc }}</li>
        @endforeach
    </ul>

    <p><strong>Finalidade da solicitação:</strong></p>
    <p>{{ $documentRequest->request_reason }}</p>

    @if ($documentRequest->expected_return_date)
        <p><strong>Prazo desejado para resposta:</strong> {{ $documentRequest->expected_return_date->format('d/m/Y') }}</p>
    @endif

    <p>Declaro que a presente solicitação observa a legislação aplicável à proteção de dados pessoais (Lei nº 13.709/2018 — LGPD) e que a autorização do paciente ou responsável legal foi obtida quando necessária.</p>

    <p>Coloco-me à disposição para quaisquer esclarecimentos.</p>

    <div class="signature">
        <p>Atenciosamente,</p>
        <div class="line">
            <strong>{{ $professional->name }}</strong><br>
            @if ($professional->crp_number)
                CRP {{ $professional->crp_number }}<br>
            @endif
            {{ $professional->email }}
        </div>
        <p style="margin-top:12px;font-size:10pt;color:#666;">Campo reservado para assinatura digital / carimbo profissional</p>
    </div>

    <div class="footer">
        Documento gerado pelo {{ config('app.name') }} — ID da solicitação: {{ $documentRequest->id }}
    </div>
</body>
</html>
