<?php
if (!defined('ABSPATH')) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm_zona se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$tabela = $wpdb->prefix . 'ze_tb_funcoes';

$mensagem = '';
$erro = '';

/* =========================================================
 * PROCESSAMENTO DO FORMULÁRIO (CRIAR / EDITAR)
 * ========================================================= */
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ze_funcao_nonce']) ) {

    if ( ! wp_verify_nonce( $_POST['ze_funcao_nonce'], 'salvar_funcao' ) ) {
        wp_die('Falha de segurança.');
    }

    $id_funcao   = isset($_POST['id_funcao']) ? intval($_POST['id_funcao']) : 0;
    $num_funcao  = sanitize_text_field($_POST['num_funcao'] ?? '');
    $nom_funcao  = sanitize_text_field($_POST['nom_funcao'] ?? '');
    $status      = isset($_POST['status_funcao']) ? intval($_POST['status_funcao']) : 1;

    if ( empty($num_funcao) || empty($nom_funcao) ) {
        $erro = 'Todos os campos são obrigatórios.';
    } else {
        if ( $id_funcao > 0 ) {
            $wpdb->update(
                $tabela,
                [
                    'num_funcao'    => $num_funcao,
                    'nom_funcao'    => $nom_funcao,
                    'status_funcao' => $status,
                    'updated_at'    => current_time('mysql'),
                ],
                [ 'id_funcao' => $id_funcao ],
                [ '%s', '%s', '%s', '%s' ],
                [ '%d' ]
            );
            $mensagem = 'Função atualizada com sucesso.';
        } else {
            $wpdb->insert(
                $tabela,
                [
                    'num_funcao'    => $num_funcao,
                    'nom_funcao'    => $nom_funcao,
                    'status_funcao' => $status,
                    'created_at'    => current_time('mysql'),
                ],
                [ '%s', '%s', '%s', '%s' ]
            );
            $mensagem = 'Função cadastrada com sucesso.';
        }
    }
}

/* =========================================================
 * CARREGAR REGISTRO PARA EDIÇÃO
 * ========================================================= */
$funcao_edicao = null;
if ( isset($_GET['editar']) ) {
    $id = intval($_GET['editar']);
    $funcao_edicao = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$tabela} WHERE id_funcao = %d", $id)
    );
}

$funcoes = $wpdb->get_results("SELECT * FROM {$tabela} ORDER BY nom_funcao ASC");
?>



<div class="ze-admin-container"> <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <div class="ze-header">
        <h1 class="ze-page-title"><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p style="color: var(--ze-text-sub); margin-top: -15px; margin-bottom: 25px;">Gerencie as nomenclaturas e códigos das funções do sistema.</p>
    </div>

    <?php if ( $mensagem ) : ?>
        <div class="ze-card" style="background: var(--ze-success-bg); color: var(--ze-success-text); padding: 15px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
            <p style="margin:0;">✅ <?= esc_html($mensagem); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( $erro ) : ?>
        <div class="ze-card" style="background: var(--ze-danger-bg); color: var(--ze-danger-text); padding: 15px; margin-bottom: 20px; border: 1px solid #fecaca;">
            <p style="margin:0;">❌ <?= esc_html($erro); ?></p>
        </div>
    <?php endif; ?>

    <div class="ze-card">
        <div class="ze-section-title">
            <span class="dashicons dashicons-plus-alt"></span> 
            <?= $funcao_edicao ? 'Editar Função' : 'Nova Função'; ?>
        </div>

        <form method="post">
            <?php wp_nonce_field('salvar_funcao', 'ze_funcao_nonce'); ?>
            <input type="hidden" name="id_funcao" value="<?= esc_attr($funcao_edicao->id_funcao ?? ''); ?>">

            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Código/Número</label>
                    <input type="text" name="num_funcao" required placeholder="Ex: 01"
                           value="<?= esc_attr($funcao_edicao->num_funcao ?? ''); ?>">
                </div>

                <div class="ze-form-group" style="grid-column: span 2;">
                    <label>Nome da Função</label>
                    <input type="text" name="nom_funcao" required placeholder="Ex: Monitor de Zona"
                           value="<?= esc_attr($funcao_edicao->nom_funcao ?? ''); ?>">
                </div>

                <div class="ze-form-group">
                    <label>Status</label>
                    <select name="status_funcao" class="ze-form-group input" style="width: 100%; height: 40px; border-radius: 6px; border: 1px solid var(--ze-border);">
                        <option value="1" <?= selected($funcao_edicao->status_funcao ?? 1, 1); ?>>Ativo</option>
                        <option value="0" <?= selected($funcao_edicao->status_funcao ?? 1, 0); ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="ze-form-footer">
                <?php if($funcao_edicao): ?>
                    <a href="?page=ze-legal-funcoes" class="ze-btn-secondary">Cancelar</a> 
                <?php endif; ?>
                <button type="submit" class="ze-btn-submit">
                    <span class="dashicons dashicons-saved"></span> 
                    <?= $funcao_edicao ? 'Atualizar Função' : 'Gravar Função'; ?>
                </button>
            </div>
        </form>
    </div>

    <div class="ze-card no-padding">
        <div style="padding: 20px 24px; font-weight: 700; border-bottom: 1px solid var(--ze-border); background: var(--ze-table-head-bg);">
            <span class="dashicons dashicons-list-view"></span> Funções Cadastradas
        </div>
        
        <table class="ze-table">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th style="width: 100px;">Código</th>
                    <th>Nome da Função</th>
                    <th style="width: 120px;">Status</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if($funcoes): foreach ( $funcoes as $f ) : ?>
                    <tr>
                        <td><span style="color: var(--ze-text-sub);">#<?= esc_html($f->id_funcao); ?></span></td>
                        <td><strong><?= esc_html($f->num_funcao); ?></strong></td>
                        <td style="font-weight: 500; color: var(--ze-text-main);"><?= esc_html($f->nom_funcao); ?></td>
                        <td>
                            <span class="ze-badge <?= intval($f->status_funcao) === 1 ? 'ze-badge-success' : 'ze-badge-danger'; ?>">
                                <?= intval($f->status_funcao) === 1 ? 'ATIVO' : 'INATIVO'; ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="?page=ze-legal-funcoes&editar=<?= esc_attr($f->id_funcao); ?>" class="ze-btn-secondary">
                                <span class="dashicons dashicons-edit"></span> Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--ze-text-sub);">Nenhuma função cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>