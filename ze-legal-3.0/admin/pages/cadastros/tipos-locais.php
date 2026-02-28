<?php
if (!defined('ABSPATH')) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm_zona se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
    wp_die( 'Acesso não autorizado.' );
}


global $wpdb;
$tabela = $wpdb->prefix . 'ze_tb_tipos_locais';

$mensagem = '';
$erro = '';

/* =========================================================
 * PROCESSAMENTO DO FORMULÁRIO (CRIAR / EDITAR)
 * ========================================================= */
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ze_tipo_local_nonce']) ) {

    if ( ! wp_verify_nonce( $_POST['ze_tipo_local_nonce'], 'salvar_tipo_local' ) ) {
        wp_die('Falha de segurança.');
    }

    $id_tipo_local = isset($_POST['id_tipo_local']) ? intval($_POST['id_tipo_local']) : 0;
    $ds_tipo_local = sanitize_text_field($_POST['ds_tipo_local'] ?? '');
    $ativo         = isset($_POST['ativo']) ? intval($_POST['ativo']) : 1;

    if ( empty($ds_tipo_local) ) {
        $erro = 'Descrição do tipo de local é obrigatória.';
    } else {
        if ( $id_tipo_local > 0 ) {
            $wpdb->update(
                $tabela,
                [
                    'ds_tipo_local' => $ds_tipo_local,
                    'ativo'         => $ativo,
                    'updated_at'    => current_time('mysql'),
                ],
                [ 'id_tipo_local' => $id_tipo_local ],
                [ '%s', '%d', '%s' ],
                [ '%d' ]
            );
            $mensagem = 'Tipo de local atualizado com sucesso.';
        } else {
            $wpdb->insert(
                $tabela,
                [
                    'ds_tipo_local' => $ds_tipo_local,
                    'ativo'         => $ativo,
                    'created_at'    => current_time('mysql'),
                ],
                [ '%s', '%d', '%s' ]
            );
            $mensagem = 'Tipo de local cadastrado com sucesso.';
        }
    }
}

/* =========================================================
 * CARREGAR REGISTRO PARA EDIÇÃO
 * ========================================================= */
$tipo_edicao = null;
if ( isset($_GET['editar']) ) {
    $id = intval($_GET['editar']);
    $tipo_edicao = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$tabela} WHERE id_tipo_local = %d", $id)
    );
}

$tipos = $wpdb->get_results("SELECT * FROM {$tabela} ORDER BY ds_tipo_local ASC");
?>


<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>
    
    <div class="ze-header-main" style="margin-bottom: 25px;">
        <h1 class="ze-page-title"></span> Cadastro de Tipos de Locais</h1>
        <p style="color: var(--ze-text-sub); margin: 5px 0 0 0;">Defina as categorias de locais (Ex: Local de Votaçao, Cartório, Equipes Volantes, Transporte de Urnas, etc).</p>
    </div>

    <?php if ( $mensagem ) : ?>
        <div class="notice notice-success is-dismissible" style="border-radius:10px; margin-bottom:20px;"><p><?= esc_html($mensagem); ?></p></div>
    <?php endif; ?>

    <?php if ( $erro ) : ?>
        <div class="notice notice-error is-dismissible" style="border-radius:10px; margin-bottom:20px;"><p><?= esc_html($erro); ?></p></div>
    <?php endif; ?>

    <div class="ze-card">
        <h2 class="ze-section-title">
            <span class="dashicons dashicons-location-alt"></span> 
            <?= $tipo_edicao ? 'Editar Tipo de Local' : 'Novo Tipo de Local'; ?>
        </h2>

        <form method="post">
            <?php wp_nonce_field('salvar_tipo_local', 'ze_tipo_local_nonce'); ?>
            <input type="hidden" name="id_tipo_local" value="<?= esc_attr($tipo_edicao->id_tipo_local ?? ''); ?>">

            <div class="ze-form-grid">
                <div class="ze-form-group" style="grid-column;">
                    <label>Descrição do Tipo</label>
                    <input type="text" name="ds_tipo_local" required placeholder="Ex: Escola Municipal"
                           value="<?= esc_attr($tipo_edicao->ds_tipo_local ?? ''); ?>">
                </div>
            
                <div class="ze-form-group" style="grid-column;">        
                    <label>Status</label>
                    <select name="ativo" style="width: 20%; height: 40px; border-radius: 6px; border: 1px solid var(--ze-border);">
                        <option value="1" <?= selected($tipo_edicao->ativo ?? 1, 1); ?>>Ativo</option>
                        <option value="0" <?= selected($tipo_edicao->ativo ?? 1, 0); ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="ze-form-footer">
                <?php if($tipo_edicao): ?>
                    <a href="?page=<?php echo $_GET['page']; ?>" class="ze-btn-secondary" style="text-decoration: none;">Cancelar</a>
                <?php endif; ?>
                <button type="submit" class="ze-btn-submit">
                    <span class="dashicons dashicons-saved" style="margin-top: 4px;"></span> 
                    <?= $tipo_edicao ? 'Atualizar Tipo' : 'Gravar Tipo'; ?>
                </button>
            </div>
        </form>
    </div>

    <div class="ze-card no-padding">
        <h2 class="ze-section-title" style="padding: 24px 24px 0 24px; border-bottom: none; margin-bottom: 0;">
            <span class="dashicons dashicons-list-view"></span> Categorias Cadastradas
        </h2>
        
        <table class="ze-table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Descrição do Tipo de Local</th>
                    <th style="width: 150px;">Status</th>
                    <th style="text-align: right; width: 120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if($tipos): foreach ( $tipos as $t ) : ?>
                    <tr>
                        <td><span style="color: var(--ze-text-sub);">#<?= esc_html($t->id_tipo_local); ?></span></td>
                        <td><strong><?= esc_html($t->ds_tipo_local); ?></strong></td>
                        <td>
                            <span class="ze-badge <?= $t->ativo ? 'ze-badge-success' : 'ze-badge-danger'; ?>">
                                <?= $t->ativo ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </td>
                        
                        <td style="text-align: right; vertical-align: middle;">
                            <a href="?page=<?php echo $_GET['page']; ?>&editar=<?= esc_attr($t->id_tipo_local); ?>" 
                               class="ze-btn-secondary" 
                               style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px; justify-content: center;">
                                <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span> 
                                Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align: center; padding: 30px; color: var(--ze-text-sub);">Nenhuma categoria cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>