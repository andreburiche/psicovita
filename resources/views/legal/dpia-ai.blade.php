@php
    $dpoName = config('compliance.lgpd.dpo_name');
    $dpoEmail = config('compliance.lgpd.dpo_email');
    $company = config('compliance.lgpd.company_name');
    $provider = config('psiconecta.ai.provider', 'openai');
    $providerLabel = config("compliance.dpia.ai_provider_label.{$provider}", $provider);
    $riskLevel = config('compliance.dpia.ai_risk_level', 'moderado');
    $lastReview = config('compliance.dpia.ai_last_review');
    $updatedAt = $lastReview ? \Illuminate\Support\Carbon::parse($lastReview)->format('d/m/Y') : now()->format('d/m/Y');

    $sections = [
        ['id' => 'descricao', 'label' => __('1. Descrição do tratamento')],
        ['id' => 'categorias', 'label' => __('2. Dados e categorias')],
        ['id' => 'bases', 'label' => __('3. Finalidades e bases legais')],
        ['id' => 'provedor', 'label' => __('4. Provedor e transferência')],
        ['id' => 'riscos', 'label' => __('5. Riscos e medidas')],
        ['id' => 'direitos', 'label' => __('6. Direitos dos titulares')],
        ['id' => 'responsaveis', 'label' => __('7. Responsáveis e contacto')],
    ];
@endphp

<x-legal-layout
    :title="__('Relatório de Impacto à Proteção de Dados (DPIA) — Assistente de IA')"
    active="dpia-ai"
    icon="sparkles"
    :badge="__('Governança de IA')"
    :description="__('Documento de impacto do módulo de inteligência artificial em contexto clínico, conforme LGPD.')"
    :updated-at="$updatedAt"
    :sections="$sections"
