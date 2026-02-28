<?php
if ( ! defined('ABSPATH') ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

/* ======================================================
   BUSCA STATUS DINÂMICOS PARA O FILTRO
====================================================== */
$status_disponiveis = $wpdb->get_col("SELECT DISTINCT status_convocacao FROM {$prefix}tb_convocacao WHERE status_convocacao IS NOT NULL ORDER BY status_convocacao ASC");

/* ======================================================
   FILTROS E CONSULTA
====================================================== */
$nome      = isset($_GET['nome']) ? sanitize_text_field($_GET['nome']) : '';
$cpf       = isset($_GET['cpf']) ? preg_replace('/\D/', '', $_GET['cpf']) : '';
$status    = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
// NOVO FILTRO: SEI
$sei_filter = isset($_GET['sei_filter']) ? sanitize_text_field($_GET['sei_filter']) : '';

$where = [];
$params = [];

if ($nome) {
    $where[] = 'c.nom_eleitor LIKE %s';
    $params[] = '%' . $wpdb->esc_like($nome) . '%';
}
if ($cpf) {
    $where[] = 'c.num_cpf = %s';
    $params[] = $cpf;
}
if ($status) {
    $where[] = 'cv.status_convocacao = %s';
    $params[] = $status;
}
// Lógica do Filtro SEI
if ($sei_filter === 'pendente') {
    // Agora o filtro de pendentes ignora quem já foi dispensado
    $where[] = "(cv.id_evento_sei IS NULL OR cv.id_evento_sei = '') AND cv.status_convocacao != 'DISPENSADO'";
} elseif ($sei_filter === 'inserido') {
    $where[] = "(cv.id_evento_sei IS NOT NULL AND cv.id_evento_sei != '')";
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT
    cv.id_convocacao,
    cv.status_convocacao,
    cv.data_aceite,
    cv.id_evento_sei,
    c.nom_eleitor,
    c.num_cpf,
    p.ano,
    z.num_zona
FROM {$prefix}tb_convocacao cv
INNER JOIN {$prefix}tb_colaboradores c ON c.id_colaborador = cv.id_colaborador
INNER JOIN {$prefix}tb_pleitos p ON p.id_pleito = cv.id_pleito
LEFT JOIN {$prefix}tb_locais l ON l.id_local = cv.id_local
LEFT JOIN {$prefix}tb_zonas z ON z.id_zona = l.id_zona
{$where_sql}
ORDER BY cv.created_at DESC
";

$convocacoes = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
?>

<style>
    .ze-premium-wrapper { margin: 20px 20px 0 0; }
    .ze-page-title { font-size: 23px; font-weight: 600; margin-bottom: 20px; color: #1d2327; }
    
    .ze-filter-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #c3c4c7; padding: 20px; margin-bottom: 25px; }
    /* Ajustado grid para acomodar o novo filtro */
    .ze-filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; }
    
    .ze-input-group { display: flex; flex-direction: column; gap: 5px; }
    .ze-input-group label { font-size: 12px; font-weight: 600; color: #50575e; text-transform: uppercase; }
    .ze-input-group input, .ze-input-group select { 
        height: 35px !important; border-radius: 4px !important; border: 1px solid #8c8f94 !important; line-height: 1 !important;
    }

    .ze-table-card { background: #fff; border-radius: 8px; border: 1px solid #c3c4c7; overflow: hidden; }
    .ze-table { width: 100%; border-collapse: collapse; margin: 0; }
    .ze-table thead { background: #f6f7f7; }
    .ze-table th { padding: 12px 15px; text-align: left; font-weight: 600; color: #2c3338; border-bottom: 1px solid #c3c4c7; font-size: 13px; }
    .ze-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f1; vertical-align: middle; font-size: 13px; }
    .ze-table tr:hover { background: #f0f6fb; }

    .ze-badge { 
        padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; font-family: monospace;
        background: #f0f0f1; color: #50575e; border: 1px solid #c3c4c7;
    }
    .ze-badge[data-status*="CONVOCACAO_ACEITA"] { background: #edfaef; color: #135e23; border-color: #7ad03a; }
    
    /* Estilos para o status SEI */
    .sei-info { display: block; font-size: 10px; margin-top: 4px; }
    .sei-status-pendente { color: #d63638; font-weight: bold; text-transform: uppercase; }
    .sei-status-ok { color: #2271b1; }

    .btn-submit { background: #2271b1 !important; color: white !important; border: none !important; padding: 0 15px !important; height: 35px !important; border-radius: 4px !important; cursor: pointer; font-weight: 600; }
    .btn-reset { color: #2271b1; text-decoration: none; font-size: 13px; margin-left: 10px; }
</style>

<div class="ze-admin-container">
    <h1 class="ze-page-title">Convocações — Consulta em Cartório</h1>

    <div class="ze-filter-card">
        <form method="get">
            <input type="hidden" name="page" value="ze-legal-convocacao-cartorio-consulta">
            
            <div class="ze-filter-grid">
                <div class="ze-input-group">
                    <label>Nome do Colaborador</label>
                    <input type="text" name="nome" placeholder="Filtrar por nome..." value="<?= esc_attr($nome) ?>">
                </div>

                <div class="ze-input-group">
                    <label>CPF</label>
                    <input type="text" name="cpf" placeholder="Somente números" value="<?= esc_attr($cpf) ?>">
                </div>

                <div class="ze-input-group">
                    <label>Status no Sistema</label>
                    <select name="status">
                        <option value="">-- Todos --</option>
                        <?php foreach ($status_disponiveis as $s): ?>
                            <option value="<?= esc_attr($s) ?>" <?= selected($status, $s) ?>><?= esc_html($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ze-input-group">
                    <label>Status SEI</label>
                    <select name="sei_filter">
                        <option value="">-- Todos --</option>
                        <option value="pendente" <?= selected($sei_filter, 'pendente') ?>>Pendente SEI</option>
                        <option value="inserido" <?= selected($sei_filter, 'inserido') ?>>Inserido SEI</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn-submit">Filtrar</button>
                    <a href="<?= admin_url('admin.php?page=ze-legal-convocacao-cartorio-consulta') ?>" class="btn-reset">Limpar</a>
                </div>
            </div>
        </form>
    </div>

    <div class="ze-table-card">
        <table class="ze-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Colaborador</th>
                    <th>CPF</th>
                    <th>Zona / Ano / SEI</th>
                    <th>Status</th>
                    <th>Data Aceite</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($convocacoes): foreach ($convocacoes as $c): ?>
                    <tr>
                        <td style="color: #646970;">#<?= $c->id_convocacao ?></td>
                        <td><strong><?= esc_html($c->nom_eleitor) ?></strong></td>
                        <td><?= esc_html($c->num_cpf) ?></td>
                        <td>
                            <?= esc_html($c->num_zona) ?>ª / <?= esc_html($c->ano) ?>
                            <div class="sei-info">
                                <?php if (!empty($c->id_evento_sei)): ?>
                                    <span class="sei-status-ok">SEI: <strong><?= esc_html($c->id_evento_sei) ?></strong></span>
                                    
                                <?php elseif (trim($c->status_convocacao) !== 'DISPENSADO'): ?>
                                    <span class="sei-status-pendente">SEI: Pendente</span>
                                    
                                <?php else: ?>
                                    <span style="color:#999;">SEI: N/A (Dispensado)</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="ze-badge" data-status="<?= esc_attr($c->status_convocacao) ?>">
                                <?= esc_html($c->status_convocacao) ?>
                            </span>
                        </td>
                        <td><?= $c->data_aceite ? date('d/m/Y H:i', strtotime($c->data_aceite)) : '<span style="color:#ccc">Aguardando</span>' ?></td>
                        <td style="text-align: right;">
                            <a href="<?= esc_url(admin_url('admin.php?page=ze-legal-convocacao-cartorio-gerenciar&id=' . $c->id_convocacao)) ?>" 
                               class="button button-small">Gerenciar</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 30px; color: #646970;">Nenhum registro encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>