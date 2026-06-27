<?php

return [

    /*
    | Percentual retido pela plataforma em pagamentos com split (ex.: 10 = 10%).
    */
    'platform_fee_percent' => (float) env('PAYMENT_PLATFORM_FEE_PERCENT', 10),

    'default_session_amount' => (float) env('PAYMENT_DEFAULT_SESSION_AMOUNT', 150),

    'auto_charge_on_session_created' => (bool) env('PAYMENT_AUTO_CHARGE_ON_SESSION', true),

    'patient_notifications_enabled' => (bool) env('PAYMENT_PATIENT_NOTIFICATIONS_ENABLED', true),

    'patient_reminder_days' => (int) env('PAYMENT_PATIENT_REMINDER_DAYS', 3),

    'professional_notifications_enabled' => (bool) env('PAYMENT_PROFESSIONAL_NOTIFICATIONS_ENABLED', true),

];
