<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LGPD — Encarregado (DPO) e contato do titular
    |--------------------------------------------------------------------------
    */
    'lgpd' => [
        'dpo_name' => env('LGPD_DPO_NAME', 'Encarregado de Proteção de Dados'),
        'dpo_email' => env('LGPD_DPO_EMAIL', 'privacidade@'.parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        'company_name' => env('LGPD_COMPANY_NAME', env('APP_NAME', 'PsiConecta')),
        'response_sla_days' => (int) env('LGPD_RESPONSE_SLA_DAYS', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retenção de registros de conformidade
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'data_subject_requests_days' => (int) env('LGPD_DSR_RETENTION_DAYS', 730),
        'data_subject_requests_completed_only' => true,
        'audit_logs_days' => (int) env('LGPD_AUDIT_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Acessibilidade — pares de contraste monitorados (WCAG AA)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | DPIA — avaliação de impacto (módulo de IA)
    |--------------------------------------------------------------------------
    */
    'audit_export' => [
        'include_user_email' => filter_var(env('LGPD_AUDIT_EXPORT_INCLUDE_USER_EMAIL', false), FILTER_VALIDATE_BOOL),
        'include_ip_address' => filter_var(env('LGPD_AUDIT_EXPORT_INCLUDE_IP_ADDRESS', true), FILTER_VALIDATE_BOOL),
        'include_changes_json' => filter_var(env('LGPD_AUDIT_EXPORT_INCLUDE_CHANGES_JSON', true), FILTER_VALIDATE_BOOL),
    ],

    'dpia' => [
        'ai_last_review' => env('LGPD_DPIA_AI_REVIEW_DATE'),
        'ai_risk_level' => env('LGPD_DPIA_AI_RISK_LEVEL', 'moderado'),
        'ai_provider_label' => [
            'openai' => 'OpenAI / ChatGPT (nuvem)',
            'chatgpt' => 'OpenAI / ChatGPT (nuvem)',
            'gpt' => 'OpenAI / ChatGPT (nuvem)',
            'claude' => 'Anthropic Claude (nuvem)',
            'anthropic' => 'Anthropic Claude (nuvem)',
            'gemini' => 'Google Gemini (nuvem)',
            'google' => 'Google Gemini (nuvem)',
            'ollama' => 'Ollama (local)',
            'mock' => 'Simulação (sem API externa)',
        ],
    ],

    'accessibility' => [
        'contrast_pairs' => [
            ['label' => 'Texto principal em fundo claro', 'foreground' => '#0f172a', 'background' => '#ffffff'],
            ['label' => 'Texto principal em fundo escuro', 'foreground' => '#f1f5f9', 'background' => '#0f172a'],
            ['label' => 'Marca violeta em branco', 'foreground' => '#5b21b6', 'background' => '#ffffff'],
            ['label' => 'Botão primário (texto branco)', 'foreground' => '#ffffff', 'background' => '#6d28d9'],
            ['label' => 'Portal paciente (emerald)', 'foreground' => '#065f46', 'background' => '#ecfdf5'],
            ['label' => 'Links no portal', 'foreground' => '#047857', 'background' => '#ffffff'],
        ],
    ],

];
