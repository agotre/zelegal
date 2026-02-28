<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

// Definição de tabelas
$tbl_pleitos = "{$prefix}tb_pleitos";
$tbl_zonas   = "{$prefix}tb_zonas";
$tbl_locais  = "{$prefix}tb_locais";
$tbl_secoes  = "{$prefix}tb_secoes";
$tbl_funcoes = "{$prefix}tb_funcoes";
$tbl_vagas   = "{$prefix}tb_vagas_pleitos";

$pleito_ativo = $wpdb->get_row( "SELECT * FROM {$tbl_pleitos} WHERE status_pleito = 1 ORDER BY ano DESC, id_pleito DESC LIMIT 1" );
$id_zona = isset($_GET['id_zona']) ? intval($_GET['id_zona']) : 0;
$filtro_sem_vaga = isset($_GET['sem_vaga']) && $_GET['sem_vaga'] === '1' ? true : false;

$zonas = $wpdb->get_results( "SELECT * FROM {$tbl_zonas} ORDER BY num_zona ASC" );

/* Helper de contagem (Mantido) */
function zl_count_for_local( $wpdb, $tbl_vagas, $id_pleito, $id_local ) {
    $row = $wpdb->get_row( $wpdb->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN tp_secao_mrv = 1 THEN 1 ELSE 0 END),0) AS mrv,
            COALESCE(SUM(CASE WHEN tp_secao_mrv = 0 THEN 1 ELSE 0 END),0) AS comum,
            COALESCE(COUNT(*),0) AS total
        FROM {$tbl_vagas}
        WHERE id_pleito = %d AND id_local = %d
    ", $id_pleito, $id_local ) );
    return $row ? ['mrv' => (int)$row->mrv, 'comum' => (int)$row->comum, 'total' => (int)$row->total] : ['mrv'=>0,'comum'=>0,'total'=>0];
}

/* Consulta de Locais com Filtro de Pendência */
$locais = [];
if ( $id_zona ) {
    $id_p = $pleito_ativo ? $pleito_ativo->id_pleito : 0;
    
    $sql_locais = "SELECT l.* FROM {$tbl_locais} l WHERE l.status_local = 1 AND l.id_zona = %d";
    
    // Se o filtro "Sem Vaga" estiver ativo, filtramos apenas locais onde o COUNT de vagas no pleito é ZERO
    if ($filtro_sem_vaga) {
        $sql_locais .= " AND (SELECT COUNT(*) FROM {$tbl_vagas} v WHERE v.id_local = l.id_local AND v.id_pleito = $id_p) = 0";
    }
    
    $locais = $wpdb->get_results( $wpdb->prepare( $sql_locais . " ORDER BY l.nom_local ASC", $id_zona ) );
}

/* Totais por função (Mantido) */
$totais_funcoes = [];
if ( $pleito_ativo && $id_zona ) {
    $sql_funcoes = "
        SELECT f.id_funcao, f.num_funcao, f.nom_funcao,
               SUM(CASE WHEN v.tp_secao_mrv = 1 THEN 1 ELSE 0 END) AS mrv,
               SUM(CASE WHEN v.tp_secao_mrv = 0 THEN 1 ELSE 0 END) AS comum,
               COUNT(v.id_vaga_pleito) AS total
        FROM {$tbl_funcoes} f
        LEFT JOIN {$tbl_vagas} v ON v.id_funcao = f.id_funcao
        LEFT JOIN {$tbl_locais} l ON l.id_local = v.id_local
        WHERE v.id_pleito = %d AND l.id_zona = %d
        GROUP BY f.id_funcao, f.num_funcao, f.nom_funcao
        ORDER BY CAST(f.num_funcao AS UNSIGNED)
    ";
    $totais_funcoes = $wpdb->get_results( $wpdb->prepare( $sql_funcoes, $pleito_ativo->id_pleito, $id_zona ) );
}
?>

