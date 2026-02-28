<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}


global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

/* Tabelas */
$tbl_pleitos      = "{$prefix}tb_pleitos";
$tbl_zonas        = "{$prefix}tb_zonas";
$tbl_locais       = "{$prefix}tb_locais";
$tbl_tipos_locais = "{$prefix}tb_tipos_locais";
$tbl_secoes       = "{$prefix}tb_secoes";
$tbl_vagas        = "{$prefix}tb_vagas_pleitos";

/* Pleito ativo */
$pleito_ativo = $wpdb->get_row("SELECT * FROM {$tbl_pleitos} WHERE status_pleito = '1' ORDER BY ano DESC, id_pleito DESC LIMIT 1");

if ( ! $pleito_ativo ) {
    echo '<div class="notice notice-warning"><p>Não há pleito ativo.</p></div>';
    return;
}

/* Filtros */
$id_zona = isset($_GET['id_zona']) ? intval($_GET['id_zona']) : 0;
$apenas_pendentes = isset($_GET['pendentes']) && $_GET['pendentes'] === '1';

$zonas = $wpdb->get_results("SELECT * FROM {$tbl_zonas} ORDER BY num_zona ASC");

$locais = [];
$stats_zona = ['total' => 0, 'preenchidas' => 0];

if ( $id_zona ) {
    // Query robusta que já calcula o status de preenchimento
    $sql = $wpdb->prepare("
        SELECT 
            l.id_local, l.nom_local, l.num_local, tl.ds_tipo_local,
            COUNT(v.id_vaga_pleito) as total_vagas,
            SUM(CASE WHEN v.status_vaga <> 'DISPONIVEL' THEN 1 ELSE 0 END) as preenchidas,
            SUM(CASE WHEN v.tp_secao_mrv = 1 THEN 1 ELSE 0 END) as total_mrv,
            SUM(CASE WHEN v.tp_secao_mrv = 1 AND v.status_vaga <> 'DISPONIVEL' THEN 1 ELSE 0 END) as mrv_preenchidas
        FROM {$tbl_locais} l
        JOIN {$tbl_tipos_locais} tl ON tl.id_tipo_local = l.id_tipo_local
        JOIN {$tbl_vagas} v ON v.id_local = l.id_local AND v.id_pleito = %d
        WHERE l.status_local = 1 AND l.id_zona = %d
        GROUP BY l.id_local
        ORDER BY l.nom_local ASC
    ", $pleito_ativo->id_pleito, $id_zona);

    $results = $wpdb->get_results($sql);

    foreach($results as $res) {
        $stats_zona['total'] += $res->total_vagas;
        $stats_zona['preenchidas'] += $res->preenchidas;

        if ($apenas_pendentes && $res->total_vagas == $res->preenchidas) {
            continue;
        }
        $locais[] = $res;
    }
}
?>

<style>
    :root { --ze-primary: #2563eb; --ze-success: #10b981; --ze-warning: #f59e0b; --ze-danger: #ef4444; --ze-dark: #1e293b; }
    
    .ze-container { max-width: 1100px; margin: 0; }
    
    /* Título Negritado */
    .ze-title { font-weight: 800 !important; font-size: 28px !important; color: var(--ze-dark); margin-bottom: 20px !important; display: block; }

    /* Dashboard Alinhado */
    .zl-dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 25px; max-width: 1100px; }
    .zl-stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .zl-stat-card .label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block; }
    .zl-stat-card .number { font-size: 26px; font-weight: 800; color: var(--ze-dark); }
    
    /* Header Card Alinhado */
    .zl-header-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); max-width: 1100px; }
    
    /* Filtro Todos / Pendente Estilizado */
    .ze-filter-toggle { display: flex; align-items: center; background: #e2e8f0; padding: 4px; border-radius: 8px; }
    .ze-filter-toggle a { padding: 8px 18px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 700; color: #475569; transition: all 0.2s ease; }
    .ze-filter-toggle a:hover { color: var(--ze-primary); }
    .ze-filter-toggle a.active { background: var(--ze-primary); color: #fff; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3); }
    
    /* Pleito Ativo 2026 Destaque */
    .zl-pleito-badge { background: var(--ze-dark); color: #fff; padding: 8px 16px; border-radius: 8px; font-weight: 800; font-size: 14px; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .zl-pleito-badge .dashicons { font-size: 18px; }

    
    .zl-local-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease; border-left: 6px solid #e5e7eb; }
    .zl-local-card:hover { transform: translateX(5px); border-color: var(--ze-primary); }
    .zl-local-card.completed { border-left-color: var(--ze-success); background: #f0fdf4; }
    .zl-local-card.partial { border-left-color: var(--ze-primary); }
    .zl-local-card.empty { border-left-color: var(--ze-danger); }

    .zl-badge { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 11px; border: 1px solid rgba(0,0,0,0.05); }
    .badge-mrv { background: #ecfdf5; color: #065f46; }
    .badge-comum { background: #f8fafc; color: #475569; }
    
    
    .zl-progress-wrapper { width: 140px; }
    .zl-progress-bg { background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 5px; }
    .zl-progress-bar { height: 100%; transition: width 0.5s ease; }
</style>

<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <h1 class="ze-page-title">Preencher Vagas Pleitos</h1>
    
    <div class="zl-pleito-badge">
                <span class="dashicons dashicons-calendar-alt"></span> 
                <?php echo esc_html( $pleito_ativo->descricao.' — '.$pleito_ativo->ano ); ?>
    </div>
    
   <div class="zl-header-card">
        <form method="get" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <input type="hidden" name="page" value="ze-legal-preencher-vagas-consulta">
            <div style="display: flex; gap: 20px; align-items: center;">
                <select name="id_zona" onchange="this.form.submit()" style="border-radius: 8px; min-width: 300px; height: 42px; font-weight: 600; border-color: #cbd5e1;">
                    <option value="">— Selecionar Zona Eleitoral —</option>
                    <?php foreach ( $zonas as $z ) : ?>
                        <option value="<?php echo intval($z->id_zona); ?>" <?php selected($id_zona,$z->id_zona); ?>>
                            <?php echo esc_html($z->descricao ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($id_zona): ?>
                <div class="ze-filter-toggle">
                    <a href="<?php echo add_query_arg('pendentes', '0'); ?>" class="<?php echo !$apenas_pendentes ? 'active' : ''; ?>">Todos</a>
                    <a href="<?php echo add_query_arg('pendentes', '1'); ?>" class="<?php echo $apenas_pendentes ? 'active' : ''; ?>">Pendentes</a>
                </div>
                <?php endif; ?>
            </div>

        </form>
    </div>

    <?php if ( $id_zona ) : 
        $perc_zona = ($stats_zona['total'] > 0) ? round(($stats_zona['preenchidas'] / $stats_zona['total']) * 100) : 0;
    ?>
        <div class="zl-dashboard">
            <div class="zl-stat-card">
                <span class="label">Total de Vagas na Zona</span>
                <div class="number"><?php echo $stats_zona['total']; ?></div>
            </div>
            <div class="zl-stat-card">
                <span class="label">Vagas Preenchidas</span>
                <div class="number" style="color: var(--ze-success);"><?php echo $stats_zona['preenchidas']; ?></div>
            </div>
            <div class="zl-stat-card">
                <span class="label">Progresso Geral</span>
                <div class="number" style="color: var(--ze-primary);"><?php echo $perc_zona; ?>%</div>
                <div class="zl-progress-bg"><div class="zl-progress-bar" style="width:<?php echo $perc_zona; ?>%; background: var(--ze-primary);"></div></div>
            </div>
        </div>

        <div style="max-width: 1100px;">
            <?php if (empty($locais)): ?>
                <div style="padding: 40px; text-align: center; background: #fff; border-radius: 12px; color: #64748b;">
                    Nenhum local pendente nesta zona. Tudo em dia! 🎉
                </div>
            <?php endif; ?>

            <?php foreach ( $locais as $l ) :
                $perc = round(($l->preenchidas / $l->total_vagas) * 100);
                $class = 'partial';
                if ($perc == 100) $class = 'completed';
                if ($perc == 0) $class = 'empty';

                $url = add_query_arg(['page' => 'ze-legal-preencher-vagas-local', 'id_local' => $l->id_local, 'id_zona' => $id_zona], admin_url('admin.php'));
            ?>
            <div class="zl-local-card <?php echo $class; ?>">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="text-align: center; min-width: 50px;">
                        <span style="font-size: 10px; font-weight: 800; color: #94a3b8; display: block;">LOCAL</span>
                        <span style="font-size: 18px; font-weight: 900; color: #1e293b;"><?php echo esc_html($l->num_local); ?></span>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 15px; color: #111827;"><?php echo esc_html($l->nom_local); ?></div>
                        <div style="font-size: 12px; color: #64748b;"><?php echo esc_html($l->ds_tipo_local); ?></div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 30px;">
                    <div style="display: flex; gap: 10px;">
                        <span class="zl-badge badge-mrv">MRV: <?php echo $l->mrv_preenchidas; ?>/<?php echo $l->total_mrv; ?></span>
                        <span class="zl-badge badge-comum">Geral: <?php echo $l->preenchidas; ?>/<?php echo $l->total_vagas; ?></span>
                    </div>

                    <div class="zl-progress-wrapper">
                        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: 700;">
                            <span>Progresso</span>
                            <span><?php echo $perc; ?>%</span>
                        </div>
                        <div class="zl-progress-bg">
                            <div class="zl-progress-bar" style="width: <?php echo $perc; ?>%; background: <?php echo ($perc == 100) ? 'var(--ze-success)' : ($perc == 0 ? 'var(--ze-danger)' : 'var(--ze-primary)'); ?>;"></div>
                        </div>
                    </div>

                    <a class="button zl-btn-manage" href="<?php echo esc_url($url); ?>">Preencher</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 2px dashed #d1d5db;">
             <span class="dashicons dashicons-location-alt" style="font-size: 48px; width: 48px; height: 48px; color: #9ca3af;"></span>
             <h3 style="color: #4b5563; margin-top: 15px;">Aguardando seleção de Zona</h3>
             <p style="color: #6b7280;">Escolha uma zona eleitoral para iniciar o monitoramento.</p>
        </div>
    <?php endif; ?>
</div>