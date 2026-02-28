<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ZE Legal
 * Página de Cadastro de Tipos de Eventos
 */

// Segurança: ajuste a capability se necessário
if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$table = $wpdb->prefix . 'ze_tb_tipos_eventos';

/* =========================================================
 * AÇÕES (CRIAR / ATIVAR / INATIVAR / EXCLUIR)
 * ========================================================= */
if ( isset($_POST['acao']) && check_admin_referer( 'ze_tipos_eventos_nonce' ) ) {

    // Criar tipo
    if ( $_POST['acao'] === 'criar' ) {
        $descricao = sanitize_text_field( $_POST['ds_tipo_evento'] );

        if ( $descricao ) {
            $wpdb->insert(
                $table,
                [
                    'ds_tipo_evento' => $descricao,
                    'status'         => 1,
                    'created_at'     => current_time('mysql'),
                ]
            );
        }
    }

    // Alterar status
    if ( $_POST['acao'] === 'status' ) {
        $wpdb->update(
            $table,
            [
                'status'     => (int) $_POST['status'],
                'updated_at'=> current_time('mysql'),
            ],
            [ 'id_tipo_evento' => (int) $_POST['id'] ]
        );
    }

    // Excluir
    if ( $_POST['acao'] === 'excluir' ) {
        $wpdb->delete(
            $table,
            [ 'id_tipo_evento' => (int) $_POST['id'] ]
        );
    }
}

/* =========================================================
 * FILTROS
 * ========================================================= */
$filtro_nome   = isset($_GET['filtro_nome']) ? sanitize_text_field($_GET['filtro_nome']) : '';
$filtro_status = isset($_GET['filtro_status']) ? $_GET['filtro_status'] : '';

$where = "WHERE 1=1";

if ( $filtro_nome ) {
    $where .= $wpdb->prepare(" AND ds_tipo_evento LIKE %s", "%{$filtro_nome}%");
}

if ( $filtro_status !== '' ) {
    $where .= $wpdb->prepare(" AND status = %d", (int)$filtro_status);
}

$tipos = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY ds_tipo_evento ASC");
?>

<div class="wrap ze-admin-container">

    <h1 class="wp-heading-inline">Tipos de Eventos</h1>

    <hr class="wp-header-end">

    <!-- =======================
         FORMULÁRIO DE INCLUSÃO
    ======================== -->
    <h2>Cadastrar Novo Tipo</h2>

    <form method="post" style="margin-bottom:20px;">
        <?php wp_nonce_field( 'ze_tipos_eventos_nonce' ); ?>
        <input type="hidden" name="acao" value="criar">

        <input type="text"
               name="ds_tipo_evento"
               placeholder="Descrição do tipo de evento"
               required
               style="width:300px;">

        <button class="button button-primary">Adicionar</button>
    </form>

    <!-- =======================
         FILTROS
    ======================== -->
    <form method="get" style="margin-bottom:15px;">
        <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

        <input type="text"
               name="filtro_nome"
               value="<?php echo esc_attr($filtro_nome); ?>"
               placeholder="Filtrar por nome">

        <select name="filtro_status">
            <option value="">Todos</option>
            <option value="1" <?php selected($filtro_status, '1'); ?>>Ativo</option>
            <option value="0" <?php selected($filtro_status, '0'); ?>>Inativo</option>
        </select>

        <button class="button">Filtrar</button>
        <a href="<?php echo admin_url('admin.php?page=' . $_GET['page']); ?>" class="button">Limpar</a>
    </form>

    <!-- =======================
         LISTAGEM
    ======================== -->
    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Status</th>
                <th style="width:220px;">Ações</th>
            </tr>
        </thead>

        <tbody>
        <?php if ( $tipos ) : foreach ( $tipos as $t ) : ?>
            <tr>
                <td><?php echo $t->id_tipo_evento; ?></td>
                <td><?php echo esc_html($t->ds_tipo_evento); ?></td>
                <td>
                    <?php echo $t->status ? '🟢 Ativo' : '🔴 Inativo'; ?>
                </td>
                <td>
                    <!-- Ativar / Inativar -->
                    <form method="post" style="display:inline;">
                        <?php wp_nonce_field( 'ze_tipos_eventos_nonce' ); ?>
                        <input type="hidden" name="acao" value="status">
                        <input type="hidden" name="id" value="<?php echo $t->id_tipo_evento; ?>">
                        <input type="hidden" name="status" value="<?php echo $t->status ? 0 : 1; ?>">
                        <button class="button">
                            <?php echo $t->status ? 'Inativar' : 'Ativar'; ?>
                        </button>
                    </form>

                    <!-- Excluir -->
                    <form method="post"
                          style="display:inline;"
                          onsubmit="return confirm('Deseja realmente excluir este tipo?');">
                        <?php wp_nonce_field( 'ze_tipos_eventos_nonce' ); ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?php echo $t->id_tipo_evento; ?>">
                        <button class="button button-link-delete">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; else : ?>
            <tr>
                <td colspan="4">Nenhum tipo encontrado.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>
