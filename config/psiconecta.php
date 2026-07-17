<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integrações externas (ex.: WhatsApp Business API)
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        /** Driver: meta (Cloud API oficial) ou evolution (Evolution API self-hosted). */
        'driver' => env('WHATSAPP_DRIVER', 'meta'),
        'enabled' => env('WHATSAPP_ENABLED', false),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        /** Nome do template aprovado na Meta para reabrir janela 24h (opcional, só driver meta). */
        'session_template' => env('WHATSAPP_SESSION_TEMPLATE'),
        'session_template_language' => env('WHATSAPP_SESSION_TEMPLATE_LANGUAGE', 'pt_BR'),
        /** Indicativo internacional só com dígitos (ex.: 351 para PT, 55 para BR). */
        'default_calling_code' => env('WHATSAPP_DEFAULT_CALLING_CODE', ''),
        'evolution' => [
            'api_url' => env('EVOLUTION_API_URL', 'http://127.0.0.1:8082'),
            'api_key' => env('EVOLUTION_API_KEY'),
            'instance' => env('EVOLUTION_INSTANCE', 'psiconecta'),
            /** URL que a Evolution (Docker) usa para chamar o PsiConecta. Vazio = auto (host.docker.internal em dev). */
            'webhook_url' => env('EVOLUTION_WEBHOOK_URL'),
            /** Token opcional validado no header X-Webhook-Token ou query ?token= */
            'webhook_token' => env('EVOLUTION_WEBHOOK_TOKEN'),
        ],
    ],

    'ai' => [
        'enabled' => env('AI_ASSISTANT_ENABLED', true),
        /**
         * Provedor do chat:
         * - openai | chatgpt | gpt → OpenAI
         * - claude | anthropic → Anthropic Claude
         * - gemini | google → Google Gemini
         * - ollama → Ollama local (API compatível OpenAI)
         * - mock → simulação sem API externa
         */
        'provider' => strtolower((string) env('AI_PROVIDER', 'openai')),
        'openai_api_key' => env('OPENAI_API_KEY'),
        'openai_base_url' => rtrim((string) env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'openai_chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
        'openai_transcribe_model' => env('OPENAI_TRANSCRIBE_MODEL', 'whisper-1'),
        'openai_timeout' => (int) env('AI_TIMEOUT', env('OPENAI_TIMEOUT', 120)),
        'claude_api_key' => env('CLAUDE_API_KEY', env('ANTHROPIC_API_KEY')),
        'claude_base_url' => rtrim((string) env('CLAUDE_BASE_URL', 'https://api.anthropic.com'), '/'),
        'claude_chat_model' => env('CLAUDE_CHAT_MODEL', 'claude-sonnet-4-20250514'),
        'claude_api_version' => env('CLAUDE_API_VERSION', '2023-06-01'),
        'gemini_api_key' => env('GEMINI_API_KEY'),
        'gemini_base_url' => rtrim((string) env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/'),
        'gemini_chat_model' => env('GEMINI_CHAT_MODEL', 'gemini-2.0-flash'),
    ],

    'video_conference' => [
        /** Domínio Jitsi (meet.jit.si ou instância própria). */
        'jitsi_domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
        'room_prefix' => env('JITSI_ROOM_PREFIX', 'psiconecta'),
        /** Disco para gravações de sessão (local ou s3). */
        'recording_disk' => env('SESSION_RECORDING_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lembretes inteligentes da agenda (profissional)
    |--------------------------------------------------------------------------
    */
    'agenda' => [
        /** Resumo da agenda do dia (e-mail, in-app e WhatsApp opcional). */
        'daily_briefing_enabled' => env('AGENDA_DAILY_BRIEFING_ENABLED', true),
        'daily_briefing_time' => env('AGENDA_DAILY_BRIEFING_TIME', '07:00'),
        /** Lembrete X minutos antes de cada sessão agendada. */
        'upcoming_reminder_enabled' => env('AGENDA_UPCOMING_REMINDER_ENABLED', true),
        'upcoming_reminder_minutes' => (int) env('AGENDA_UPCOMING_REMINDER_MINUTES', 10),
        /** Envia também por WhatsApp quando integração e telefone do profissional estiverem activos. */
        'whatsapp_enabled' => env('AGENDA_WHATSAPP_ENABLED', true),
    ],

    'chatbot' => [
        'enabled' => env('CHATBOT_ENABLED', true),
        'widget_enabled' => env('CHATBOT_WIDGET_ENABLED', true),
        /** Respostas automáticas via WhatsApp quando não há conversa clínica vinculada. */
        'whatsapp_enabled' => env('CHATBOT_WHATSAPP_ENABLED', false),
        /** Classificação de intents, resumo e sentimento via LLM (OpenAI/Ollama). */
        'ai_enabled' => env('CHATBOT_AI_ENABLED', false),
    ],

];
