<?php
/**
 * Validação de CPF
 * Regra pura de domínio
 */

if ( ! defined('ABSPATH') ) {
    // Permite uso fora do WordPress também
}

/**
 * Remove caracteres não numéricos
 */
function cpf_somente_numeros( $cpf ) {
    return preg_replace('/\D/', '', $cpf);
}

/**
 * Valida CPF
 * Retorna true ou false
 */
function cpf_valida( $cpf ) {

    $cpf = cpf_somente_numeros( $cpf );

    if ( strlen( $cpf ) !== 11 ) {
        return false;
    }

    if ( preg_match('/^(\d)\1{10}$/', $cpf ) ) {
        return false;
    }

    for ( $t = 9; $t < 11; $t++ ) {
        $soma = 0;
        for ( $i = 0; $i < $t; $i++ ) {
            $soma += $cpf[$i] * ( ($t + 1) - $i );
        }
        $digito = ( ( 10 * $soma ) % 11 ) % 10;
        if ( $cpf[$i] != $digito ) {
            return false;
        }
    }

    return true;
}
