<?php
/**
 * convocacao-handler.php
 * Manipula as ações de aceite e interações com a convocação.
 */

if ( ! defined('ABSPATH') ) exit;

/**
 * Hook para processar o aceite vindo do formulário front-end (Portal do Colaborador)
 */
add_action('admin_post_ze_aceitar_convocacao', 'ze_legal_handle_aceite_convocacao');

function ze_legal_handle_aceite_convocacao() {
    global $wpdb;
    $prefix = $wpdb->prefix . 'ze_';

    // 1. Verificação de Segurança (Nonce)
    if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'ze_aceitar_convocacao') ) {
        wp_die('Erro de segurança: Pedido inválido ou expirado.');
    }

    // 2. Parâmetros de Entrada
    $id_convocacao = isset($_POST['id_convocacao']) ? intval($_POST['id_convocacao']) : 0;
    $user_id       = get_current_user_id();
    $agora         = current_time('mysql');
    $user_ip       = $_SERVER['REMOTE_ADDR'];

    if ( ! $id_convocacao ) {
        wp_die('ID de convocação não fornecido.');
    }

    // 3. Obter status atual para o log
    $status_anterior = $wpdb->get_var($wpdb->prepare(
        "SELECT status_convocacao FROM {$prefix}tb_convocacao WHERE id_convocacao = %d",
        $id_convocacao
    ));

    // 4. Executar a Atualização do Aceite
    // Usamos o snapshot da tabela para garantir que o aceite fica vinculado a este registo fixo
    $resultado = $wpdb->update(
        "{$prefix}tb_convocacao",
        array(
            'status_convocacao'      => 'CONVOCACAO_ACEITA',
            'data_aceite'            => $agora,
            'ip_aceite'              => $user_ip,
            'data_atualizacao'       => $agora,
            'id_usuario_atualizacao' => $user_id,
            'updated_at'             => $agora
        ),
        array('id_convocacao' => $id_convocacao),
        array('%s', '%s', '%s', '%s', '%d', '%s'),
        array('%d')
    );

    if ( $resultado !== false ) {
        // 5. Registar Log de Auditoria
        // Essencial para rastrear quem aceitou e de onde (IP)
        $wpdb->insert(
            "{$prefix}tb_log_convocacao",
            array(
                'id_convocacao'   => $id_convocacao,
                'status_anterior' => $status_anterior,
                'status_novo'     => 'CONVOCACAO_ACEITA',
                'acao'            => 'ACEITE_DIGITAL',
                'descricao'       => 'Colaborador aceitou a convocação via portal eletrónico.',
                'id_usuario'      => $user_id,
                'ip_origem'       => $user_ip,
                'data_evento'     => $agora
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        // 6. Redirecionamento com feedback
        $redirect_url = add_query_arg('aceite', 'sucesso', wp_get_referer());
        wp_redirect($redirect_url);
        exit;
    } else {
        wp_die('Erro ao gravar o aceite no banco de dados.');
    }
}

/**
 * Hook para processar outras ações futuras, como 'Recusar' ou 'Pedir Dispensa'
 */
// add_action('admin_post_ze_recusar_convocacao', 'ze_legal_handle_recusa_convocacao');