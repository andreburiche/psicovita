<?php

return [

    'trial_days' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 14),

    'expiring_soon_days' => (int) env('SUBSCRIPTION_EXPIRING_SOON_DAYS', 3),

    /** E-mail + in-app para admins quando pagamento/renovação de assinatura é confirmado (webhook Asaas). */
    'admin_notifications_enabled' => env('SUBSCRIPTION_ADMIN_NOTIFICATIONS', true),

    /** Permite ao admin activar/renovar planos manualmente (sem Asaas/Mercado Pago). */
    'manual_activation_enabled' => env('SUBSCRIPTION_MANUAL_ACTIVATION', true),

    /** Após pagamento (webhook/checkout), exige validação do admin para activar o plano. */
    'require_admin_after_payment' => env('SUBSCRIPTION_REQUIRE_ADMIN_AFTER_PAYMENT', true),

    'annual_discount_percent' => (int) env('SUBSCRIPTION_ANNUAL_DISCOUNT_PERCENT', 17),

    /*
    | Features bloqueadas quando a assinatura não está activa:
    | create_patient, create_session, create_clinical_record, use_ai
    */
    'features' => [
        'create_patient',
        'create_session',
        'create_clinical_record',
        'use_ai',
    ],

];
