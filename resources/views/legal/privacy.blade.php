@php
    $dpoName = config('compliance.lgpd.dpo_name');
    $dpoEmail = config('compliance.lgpd.dpo_email');
    $company = config('compliance.lgpd.company_name');

    $sections = [
        ['id' => 'controlador', 'label' => __('1. Controlador e encarregado')],
        ['id' => 'dados', 'label' => __('2. Dados tratados')],
        ['id' => 'finalidades', 'label' => __('3. Finalidades e bases legais')],
        ['id' => 'compartilhamento', 'label' => __('4. Compartilhamento')],
        ['id' => 'direitos', 'label' => __('5. Direitos do titular')],
        ['id' => 'seguranca', 'label' => __('6. Segurança e retenção')],
        ['id' => 'ia', 'label' => __('7. Inteligência artificial')],
        ['id' => 'cookies', 'label' => __('8. Cookies')],
        ['id' => 'exclusao', 'label' => __('9. Exclusão de conta')],
    ];
@endphp

<x-legal-layout
    :title="__('Política de Privacidade')"
    active="privacy"
    icon="shield-check"
    :badge="__('LGPD · Lei 13.709/2018')"
    :description="__('Como o :app trata dados pessoais em conformidade com a Lei Geral de Proteção de Dados.', ['app' => $company])"
    :sections="$sections"
>
    <x-legal.callout variant="shield" :title="__('Compromisso com a privacidade')">
        <p>{{ __('Esta política descreve de forma transparente as práticas de tratamento de dados, direitos dos titulares e medidas de segurança adotadas pela plataforma.') }}</p>
    </x-legal.callout>

    <x-legal.section id="controlador" :number="1" :title="__('Controlador e encarregado')">
        <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
            <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                <div class="grid gap-1 px-4 py-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Controlador') }}</dt>
                    <dd class="text-sm font-medium text-slate-900 dark:text-white sm:col-span-2">{{ $company }}</dd>
                </div>
                <div class="grid gap-1 px-4 py-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Encarregado (DPO)') }}</dt>
                    <dd class="text-sm font-medium text-slate-900 dark:text-white sm:col-span-2">{{ $dpoName }}</dd>
                </div>
                <div class="grid gap-1 px-4 py-3 sm:grid-cols-3 sm:gap-4">
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Contato LGPD') }}</dt>
                    <dd class="sm:col-span-2"><a href="mailto:{{ $dpoEmail }}">{{ $dpoEmail }}</a></dd>
                </div>
            </dl>
        </div>
    </x-legal.section>

    <x-legal.section id="dados" :number="2" :title="__('Dados tratados')">
        <ul>
            <li>{{ __('Dados de cadastro de profissionais (nome, e-mail, CRP).') }}</li>
            <li>{{ __('Dados de pacientes (identificação, contacto, documentos clínicos, prontuário, sessões, pagamentos).') }}</li>
            <li>{{ __('Registos de auditoria (acessos, IP, data/hora) para fins de segurança e conformidade.') }}</li>
            <li>{{ __('Dados enviados à assistente de IA, quando utilizada, com consentimento explícito quando aplicável.') }}</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="finalidades" :number="3" :title="__('Finalidades e bases legais')">
        <ul>
            <li>{{ __('Prestação do serviço de gestão clínica (execução de contrato).') }}</li>
            <li>{{ __('Cumprimento de obrigações legais e regulatórias do profissional.') }}</li>
            <li>{{ __('Proteção da vida e tutela da saúde, quando aplicável.') }}</li>
            <li>{{ __('Legítimo interesse em segurança, prevenção a fraudes e melhoria do sistema.') }}</li>
            <li>{{ __('Consentimento do titular, quando exigido (ex.: transcrição de áudio com IA, compartilhamento de documentos com instituições).') }}</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="compartilhamento" :number="4" :title="__('Compartilhamento')">
        <p>{{ __('Dados podem ser compartilhados com instituições indicadas pelo profissional (solicitações de documentos), provedores de infraestrutura (hospedagem, e-mail) e, quando configurado, provedores de IA. Não vendemos dados pessoais.') }}</p>
    </x-legal.section>

    <x-legal.section id="direitos" :number="5" :title="__('Direitos do titular (art. 18 LGPD)')">
        <p>{{ __('Você pode solicitar confirmação de tratamento, acesso, correção, anonimização, portabilidade, eliminação, informação sobre compartilhamento e revogação de consentimento.') }}</p>
        @auth
            @if (auth()->user()->usesPatientPortalExperience())
                <x-legal.callout variant="contact" class="mt-4">
                    <p>
                        <a href="{{ route('patient.lgpd.index') }}">{{ __('Aceder ao portal de privacidade') }}</a>
                        {{ __('para enviar solicitações, exportar os seus dados ou contactar o encarregado.') }}
                    </p>
                </x-legal.callout>
            @endif
        @endauth
    </x-legal.section>

    <x-legal.section id="seguranca" :number="6" :title="__('Segurança e retenção')">
        <p>{{ __('Adotamos criptografia em campos clínicos sensíveis, CPF, e-mail e telefone dos pacientes, controlo de acesso por profissional, registos de auditoria persistidos e boas práticas de desenvolvimento. Os dados são mantidos pelo tempo necessário à finalidade e às obrigações legais do profissional.') }}</p>
        <p class="mt-3">{{ __('Registos de solicitações LGPD concluídas ou rejeitadas são conservados por até :days dias; registos de auditoria por até :audit dias, após o que podem ser removidos automaticamente.', [
            'days' => config('compliance.retention.data_subject_requests_days', 730),
            'audit' => config('compliance.retention.audit_logs_days', 365),
        ]) }}</p>
    </x-legal.section>

    <x-legal.section id="ia" :number="7" :title="__('Inteligência artificial')">
        <p>
            {{ __('Funcionalidades opcionais de IA (transcrição, geração de texto) exigem consentimento explícito do profissional antes do envio de dados.') }}
            <a href="{{ route('legal.dpia-ai') }}">{{ __('Consultar o Relatório de Impacto (DPIA) do módulo de IA') }}</a>
            {{ __('para detalhes sobre riscos, provedores e salvaguardas.') }}
        </p>
    </x-legal.section>

    <x-legal.section id="cookies" :number="8" :title="__('Cookies')">
        <p>{{ __('Utilizamos cookies essenciais de sessão e autenticação. Não utilizamos cookies de rastreamento publicitário.') }}</p>
    </x-legal.section>

    <x-legal.section id="exclusao" :number="9" :title="__('Exclusão de conta')">
        <p>{{ __('Profissionais que excluem a conta removem permanentemente fichas de pacientes, prontuários e demais dados clínicos vinculados, salvo obrigações legais de guarda que permanecem sob responsabilidade do titular do tratamento. Pacientes podem solicitar eliminação via portal de privacidade ou contacto com o encarregado.') }}</p>
    </x-legal.section>
</x-legal-layout>
