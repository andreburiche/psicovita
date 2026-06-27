{{ __('Olá, :name', ['name' => $dpoName]) }}

{{ __('Foi registrada uma nova solicitação de direitos do titular no :app.', ['app' => $appName]) }}

{{ __('Titular:') }} {{ $requester->name }} ({{ $requester->email }})
{{ __('Tipo:') }} {{ $dataSubjectRequest->type->label() }}
{{ __('Status:') }} {{ $dataSubjectRequest->status->label() }}
{{ __('Data:') }} {{ $dataSubjectRequest->created_at->format('d/m/Y H:i') }}
@if ($dataSubjectRequest->patient)
{{ __('Ficha:') }} {{ $dataSubjectRequest->patient->name }} (#{{ $dataSubjectRequest->patient_id }})
@endif

@if ($dataSubjectRequest->details)
{{ __('Detalhes:') }}
{{ $dataSubjectRequest->details }}
@endif

{{ __('Painel:') }} {{ route('admin.lgpd.requests.show', $dataSubjectRequest) }}

{{ __('Responda diretamente ao titular utilizando o e-mail de resposta desta mensagem.') }}
