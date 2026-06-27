<?php

return [

    'invitation_expires_days' => (int) env('PATIENT_PORTAL_INVITATION_EXPIRES_DAYS', 7),

    /**
     * URL pública para links em WhatsApp/e-mail quando APP_URL é localhost.
     * Ex.: https://seu-dominio.ngrok-free.app
     */
    'public_app_url' => env('APP_PUBLIC_URL'),

];
