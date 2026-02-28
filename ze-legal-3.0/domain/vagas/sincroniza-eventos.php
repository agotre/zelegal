<?php
/**
 * ZE Legal 3.0
 * Sincronização de colaborador da vaga para eventos
 *
 * Chamada direta por página.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



global $wpdb;

$tabela_vagas   = $wpdb->prefix . 'ze_tb_vagas_pleitos';
$tabela_eventos = $wpdb->prefix . 'ze_tb_eventos_vagas';

$data_hoje = current_time( 'Y-m-d' );

/**
 * 1. Buscar todas as vagas com colaborador definido
 */
$vagas = $wpdb->get_results(
    "
    SELECT 
        id_vaga_pleito,
        id_pleito,
        id_colaborador
    FROM {$tabela_vagas}
    WHERE id_colaborador IS NOT NULL
    ",
    ARRAY_A
);

if ( empty( $vagas ) ) {
    return;
}

/**
 * 2. Para cada vaga, localizar eventos vinculados
 */
foreach ( $vagas as $vaga ) {

    $eventos = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT 
                id_evento_vaga,
                id_colaborador,
                data_evento,
                compareceu
            FROM {$tabela_eventos}
            WHERE id_vaga_pleito = %d
            ",
            $vaga['id_vaga_pleito']
        ),
        ARRAY_A
    );

    if ( empty( $eventos ) ) {
        continue;
    }

    /**
     * 3. Avaliar cada evento individualmente
     */
    foreach ( $eventos as $evento ) {

        // ❌ Regra 1: evento no passado não pode ser alterado
        if ( $evento['data_evento'] < $data_hoje ) {
            continue;
        }

        // ❌ Regra 2: presença registrada bloqueia alteração
        if ( intval($evento['compareceu']) === 1 ) {
            continue;
        }

        // ❌ Regra 3: colaborador já correto
        if ( intval( $evento['id_colaborador'] ) === intval( $vaga['id_colaborador'] ) ) {
            continue;
        }

        /**
         * 4. Atualizar colaborador do evento
         */
        $wpdb->update(
            $tabela_eventos,
            [
                'id_colaborador' => $vaga['id_colaborador'],
                'updated_at'     => current_time( 'mysql' ),
            ],
            [
                'id_evento_vaga' => $evento['id_evento_vaga'],
            ],
            [ '%d', '%s' ],
            [ '%d' ]
        );
    }
}
