{{ __('Prezados(as),') }}

@if ($customMessage)
{{ $customMessage }}
@else
{{ __('Segue em anexo o ofício formal solicitando documentos referentes ao(a) paciente :patient.', ['patient' => $documentRequest->patient->name]) }}
@endif

{{ __('Instituição:') }} {{ $documentRequest->institution_name }}
{{ __('Data da solicitação:') }} {{ $documentRequest->request_date->format('d/m/Y') }}

{{ __('Atenciosamente,') }}
{{ $professional->name }}
@if ($professional->crp_number)
{{ __('CRP') }} {{ $professional->crp_number }}
@endif
{{ $professional->email }}

---
{{ __('Enviado por :app', ['app' => $appName]) }}
