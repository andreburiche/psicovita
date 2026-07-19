<?php

/**
 * Valores vazios no .env (ex.: INACTIVITY_TIMEOUT_PROFESSIONAL=) viram 0 com (int),
 * o que quebrava o timeout (max(1,0)=1) e o modal quase não aparecia.
 */
$minutes = static function (mixed $value, int $default): int {
    if ($value === null || $value === '') {
        return $default;
    }

    $parsed = (int) $value;

    return $parsed > 0 ? $parsed : $default;
};

$seconds = static function (mixed $value, int $default): int {
    if ($value === null || $value === '') {
        return $default;
    }

    $parsed = (int) $value;

    return $parsed > 0 ? $parsed : $default;
};

return [

    /*
    |--------------------------------------------------------------------------
    | Timeout de inatividade (minutos) por perfil
    |--------------------------------------------------------------------------
    */
    'inactivity_timeout' => [
        'admin' => $minutes(env('INACTIVITY_TIMEOUT_ADMIN'), 30),
        'professional' => $minutes(env('INACTIVITY_TIMEOUT_PROFESSIONAL'), 60),
        'patient' => $minutes(env('INACTIVITY_TIMEOUT_PATIENT'), 60),
        'default' => $minutes(env('INACTIVITY_TIMEOUT_DEFAULT'), 60),
    ],

    /** Segundos de aviso (modal + contagem) antes do logout automático. */
    'inactivity_warning_seconds' => $seconds(env('INACTIVITY_WARNING_SECONDS'), 60),

];