>
    <x-legal.callout variant="info" :title="__('Nível de risco residual')">
        <p class="text-base font-bold capitalize text-slate-900 dark:text-white">{{ $riskLevel }}</p>
        <p class="mt-1">{{ __('Este documento descreve o tratamento de dados pessoais e sensíveis no módulo de assistente de inteligência artificial do :app, em conformidade com a LGPD e boas práticas de governança de IA em contexto clínico.', ['app' => $company]) }}</p>
    </x-legal.callout>

    <x-legal.section id="descricao" :number="1" :title="__('Descrição do tratamento')">
        <p>{{ __('O assistente de IA apoia profissionais de saúde mental em tarefas opcionais: transcrição de áudio de sessões, geração de texto clínico (evoluções, resumos) e recomendações de abordagem terapêutica. O uso é voluntário e restrito a utilizadores autenticados com perfil profissional.') }}</p>
    </x-legal.section>

    <x-legal.section id="categorias" :number="2" :title="__('Dados tratados e categorias')">
        <ul>
            <li>{{ __('Conteúdo textual inserido pelo profissional (notas, pedidos, contexto da sessão).') }}</li>
            <li>{{ __('Áudio enviado para transcrição, quando o profissional opta por essa funcionalidade.') }}</li>
            <li>{{ __('Identificação do paciente associado ao pedido (quando selecionado na interface).') }}</li>
            <li>{{ __('Metadados: data/hora, tipo de pedido, tokens consumidos, IP e timestamp de consentimento LGPD.') }}</li>
        </ul>
        <x-legal.callout variant="warning" class="mt-4" :title="__('Dados sensíveis')">
            <p>{{ __('Podem estar presentes dados sensíveis de saúde (art. 5º, II e art. 11 LGPD), dependendo do conteúdo fornecido pelo profissional.') }}</p>
        </x-legal.callout>
    </x-legal.section>

    <x-legal.section id="bases" :number="3" :title="__('Finalidades e bases legais')">
        <ul>
            <li>{{ __('Apoio à documentação clínica e produtividade do profissional (execução de contrato / legítimo interesse, com salvaguardas).') }}</li>
            <li>{{ __('Transcrição e processamento com IA: consentimento explícito do profissional antes do envio (registado em ai_requests.lgpd_consent_at).') }}</li>
            <li>{{ __('Segurança e auditoria: registos de pedidos e trilha de auditoria para incidentes e conformidade.') }}</li>
        </ul>
    </x-legal.section>

    <x-legal.section id="provedor" :number="4" :title="__('Provedor e transferência')">
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Provedor configurado') }}</p>
            <p class="mt-1 text-base font-semibold text-slate-900 dark:text-white">{{ $providerLabel }}</p>
        </div>
        <p class="mt-4">
            @if (in_array($provider, ['openai', 'chatgpt', 'gpt'], true))
                {{ __('Quando OpenAI/ChatGPT está ativo, texto e áudio podem ser transmitidos a servidores da OpenAI (possível transferência internacional). O profissional deve avaliar contratos e políticas do provedor.') }}
            @elseif (in_array($provider, ['claude', 'anthropic'], true))
                {{ __('Com Anthropic Claude, o texto pode ser processado em servidores da Anthropic (possível transferência internacional). Avalie contratos, retenção e política de dados do provedor.') }}
            @elseif (in_array($provider, ['gemini', 'google'], true))
                {{ __('Com Google Gemini, o texto pode ser processado em servidores Google (possível transferência internacional). Avalie contratos e política de dados do Google AI.') }}
            @elseif ($provider === 'ollama')
                {{ __('Com Ollama, o processamento ocorre na infraestrutura indicada pelo administrador (tipicamente local ou rede privada), reduzindo transferência a terceiros na nuvem.') }}
            @else
                {{ __('No modo simulação, nenhum dado é enviado a APIs externas; respostas são geradas localmente para demonstração.') }}
            @endif
        </p>
    </x-legal.section>

    <x-legal.section id="riscos" :number="5" :title="__('Riscos identificados e medidas')">
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
            <table class="w-full min-w-[480px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/80">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Risco') }}</th>
                        <th scope="col" class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Mitigação') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr class="bg-white dark:bg-slate-900/40">
                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ __('Exposição de dados clínicos a terceiros') }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ __('Consentimento prévio, minimização de dados, escolha de provedor (local vs nuvem), controlo de acesso por profissional.') }}</td>
                    </tr>
                    <tr class="bg-white dark:bg-slate-900/40">
                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ __('Respostas incorretas da IA') }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ __('Interface deixa claro que o output é sugestão; revisão humana obrigatória antes de integrar ao prontuário.') }}</td>
                    </tr>
                    <tr class="bg-white dark:bg-slate-900/40">
                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ __('Retenção excessiva') }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ __('Pedidos armazenados na tabela ai_requests; política de retenção alinhada ao prontuário e solicitações de eliminação via portal LGPD.') }}</td>
                    </tr>
                    <tr class="bg-white dark:bg-slate-900/40">
                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ __('Acesso não autorizado') }}</td>
                        <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ __('Autenticação, autorização por perfil, auditoria append-only e criptografia de campos sensíveis do paciente.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-legal.section>

    <x-legal.section id="direitos" :number="6" :title="__('Direitos dos titulares')">
        <p>{{ __('Pacientes podem exercer direitos previstos no art. 18 LGPD através do profissional responsável ou do encarregado. Profissionais podem solicitar informações sobre pedidos de IA associados à sua conta.') }}</p>
        <p class="mt-3">
            <a href="{{ route('legal.privacy') }}">{{ __('Consultar a Política de Privacidade') }} →</a>
        </p>
    </x-legal.section>

    <x-legal.section id="responsaveis" :number="7" :title="__('Responsáveis e contacto')">
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
                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ __('Contato') }}</dt>
                    <dd class="sm:col-span-2"><a href="mailto:{{ $dpoEmail }}">{{ $dpoEmail }}</a></dd>
                </div>
            </dl>
        </div>
    </x-legal.section>
</x-legal-layout>
