<div class="signature">
    <p>{{ __('Atenciosamente,') }}</p>
    <div class="line">
        <strong>{{ $professional->name }}</strong><br>
        @if ($professional->crp_number)
            CRP {{ $professional->crp_number }}<br>
        @endif
        @if ($professional->professionalFunctionLabel())
            {{ $professional->professionalFunctionLabel() }}<br>
        @endif
        {{ $professional->email }}
    </div>
    <p style="margin-top:12px;font-size:9pt;color:#666;">{{ __('Espaço reservado para assinatura e carimbo profissional') }}</p>
</div>
