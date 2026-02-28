<?php
/**
 * Remove qualquer caractere não numérico de uma string (Pontos, traços, espaços).
 * Ideal para preparar o CPF antes de consultas ao banco de dados ou validações.
 *
 * @param string $cpf CPF formatado ou sujo.
 * @return string Retorna apenas os dígitos.
 */
function cpf_limpa( $cpf ) {
    if ( empty( $cpf ) ) {
        return '';
    }

    // Remove tudo que NÃO for número (0-9)
    return preg_replace( '/[^0-9]/', '', $cpf );
}