<?php

/**
 * Remove todos os caracteres não numéricos (LGPD / armazenamento normalizado).
 */
function only_digits(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    return preg_replace('/\D/', '', $value) ?? '';
}

function is_valid_cpf(?string $cpf): bool
{
    $n = only_digits($cpf ?? '');
    if (strlen($n) !== 11 || preg_match('/^(\d)\1{10}$/', $n)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += (int) $n[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($d !== (int) $n[$t]) {
            return false;
        }
    }

    return true;
}

/**
 * Completa 9 dígitos base com os verificadores de CPF (útil em factories / seeds).
 */
function cpf_append_check_digits(string $nineBase): string
{
    $n = only_digits($nineBase);
    if (strlen($n) !== 9) {
        return '';
    }

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += (int) $n[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        $n .= (string) $d;
    }

    return $n;
}

function is_valid_cnpj(?string $cnpj): bool
{
    $n = only_digits($cnpj ?? '');
    if (strlen($n) !== 14 || preg_match('/^(\d)\1{13}$/', $n)) {
        return false;
    }

    $calc = function (string $base, array $weights): int {
        $sum = 0;
        foreach ($weights as $i => $w) {
            $sum += (int) $base[$i] * $w;
        }
        $mod = $sum % 11;

        return $mod < 2 ? 0 : 11 - $mod;
    };

    $d1 = $calc(substr($n, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
    if ($d1 !== (int) $n[12]) {
        return false;
    }

    $d2 = $calc(substr($n, 0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

    return $d2 === (int) $n[13];
}

/** Ex.: 00.000.000/0001-00 */
function format_cnpj_human(?string $digits): string
{
    $n = only_digits($digits ?? '');
    if (strlen($n) !== 14) {
        return $digits ?? '';
    }

    return substr($n, 0, 2).'.'.substr($n, 2, 3).'.'.substr($n, 5, 3).'/'.substr($n, 8, 4).'-'.substr($n, 12, 2);
}

function is_valid_br_phone_digits(?string $digits): bool
{
    $n = only_digits($digits ?? '');
    if ($n === '') {
        return true;
    }

    $len = strlen($n);

    return $len >= 10 && $len <= 11;
}

/** CEP apenas dígitos: 8 caracteres */
function is_valid_cep_digits(?string $digits): bool
{
    return strlen(only_digits($digits ?? '')) === 8;
}

/** Ex.: 00000-000 */
function format_cep_human(?string $digits): string
{
    $n = only_digits($digits ?? '');
    if (strlen($n) !== 8) {
        return $digits ?? '';
    }

    return substr($n, 0, 5).'-'.substr($n, 5, 3);
}

/** Ex.: 000.000.000-00 */
function format_cpf_human(?string $digits): string
{
    $n = only_digits($digits ?? '');
    if (strlen($n) !== 11) {
        return $digits ?? '';
    }

    return substr($n, 0, 3).'.'.substr($n, 3, 3).'.'.substr($n, 6, 3).'-'.substr($n, 9, 2);
}

/** Ex.: (11) 98765-4321 */
function format_phone_br_human(?string $digits): string
{
    $n = only_digits($digits ?? '');
    if (strlen($n) < 10) {
        return $digits ?? '';
    }
    $ddd = substr($n, 0, 2);
    $rest = substr($n, 2);
    if (strlen($rest) === 9) {
        return '('.$ddd.') '.substr($rest, 0, 5).'-'.substr($rest, 5, 4);
    }

    return '('.$ddd.') '.substr($rest, 0, 4).'-'.substr($rest, 4, 4);
}
