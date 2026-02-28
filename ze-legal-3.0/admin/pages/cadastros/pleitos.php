<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$tabela = $wpdb->prefix . 'ze_tb_pleitos';

/**
 * LÓGICA DE PROCESSAMENTO (PHP)
 */

// 1. Ativar Pleito
if ( isset($_POST['zelegal_ativar_pleito']) ) {
    check_admin_referer('zelegal_ativar_pleito_nonce');
    $id = intval($_POST['zelegal_ativar_pleito']);
    $wpdb->query("UPDATE {$tabela} SET status_pleito = 0");
    $wpdb->update($tabela, ['status_pleito' => 1], ['id_pleito' => $id]);
    echo '<div class="notice notice-success is-dismissible"><p>Pleito ativado com sucesso!</p></div>';
}

// 2. Salvar ou Atualizar
if ( isset($_POST['zelegal_salvar_pleito_btn']) ) {
    check_admin_referer('zelegal_salvar_pleito_nonce');

    $dados = [
        'ano'        => intval($_POST['ano']),
        'descricao'  => sanitize_text_field($_POST['descricao']),
        'dt_1turno'  => !empty($_POST['dt_1turno']) ? $_POST['dt_1turno'] : null,
        'dt_2turno'  => !empty($_POST['dt_2turno']) ? $_POST['dt_2turno'] : null,
        'updated_at' => current_time('mysql'),
    ];

    if ( ! empty($_POST['id_pleito']) ) {
        $wpdb->update($tabela, $dados, ['id_pleito' => intval($_POST['id_pleito'])]);
        echo '<div class="notice notice-success is-dismissible"><p>Dados atualizados!</p></div>';
    } else {
        $dados['status_pleito'] = 0;
        $dados['created_at']    = current_time('mysql');
        $wpdb->insert($tabela, $dados);
        echo '<div class="notice notice-success is-dismissible"><p>Novo pleito cadastrado!</p></div>';
    }
}

$pleitos = $wpdb->get_results("SELECT * FROM {$tabela} ORDER BY ano DESC");
?>

<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>
    
    <header>
        <h1 class="ze-page-title">Cadastro dos Pleito Eleitoal</h1>
        <p style="color: var(--ze-text-sub); margin: 5px 0 0 0;">Defina o pleito ativo.</p>
    </header>

    <div class="ze-card">
        <h2 class="ze-section-title">
            <span class="dashicons dashicons-calendar-alt"></span> Gerenciar Ano Eleitoral
        </h2>
        
        <form method="post" id="form-pleito">
            <?php wp_nonce_field('zelegal_salvar_pleito_nonce'); ?>
            <input type="hidden" name="id_pleito" id="id_pleito">

            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Ano do Pleito</label>
                    <input type="number" name="ano" id="ano" required placeholder="Ex: 2026">
                </div>
                <div class="ze-form-group" style="flex: 2;">
                    <label>Descrição do Evento</label>
                    <input type="text" name="descricao" id="descricao" placeholder="Ex: Eleições Gerais">
                </div>
                <div class="ze-form-group">
                    <label>Data 1º Turno</label>
                    <input type="date" name="dt_1turno" id="dt_1turno">
                </div>
                <div class="ze-form-group">
                    <label>Data 2º Turno</label>
                    <input type="date" name="dt_2turno" id="dt_2turno">
                </div>
            </div>

            <div class="ze-form-footer">
                <button type="button" class="ze-btn-secondary" onclick="window.location.reload();">Limpar / Novo</button>
                <button type="submit" name="zelegal_salvar_pleito_btn" class="ze-btn-submit">
                    Salvar Dados do Pleito
                </button>
            </div>
        </form>
    </div>

    <h2 class="ze-section-title">Pleitos Cadastrados</h2>
    <div class="ze-card no-padding">
        <table class="ze-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Ano</th>
                    <th>Descrição</th>
                    <th>Cronograma</th>
                    <th>Status</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pleitos): foreach ($pleitos as $p): 
                    $is_active = ((int)$p->status_pleito === 1);
                ?>
                <tr>
                    <td><strong><?= esc_html($p->ano); ?></strong></td>
                    <td><?= esc_html($p->descricao); ?></td>
                    <td>
                        <small style="color: #666;">
                            1º: <?= $p->dt_1turno ? date('d/m/Y', strtotime($p->dt_1turno)) : '--'; ?> | 
                            2º: <?= $p->dt_2turno ? date('d/m/Y', strtotime($p->dt_2turno)) : '--'; ?>
                        </small>
                    </td>
                    <td>
                        <span class="ze-badge <?= $is_active ? 'ze-badge-success' : 'ze-badge-danger'; ?>">
                            <?= $is_active ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </td>
                    <td style="text-align: right; display: flex; justify-content: flex-end; gap: 15px; align-items: center;">
                        <a href="javascript:void(0)" 
                           class="ze-edit-link btn-edit-pleito" 
                           data-pleito='<?= esc_attr(json_encode($p)); ?>'>
                            <span class="dashicons dashicons-edit"></span> Editar
                        </a>

                        <?php if (!$is_active): ?>
                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('zelegal_ativar_pleito_nonce'); ?>
                                <input type="hidden" name="zelegal_ativar_pleito" value="<?= intval($p->id_pleito); ?>">
                                <button type="submit" class="ze-edit-link" style="color: #166534; background:none; border:none; cursor:pointer;" onclick="return confirm('Ativar este ano?')">
                                    <span class="dashicons dashicons-yes"></span> Ativar
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="padding:40px; text-align:center; color:#999;">Nenhum pleito encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.btn-edit-pleito').forEach(btn => {
    btn.addEventListener('click', () => {
        try {
            const p = JSON.parse(btn.dataset.pleito);
            document.getElementById('id_pleito').value = p.id_pleito;
            document.getElementById('ano').value = p.ano;
            document.getElementById('descricao').value = p.descricao || '';
            document.getElementById('dt_1turno').value = p.dt_1turno ? p.dt_1turno.substring(0,10) : '';
            document.getElementById('dt_2turno').value = p.dt_2turno ? p.dt_2turno.substring(0,10) : '';
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            const card = document.querySelector('.ze-card');
            card.style.borderColor = 'var(--ze-primary)';
            setTimeout(() => card.style.borderColor = 'rgba(0,0,0,0.08)', 1000);
            
        } catch (e) { console.error("Erro ao carregar dados", e); }
    });
});
</script>