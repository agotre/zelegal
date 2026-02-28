<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Aplica máscara +55 XX XXXXX-XXXX
 * * Suporta 8 ou 9 dígitos e trata duplicidade de DDI.
 */
function ze_telefone_mascara( $numero ) {

    if ( empty($numero) ) {
        return null;
    }

    // Remove tudo que não for número
    $numero = preg_replace('/\D/', '', $numero);

    // Remove prefixo 55 se já vier no número
    if ( substr($numero, 0, 2) === '55' && strlen($numero) > 11 ) {
        $numero = substr($numero, 2);
    }

    // Deve ter DDD + número
    if ( strlen($numero) < 10 ) {
        return null;
    }

    $ddd = substr($numero, 0, 2);
    $telefone = substr($numero, 2);

    // Celular (9 dígitos)
    if ( strlen($telefone) == 9 ) {
        return "+55 ({$ddd}) " .
               substr($telefone, 0, 5) . "-" .
               substr($telefone, 5, 4);
    }

    // Fixo (8 dígitos)
    if ( strlen($telefone) == 8 ) {
        return "+55 ({$ddd}) " .
               substr($telefone, 0, 4) . "-" .
               substr($telefone, 4, 4);
    }

    return null;
}