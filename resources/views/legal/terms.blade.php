@php
    $company = config('compliance.lgpd.company_name');
    $dpoEmail = config('compliance.lgpd.dpo_email');

    $sections = [
        ['id' => 'objeto', 'label' => __('1. Objeto')],
        ['id' => 'conta', 'label' => __('2. Conta e responsabilidades')],
        ['id' => 'lgpd', 'label' => __('3. Dados e LGPD')],
        ['id' => 'ia', 'label' => __('4. Assistente de IA')],
        ['id' => 'disponibilidade', 'label' => __('5. Disponibilidade')],
        ['id' => 'alteracoes', 'label' => __('6. Alterações')],
    ];
@endphp

<x-legal-layout
    :title="__('Termos de Uso')"
    active="terms"
    icon="document-text"
    :badge="__('Documento legal')"
    :description="__('Condições de utilização da plataforma para profissionais de saúde mental e respetivos pacientes.')"
    :sections="$sections"
>
    <x-legal.callout variant="info" :title="__('Leia também')">
        <p>
            {{ __('Ao utilizar o :app, concorda com estes termos. Consulte também a nossa', ['app' => $company]) }}
            <a href="{{ route('legal.privacy') }}">{{ __('Política de Privacidade') }}</a>.
        </p>
    </x-legal.callout>

    <x-legal.section id="objeto" :number="1" :title="__('Objeto')">
        <p>{{ __('O sistema destina-se a profissionais de psicologia e respetivos pacientes, para gestão de agenda, prontuário, comunicação e ferramentas de apoio clínico.') }}</p>
    </x-legal.section>

    <x-legal.section id="conta" :number="2" :title="__('Conta e responsabilidades')">
        <ul>
            <li>{{ __('O profissional é responsável pela veracidade dos dados cadastrados e pelo uso ético da plataforma.') }}</li>
            <li>{{ __('Credenciais de acesso são pessoais e intransferíveis.') }}</li>
            <li>{{ __('O profissional deve obter consentimento do paciente quando exigido por lei ou pelo Conselho Federal de Psicologia.') }}</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="lgpd" :number="3" :title="__('Dados e LGPD')">
        <p>{{ __('O tratamento de dados pessoais segue a Política de Privacidade. Dúvidas ou solicitações do titular podem ser enviadas para :email.', ['email' => $dpoEmail]) }}</p>
        <p class="mt-3">
            <a href="{{ route('legal.privacy') }}">{{ __('Ver Política de Privacidade completa') }} →</a>
        </p>
    </x-legal.section>

    <x-legal.section id="ia" :number="4" :title="__('Assistente de IA')">
        <x-legal.callout variant="warning" :title="__('Revisão profissional obrigatória')">
            <p>{{ __('Funcionalidades de IA são auxiliares. O profissional deve revisar todo o conteúdo gerado antes de uso clínico ou administrativo.') }}</p>
        </x-legal.callout>
        <p class="mt-4">{{ __('O uso de transcrição de áudio exige consentimento explícito registrado no sistema.') }}</p>
        <p class="mt-3">
            <a href="{{ route('legal.dpia-ai') }}">{{ __('Consultar o Relatório de Impacto (DPIA) do módulo de IA') }} →</a>
        </p>
    </x-legal.section>

    <x-legal.section id="disponibilidade" :number="5" :title="__('Disponibilidade')">
        <p>{{ __('Empregamos esforços razoáveis para manter o serviço disponível, sem garantia de funcionamento ininterrupto.') }}</p>
    </x-legal.section>

    <x-legal.section id="alteracoes" :number="6" :title="__('Alterações')">
        <p>{{ __('Estes termos podem ser atualizados. O uso continuado após alterações constitui aceite da nova versão.') }}</p>
    </x-legal.section>
</x-legal-layout>
