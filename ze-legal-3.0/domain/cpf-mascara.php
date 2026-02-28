<?php
/**
 * Máscara de CPF
 * Apenas formatação visual
 */

if ( ! defined('ABSPATH') ) {
    // Permite uso fora do WordPress também
}

/**
 * Aplica máscara 000.000.000-00
 */
function cpf_mascara( $cpf ) {

    $cpf = preg_replace('/\D/', '', $cpf);

    if ( strlen( $cpf ) !== 11 ) {
        return $cpf;
    }

    return substr($cpf, 0, 3) . '.' .
           substr($cpf, 3, 3) . '.' .
           substr($cpf, 6, 3) . '-' .
           substr($cpf, 9, 2);
}
