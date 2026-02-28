<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('ze_cadastro_adm_zona')) {
    wp_die('Acesso não autorizado.');
}

global $wpdb;
$prefix = $wpdb->prefix;

$table_rotas          = $prefix . 'ze_tb_rotas';
$table_rotas_destinos = $prefix . 'ze_tb_rotas_destinos';
$table_locais         = $prefix . 'ze_tb_locais';
$table_municipio      = $prefix . 'ze_tb_municipios';
$table_pleitos        = $prefix . 'ze_tb_pleitos';
$table_enums          = $prefix . 'ze_tb_enums';
$vw_numero_secoes     = $prefix . 'ze_vw_numero_secoes';

$current_user_id = get_current_user_id();
$mensagem = '';

/* ==========================================================
   PROCESSAMENTO (AÇÕES)
========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['acao_criar_rota'])) {
        check_admin_referer('ze_rota_nova');
        $wpdb->insert($table_rotas, [
            'id_local'    => intval($_POST['id_local']),
            'id_pleito'   => intval($_POST['id_pleito']),
            'ds_rota'     => sanitize_text_field($_POST['ds_rota']),
            'tipo_rota'   => sanitize_text_field($_POST['tipo_rota']),
            'status_rota' => sanitize_text_field($_POST['status_rota']),
            'created_at'  => current_time('mysql'),
            'id_usuario_criacao' => $current_user_id
        ]);
        $mensagem = '<div class="updated"><p>Rota criada com sucesso!</p></div>';
    }

    if (isset($_POST['acao_add_destino'])) {
        check_admin_referer('ze_destino_add');
        $wpdb->insert($table_rotas_destinos, [
            'id_rota'               => intval($_POST['id_rota']),
            'sq_rota'               => intval($_POST['sq_rota']),
            'id_local_destino'      => intval($_POST['id_local_destino']),
            'qt_urnas_contingencia' => intval($_POST['qt_urnas']),
            'observacao'            => sanitize_textarea_field($_POST['observacao'])
        ]);
    }

    if (isset($_POST['acao_excluir_rota'])) {
        check_admin_referer('ze_rota_delete');
        $id = intval($_POST['id_rota']);
        $wpdb->delete($table_rotas_destinos, ['id_rota' => $id]);
        $wpdb->delete($table_rotas, ['id_rota' => $id]);
        $mensagem = '<div class="error"><p>Rota removida.</p></div>';
    }
}

/* ==========================================================
   CONSULTAS
========================================================== */
$pleito_ativo = $wpdb->get_row("SELECT id_pleito, descricao FROM {$table_pleitos} WHERE status_pleito = 1 LIMIT 1");
$id_pleito_ativo = $pleito_ativo ? $pleito_ativo->id_pleito : 0;

$ocupados_base = $wpdb->get_col("SELECT id_local FROM {$table_rotas} WHERE id_pleito = $id_pleito_ativo");
$ocupados_dest = $wpdb->get_col("SELECT id_local_destino FROM {$table_rotas_destinos} d JOIN {$table_rotas} r ON d.id_rota = r.id_rota WHERE r.id_pleito = $id_pleito_ativo");
$todos_ocupados = array_unique(array_merge($ocupados_base, $ocupados_dest));
$filtro_sql = !empty($todos_ocupados) ? implode(',', array_map('intval', $todos_ocupados)) : '0';

