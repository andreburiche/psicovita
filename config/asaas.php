<?php

return [

    'enabled' => (bool) env('ASAAS_ENABLED', false),

    'api_key' => env('ASAAS_API_KEY'),

    'environment' => env('ASAAS_ENVIRONMENT', 'sandbox'),

    'base_url' => env('ASAAS_BASE_URL', env('ASAAS_ENVIRONMENT', 'sandbox') === 'production'
        ? 'https://api.asaas.com/v3'
        : 'https://sandbox.asaas.com/api/v3'),

    'webhook_token' => env('ASAAS_WEBHOOK_TOKEN'),

    /*
    | Split de pagamentos clínicos: repassa professional_amount à carteira do profissional.
    | Requer ASAAS_SPLIT_ENABLED=true e users.asaas_wallet_id preenchido.
    */
    'split_enabled' => (bool) env('ASAAS_SPLIT_ENABLED', false),

    'platform_wallet_id' => env('ASAAS_PLATFORM_WALLET_ID'),

    /*
    | Criação automática de carteira (Asaas Connect / subconta).
    | Em dev/stub funciona sem credenciais; em produção exige CPF/CNPJ do profissional.
    */
    'connect_enabled' => (bool) env('ASAAS_CONNECT_ENABLED', false),

    'connect_defaults' => [
        'birth_date' => env('ASAAS_CONNECT_BIRTH_DATE', '1990-01-01'),
        'company_type' => env('ASAAS_CONNECT_COMPANY_TYPE', 'INDIVIDUAL'),
        'income_value' => (float) env('ASAAS_CONNECT_INCOME_VALUE', 5000),
        'address' => env('ASAAS_CONNECT_ADDRESS'),
        'address_number' => env('ASAAS_CONNECT_ADDRESS_NUMBER'),
        'province' => env('ASAAS_CONNECT_PROVINCE'),
        'postal_code' => env('ASAAS_CONNECT_POSTAL_CODE'),
    ],

    /*
    | Sem conta Asaas: QR estático do seu banco (ficheiro em public/).
    | Coloque a imagem exportada do app do banco, ex.: public/images/pix-nubank.png
    */
    'pix_fallback_image' => env('ASAAS_PIX_FALLBACK_IMAGE', 'images/pix-fallback.svg'),
    'pix_fallback_payload' => env('ASAAS_PIX_FALLBACK_PAYLOAD'),
    'pix_fallback_bank' => env('ASAAS_PIX_FALLBACK_BANK'),

];