<style>
    :root {
        --primary: #2563eb; --success: #059669; --warning: #d97706; --danger: #dc2626;
        --slate-200: #e2e8f0; --slate-800: #1e293b;
    }
    .ze-premium-wrap { max-width: 1200px; margin: 20px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    
    /* Resumo Principal Separado */
    .ze-main-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
    .ze-main-card { padding: 25px; border-radius: 16px; color: #fff; position: relative; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
    .ze-main-card.mrv { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }
    .ze-main-card.comum { background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); }
    .ze-main-card .label { font-size: 12px; font-weight: 700; text-transform: uppercase; opacity: 0.9; letter-spacing: 1px; }
    .ze-main-card .value { font-size: 42px; font-weight: 900; margin-top: 5px; }

    /* Funções Secundárias */
    .ze-stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
    .ze-stat-mini { background: #fff; padding: 15px; border-radius: 12px; border: 1px solid var(--slate-200); }
    .ze-stat-mini strong { display: block; font-size: 18px; color: var(--slate-800); }
    .ze-stat-mini span { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; }

    /* Filtros */
    .ze-filter-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid var(--slate-200); margin-bottom: 30px; }
    .ze-filter-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
    
    /* Lista de Locais */
    .ze-local-card { 
        background: #fff; padding: 18px 25px; border-radius: 12px; border: 1px solid var(--slate-200); 
        margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;
    }
    .ze-local-card.pending { border-left: 5px solid var(--danger); background: #fffcfc; }
    .ze-badge { padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; }
    .badge-alert { background: #fee2e2; color: #991b1b; }
</style>

<div class="ze-premium-wrap">
    
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <h1 class="ze-page-title">Criar Vagas Pleitos</h1>
    
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <?php if($pleito_ativo): ?>
            <div style="background: #f1f5f9; padding: 8px 15px; border-radius: 8px; font-weight: 700; color: #475569;">
                <span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html($pleito_ativo->descricao.' — '.$pleito_ativo->ano); ?>
            </div>
        <?php endif; ?>
    </div>

    <form method="get" class="ze-filter-card">
        <input type="hidden" name="page" value="ze-legal-criar-vagas">
        <div class="ze-filter-row">
            <div style="flex: 1; min-width: 250px;">
                <label style="display:block; font-size:11px; font-weight:700; text-transform:uppercase; color:#64748b; margin-bottom:5px;">Zona Eleitoral</label>
                <select name="id_zona" style="width:100%; height:42px; border-radius:8px;">
                    <option value="">— Selecione uma zona —</option>
                    <?php foreach($zonas as $z): ?>
                        <option value="<?php echo (int)$z->id_zona; ?>" <?php selected($id_zona, $z->id_zona); ?>>
                            <?php echo esc_html($z->descricao); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="padding-bottom: 10px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:14px; color:var(--danger);">
                    <input type="checkbox" name="sem_vaga" value="1" <?php checked($filtro_sem_vaga); ?> onchange="this.form.submit()"> 
                    Somente locais sem vagas criadas
                </label>
            </div>

            <button type="submit" class="button button-primary" style="height:42px; padding:0 30px; border-radius:8px;">Filtrar</button>
        </div>
    </form>

    <?php if ( $id_zona ) : 
        $total_mrv = 0; $total_comum = 0;
        foreach($locais as $L) {
            $cnt = zl_count_for_local($wpdb, $tbl_vagas, $pleito_ativo ? $pleito_ativo->id_pleito : 0, $L->id_local);
            $total_mrv += $cnt['mrv']; $total_comum += $cnt['comum'];
        }
    ?>
        <div class="ze-main-summary">
            <div class="ze-main-card mrv">
                <div class="label">Total Vagas MRV</div>
                <div class="value"><?php echo $total_mrv; ?></div>
            </div>
            <div class="ze-main-card comum">
                <div class="label">Total Vagas Comuns</div>
                <div class="value"><?php echo $total_comum; ?></div>
            </div>
        </div>

        <div class="ze-stats-grid">
            <?php foreach( $totais_funcoes as $tf ) : ?>
                <div class="ze-stat-mini">
                    <span><?php echo esc_html($tf->nom_funcao); ?></span>
                    <strong><?php echo (int)$tf->total; ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 style="font-weight: 800; margin-bottom: 20px;">Locais de Votação (<?php echo count($locais); ?>)</h2>
        
        <div class="ze-list-container">
            <?php foreach ( $locais as $l ) :
                $num_secoes = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$tbl_secoes} WHERE id_local = %d", $l->id_local) );
                $cnt = zl_count_for_local($wpdb, $tbl_vagas, $pleito_ativo ? $pleito_ativo->id_pleito : 0, $l->id_local);
                $is_pending = ($cnt['total'] === 0 && $num_secoes > 0);
            ?>
                <div class="ze-local-card <?php echo $is_pending ? 'pending' : ''; ?>">
                    <div>
                        <div style="font-weight: 800; font-size: 16px;"><?php echo esc_html($l->num_local . ' — ' . $l->nom_local . ' — ' . $l->endereco); ?></div>
                        <div style="font-size: 12px; color:#64748b;">
                            <?php if($is_pending): ?>
                                <span style="color:var(--danger); font-weight:700;">⚠️ ATENÇÃO: Local com <?php echo $num_secoes; ?> seções e ZERO vagas.</span>
                            <?php else: ?>
                                <?php echo $num_secoes; ?> seções cadastradas
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; align-items:center;">
                        <?php if(!$is_pending): ?>
                            <span class="ze-badge" style="background:#f1f5f9;"><?php echo $cnt['mrv']; ?> MRV</span>
                            <span class="ze-badge" style="background:#f1f5f9;"><?php echo $cnt['comum']; ?> Comum</span>
                        <?php else: ?>
                            <span class="ze-badge badge-alert">PENDENTE</span>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url( add_query_arg(['page'=>'ze-legal-criar-vagas-local','id_local'=>$l->id_local,'id_zona'=>$id_zona], admin_url('admin.php')) ); ?>" 
                           class="button <?php echo $is_pending ? 'button-primary' : ''; ?>">Gerenciar</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>