$locais_base = $wpdb->get_results("SELECT id_local, nom_local FROM {$table_locais} WHERE flg_rota = 1 AND id_local NOT IN ($filtro_sql) ORDER BY nom_local ASC");
$locais_dest = $wpdb->get_results("SELECT l.id_local, l.nom_local, s.total_secoes, m.nom_municipio FROM {$table_locais} l JOIN {$vw_numero_secoes} s ON l.id_local = s.id_local 
                                   JOIN {$table_municipio} m ON m.id_municipio = l.id_municipio
                                   WHERE l.flg_rota = 0 AND l.id_local NOT IN ($filtro_sql) ORDER BY nom_local ASC");
$tipos_rota = $wpdb->get_results("SELECT ds_enum FROM {$table_enums} WHERE campo_alvo_enum = 'tipo_rota' AND status_enum = 1");

$rotas = $wpdb->get_results($wpdb->prepare("
    SELECT r.*, l.nom_local 
    FROM {$table_rotas} r 
    LEFT JOIN {$table_locais} l ON r.id_local = l.id_local 
    WHERE r.id_pleito = %d ORDER BY r.ds_rota", $id_pleito_ativo));
?>

<style>
    .ze-wrap { max-width: 1200px; margin: 20px auto; font-family: sans-serif; }
    .header-rota { background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #ccd0d4; margin-bottom: 30px; }
    .rota-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; margin-bottom: 20px; }
    .rota-header { background: #f6f7f7; padding: 15px 20px; border-bottom: 1px solid #c3c4c7; display: flex; justify-content: space-between; align-items: center; }
    .badge { background: #0073aa; color: #fff; padding: 4px 12px; border-radius: 15px; font-size: 11px; font-weight: bold; }
    .table-destinos { width: 100%; border-collapse: collapse; }
    .table-destinos th { text-align: left; padding: 10px; background: #f0f0f1; font-size: 11px; text-transform: uppercase; }
    .table-destinos td { padding: 10px; border-bottom: 1px solid #f0f0f1; }
    .grid-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: flex-end; }
    .resumo-urnas { background: #e7f3ff; padding: 2px 8px; border-radius: 4px; font-weight: bold; color: #005a87; margin-left: 10px; font-size: 12px; }
</style>

<div class="ze-wrap">
    
    <!-- BOTÃO PDF -->
    <div style="display:block; clear:both; margin: 20px 0; position:relative; z-index:9999;">
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('ze_gerar_rotas_pdf_nonce'); ?>
            <input type="hidden" name="action" value="ze_gerar_rotas_pdf">
            <button type="submit" class="button button-primary button-large">
                Imprimir Rotas (PDF)
            </button>
        </form>
    </div>
    
    <h1>Gerenciador de Itinerários Eleitorais</h1>
    <?php echo $mensagem; ?>
    
    
    <div class="header-rota">
        <h2 style="margin-top:0"><span class="dashicons dashicons-plus"></span> 1. Criar Nova de Rota</h2>
        <form method="post" class="grid-form">
            <?php wp_nonce_field('ze_rota_nova'); ?>
            <input type="hidden" name="id_pleito" value="<?php echo $id_pleito_ativo; ?>">
            <div><label>Descrição</label><input type="text" name="ds_rota" required style="width:100%"></div>
            <div>
                <label>Origem (Base)</label>
                <select name="id_local" required style="width:100%">
                    <option value="">Selecione...</option>
                    <?php foreach($locais_base as $l): ?>
                        <option value="<?php echo $l->id_local; ?>"><?php echo esc_html($l->nom_local); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Tipo</label>
                <select name="tipo_rota" required style="width:100%">
                    <?php foreach($tipos_rota as $t): ?>
                        <option value="<?php echo esc_attr($t->ds_enum); ?>"><?php echo esc_html($t->ds_enum); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="status_rota" value="Ativa">
            <button type="submit" name="acao_criar_rota" class="button button-primary">Criar Rota</button>
        </form>
    </div>

    <h2><span class="dashicons dashicons-list-view"></span> 2. Gerenciar Destinos por Rota</h2>

    <?php foreach ($rotas as $r): 
        // Busca itinerário incluindo total_secoes da view
        $itinerario = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, l.nom_local, s.total_secoes 
             FROM {$table_rotas_destinos} d 
             LEFT JOIN {$table_locais} l ON d.id_local_destino = l.id_local 
             LEFT JOIN {$vw_numero_secoes} s ON l.id_local = s.id_local
             WHERE d.id_rota = %d ORDER BY d.sq_rota ASC", $r->id_rota));

        // Cálculos de soma para o cabeçalho
        $soma_urnas_secao = array_sum(array_column($itinerario, 'total_secoes'));
        $soma_urnas_cont  = array_sum(array_column($itinerario, 'qt_urnas_contingencia'));
    ?>
        <div class="rota-card">
            <div class="rota-header">
                <div>
                    <span class="badge"><?php echo esc_html($r->tipo_rota); ?></span>
                    <strong style="font-size: 16px; margin-left: 10px;"><?php echo esc_html($r->ds_rota); ?></strong>
                    <span style="color: #646970; margin-left: 15px;">| Origem: <?php echo esc_html($r->nom_local); ?></span>
                    
                    <span class="resumo-urnas" title="Soma de todas as urnas das seções + contingência desta rota">
                        <span class="dashicons dashicons-archive" style="font-size:17px; vertical-align:middle;"></span>
                        Total de Urnas: <?php echo ($soma_urnas_secao + $soma_urnas_cont); ?> 
                        <small>(<?php echo $soma_urnas_secao; ?> Seção + <?php echo $soma_urnas_cont; ?> contingência.)</small>
                    </span>
                </div>
                <form method="post" onsubmit="return confirm('Excluir rota?');">
                    <?php wp_nonce_field('ze_rota_delete'); ?>
                    <input type="hidden" name="id_rota" value="<?php echo $r->id_rota; ?>">
                    <button type="submit" name="acao_excluir_rota" class="button button-link-delete">Excluir</button>
                </form>
            </div>

            <div style="padding:20px">
                <table class="table-destinos">
                    <thead>
                        <tr>
                            <th width="50">Seq.</th>
                            <th>Local de Destino</th>
                            <th width="100">Qt Urnas contingência</th>
                            <th width="100">Qt Urnas Local</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$itinerario): ?>
                            <tr><td colspan="5" style="text-align:center;">Nenhum destino.</td></tr>
                        <?php else: foreach ($itinerario as $item): ?>
                            <tr>
                                <td><strong><?php echo $item->sq_rota; ?>º</strong></td>
                                <td><?php echo esc_html($item->nom_local); ?></td>
                                <td><?php echo $item->qt_urnas_contingencia; ?></td>
                                <td style="font-weight:bold; color:#2c3338;"><?php echo ($item->total_secoes ?? 0); ?></td>
                                <td><small><?php echo esc_html($item->observacao); ?></small></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>

                <div class="form-add-dest" style="background:#f9f9f9; padding:15px; margin-top:15px; border-radius:6px;">
                    <form method="post" class="grid-form">
                        <?php wp_nonce_field('ze_destino_add'); ?>
                        <input type="hidden" name="id_rota" value="<?php echo $r->id_rota; ?>">
                        <div style="flex:0 0 60px;"><label>Seq.</label><input type="number" name="sq_rota" value="<?php echo count($itinerario) + 1; ?>" style="width:100%"></div>
                        <div style="flex:2">
                            <label>Local Destino</label>
                            <select name="id_local_destino" required style="width:100%">
                                <option value="">Selecione...</option>
                                <?php foreach($locais_dest as $ld): ?>
                                    <option value="<?php echo $ld->id_local; ?>"><?php echo esc_html($ld->nom_local) . " - " . $ld->nom_municipio . " (" . $ld->total_secoes . " Urnas)"; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:0 0 80px;"><label>Conting.</label><input type="number" name="qt_urnas" value="0" style="width:100%"></div>
                        <div style="flex:1"><label>Obs.</label><input type="text" name="observacao" style="width:100%"></div>
                        <button type="submit" name="acao_add_destino" class="button">Adicionar</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>