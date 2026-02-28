<?php

if ( ! defined('ABSPATH') ) exit;


add_action('wp_ajax_ze_registro_barcode', 'ze_registro_barcode_handler');

function ze_registro_barcode_handler() {

    if ( ! current_user_can('ze_cadastro_adm_cartorio') ) {
        wp_send_json_error(['msg' => 'Acesso não autorizado.']);
    }

    global $wpdb;
    $prefix = $wpdb->prefix;
    $vw_eventos = $prefix . 'ze_vw_eventos_vagas_locais';
    $table_eventos = $prefix . 'ze_tb_eventos_vagas';

    $inscricao = isset($_POST['num_inscricao']) ? sanitize_text_field($_POST['num_inscricao']) : '';
    $data_sel  = isset($_POST['data_evento']) ? sanitize_text_field($_POST['data_evento']) : '';

    if (empty($inscricao) || empty($data_sel)) {
        wp_send_json_error(['msg' => 'Dados inválidos.']);
    }

    $evento = $wpdb->get_row($wpdb->prepare(
        "SELECT id_evento_vaga, nom_eleitor, nom_funcao 
         FROM $vw_eventos 
         WHERE num_inscricao = %s 
         AND data_evento = %s
         LIMIT 1",
        $inscricao,
        $data_sel
    ));

    if ($evento) {

        $wpdb->update(
            $table_eventos,
            ['compareceu' => 1],
            ['id_evento_vaga' => $evento->id_evento_vaga],
            ['%d'],
            ['%d']
        );

        wp_send_json_success([
            'nome'   => esc_html($evento->nom_eleitor),
            'funcao' => esc_html($evento->nom_funcao)
        ]);

    } else {
        wp_send_json_error(['msg' => 'Inscrição não localizada para este dia.']);
    }
}