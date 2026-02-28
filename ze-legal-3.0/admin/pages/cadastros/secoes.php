<?php
if ( ! defined('ABSPATH') ) exit;

// Segurança: Verifica permissão
if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
    wp_die( 'Acesso não autorizado.' );
}


global $wpdb;
$prefix           = $wpdb->prefix . 'ze_';
$table_secoes     = "{$prefix}tb_secoes";
$table_locais     = "{$prefix}tb_locais";
$table_zonas      = "{$prefix}tb_zonas";
$table_municipios = "{$prefix}tb_municipios";
$table_tipos_locais = "{$prefix}tb_tipos_locais";

// O SLUG DA PÁGINA deve ser exatamente o que você registrou no add_submenu_page
$slug_pagina = 'ze-legal-secoes'; 

$mensagem = '';

// ========= FILTROS =========
$zona_filtro   = isset( $_GET['id_zona_filtro'] )   ? intval( $_GET['id_zona_filtro'] ) : 0;
$local_filtro  = isset( $_GET['id_local_filtro'] )  ? intval( $_GET['id_local_filtro'] ) : 0;
$busca_secao   = isset( $_GET['busca_secao'] )      ? sanitize_text_field( $_GET['busca_secao'] ) : '';

if ( isset( $_GET['msg'] ) ) {
    switch ( $_GET['msg'] ) {
        case 'secao_cadastrada': $mensagem = 'Seção cadastrada com sucesso.'; break;
        case 'secao_atualizada': $mensagem = 'Seção atualizada com sucesso.'; break;
        case 'secao_excluida': $mensagem = 'Seção excluída com sucesso.'; break;
    }
}

// ========= LISTAS AUXILIARES =========
$zonas = $wpdb->get_results( "SELECT * FROM {$table_zonas} ORDER BY num_zona ASC" );

$sql_locais_filtro = "SELECT l.id_local, l.nom_local, l.num_local, z.num_zona, m.nom_municipio 
                      FROM {$table_locais} l JOIN {$table_zonas} z ON z.id_zona = l.id_zona 
                      JOIN {$table_tipos_locais} tl ON tl.id_tipo_local = l.id_tipo_local
                      JOIN {$table_municipios} m ON m.id_municipio = l.id_municipio
                      WHERE l.status_local = 1 and tl.ds_tipo_local = 'Local de Votação'";
if ( $zona_filtro ) {
    $sql_locais_filtro .= $wpdb->prepare(" AND l.id_zona = %d", $zona_filtro);
}
$locais_filtro = $wpdb->get_results( $sql_locais_filtro . " ORDER BY z.num_zona ASC, l.nom_local ASC" );

$local_filtro_obj = null;
if ( $local_filtro ) {
    $local_filtro_obj = $wpdb->get_row($wpdb->prepare("SELECT l.*, z.num_zona, m.nom_municipio FROM {$table_locais} l JOIN {$table_zonas} z ON z.id_zona = l.id_zona LEFT JOIN {$table_municipios} m ON m.id_municipio = l.id_municipio WHERE l.id_local = %d", $local_filtro));
}

// ========= PROCESSAMENTO (SALVAR) =========
if ( isset( $_POST['ze_secao_nonce'] ) && wp_verify_nonce( $_POST['ze_secao_nonce'], 'ze_salvar_secao' ) ) {
    $id_secao = intval( $_POST['id_secao'] ?? 0 );
    $id_local = intval( $_POST['id_local'] ?? 0 );
    $num_secao = substr( sanitize_text_field( $_POST['num_secao'] ?? '' ), 0, 4 );

    $dados = [ 'id_local' => $id_local, 'num_secao' => $num_secao ];
    if ( $id_secao > 0 ) {
        $wpdb->update($table_secoes, $dados, [ 'id_secao' => $id_secao ]);
        $msg = 'secao_atualizada';
        $next = '';
    } else {
        $wpdb->insert($table_secoes, $dados);
        $msg = 'secao_cadastrada';
        $next = str_pad( intval($num_secao) + 1, 4, '0', STR_PAD_LEFT );
    }

    // Redirecionamento forçando o parâmetro 'page' para evitar erro de permissão
    $url = admin_url('admin.php');
    $url = add_query_arg(['page' => $slug_pagina, 'msg' => $msg, 'id_zona_filtro' => $zona_filtro, 'id_local_filtro' => $id_local, 'last_num' => $next], $url);
    wp_safe_redirect($url);
    exit;
}

// ========= PROCESSAMENTO (EXCLUIR) =========
if ( isset( $_GET['action'] ) && $_GET['action'] === 'excluir' && wp_verify_nonce( $_GET['_wpnonce'], 'ze_excluir_secao' ) ) {
    $wpdb->delete($table_secoes, [ 'id_secao' => intval($_GET['id_secao']) ]);
    
    $url = admin_url('admin.php');
    $url = add_query_arg(['page' => $slug_pagina, 'msg' => 'secao_excluida', 'id_zona_filtro' => $zona_filtro, 'id_local_filtro' => $local_filtro], $url);
    wp_safe_redirect($url);
    exit;
}

$edit_secao = null;
if ( isset( $_GET['editar'] ) ) {
    $edit_secao = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_secoes} WHERE id_secao = %d", $_GET['editar']));
}

$valor_campo_secao = $edit_secao ? $edit_secao->num_secao : ($_GET['last_num'] ?? '');

// ========= LISTA =========
$sql_lista = "SELECT s.*, l.nom_local, l.num_local, z.num_zona, z.descricao, m.nom_municipio FROM {$table_secoes} s JOIN {$table_locais} l ON l.id_local = s.id_local JOIN {$table_zonas} z ON z.id_zona = l.id_zona LEFT JOIN {$table_municipios} m ON m.id_municipio = l.id_municipio WHERE 1=1";
if($zona_filtro) $sql_lista .= $wpdb->prepare(" AND l.id_zona = %d", $zona_filtro);
if($local_filtro) $sql_lista .= $wpdb->prepare(" AND s.id_local = %d", $local_filtro);
if($busca_secao) $sql_lista .= $wpdb->prepare(" AND s.num_secao LIKE %s", '%'.$wpdb->esc_like($busca_secao).'%');
$secoes = $wpdb->get_results($sql_lista . " ORDER BY l.nom_local ASC, s.num_secao ASC");
?>

<style>
    :root { --primary: #2271b1; --bg: #f0f2f5; --text: #1e293b; --text-sub: #64748b; --border: #e2e8f0; }
    .ze-premium-wrap { max-width: 1100px; margin: 20px auto; padding: 0 20px; font-family: 'Segoe UI', system-ui, sans-serif; }
    .ze-header { margin-bottom: 25px; }
    .ze-header h1 { font-size: 26px; font-weight: 800; color: var(--text); margin:0; }
    .ze-header p { color: var(--text-sub); margin: 5px 0 0 0; }
    .ze-card { background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border); }
    .ze-card h2 { font-size: 18px; color: var(--text); margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .ze-grid-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; align-items: end; }
    .ze-field { display: flex; flex-direction: column; gap: 5px; }
    .ze-field label { font-size: 11px; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; }
    .ze-field select, .ze-field input { height: 40px; border: 1.5px solid var(--border); border-radius: 8px; padding: 0 12px; font-size: 14px; background: #fcfcfd; }
    .ze-field input:focus { border-color: var(--primary); outline: none; background: #fff; }
    .btn-ze-outline { display: inline-flex; align-items: center; justify-content: center; padding: 0 20px; height: 38px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; text-decoration: none; border: 1.5px solid var(--primary); color: var(--primary); background: transparent; }
    .btn-ze-outline:hover { background: var(--primary); color: #fff; }
    .btn-ze-danger { border-color: #dc2626; color: #dc2626; }
    .btn-ze-danger:hover { background: #dc2626; color: #fff; }
    .ze-table { width: 100%; border-collapse: collapse; }
    .ze-table th { text-align: left; padding: 12px 15px; background: #f8fafc; color: var(--text-sub); font-size: 11px; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid var(--border); }
    .ze-table td { padding: 14px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .ze-table tr:hover { background: #f9fafb; }
    .ze-local-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
</style>

<div class="ze-premium-wrap">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>
    <header class="ze-header">
        <h1 class="ze-page-title"></span> Cadastro de Seções Fisicas</h1>
        <p>Cadastre e organize as seções eleitorais fisicas vinculadas aos locais de votação.</p>
    </header>

    <?php if ($mensagem): ?>
        <div class="notice notice-success is-dismissible" style="border-radius:8px; margin-bottom:20px;"><p><?php echo esc_html($mensagem); ?></p></div>
    <?php endif; ?>

    <div class="ze-card">
        <form method="get" action="<?php echo admin_url('admin.php'); ?>" class="ze-grid-filters">
            <input type="hidden" name="page" value="<?php echo esc_attr($slug_pagina); ?>">
            
            <div class="ze-field">
                <label>Filtrar Zona</label>
                <select name="id_zona_filtro" onchange="this.form.submit()">
                    <option value="">Todas as Zonas</option>
                    <?php foreach($zonas as $z): ?>
                        <option value="<?= $z->id_zona ?>" <?php selected($zona_filtro, $z->id_zona) ?>><?= "{$z->descricao}" ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ze-field">
                <label>Filtrar Local</label>
                <select name="id_local_filtro" onchange="this.form.submit()">
                    <option value="">Todos os Locais</option>
                    <?php foreach($locais_filtro as $l): ?>
                        <option value="<?= $l->id_local ?>" <?php selected($local_filtro, $l->id_local) ?>><?= "{$l->nom_local} - {$l->nom_municipio}" ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ze-field">
                <label>Buscar Seção</label>
                <input type="text" name="busca_secao" value="<?= esc_attr($busca_secao) ?>" placeholder="Ex: 0001">
            </div>
            <button type="submit" class="btn-ze-outline">Filtrar</button>
        </form>
    </div>

    <?php if ( $local_filtro_obj ) : ?>
        <div class="ze-card" id="form-secao">
            <h2><span class="dashicons dashicons-plus-alt"></span> <?= $edit_secao ? 'Editar Seção' : 'Nova Seção' ?></h2>
            <div class="ze-local-info">
                <span class="dashicons dashicons-location"></span>
                <span><strong>Local:</strong> <?= "{$local_filtro_obj->num_local} - {$local_filtro_obj->nom_local} ({$local_filtro_obj->nom_municipio})" ?></span>
            </div>
            <form method="post">
                <?php wp_nonce_field('ze_salvar_secao', 'ze_secao_nonce'); ?>
                <input type="hidden" name="id_secao" value="<?= $edit_secao ? $edit_secao->id_secao : 0 ?>">
                <input type="hidden" name="id_local" value="<?= $local_filtro_obj->id_local ?>">
                
                <div class="ze-grid-filters" style="grid-template-columns: 200px auto;">
                    <div class="ze-field">
                        <label>Número da Seção</label>
                        <input type="text" name="num_secao" value="<?= esc_attr($valor_campo_secao) ?>" maxlength="4" required style="text-align:center; font-weight:bold; font-size:18px;">
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn-ze-outline" style="background:var(--primary); color:#fff; border:none; height:42px;">Salvar Seção</button>
                        <?php if($edit_secao): ?>
                            <a href="<?= admin_url('admin.php?page='.$slug_pagina.'&id_local_filtro='.$local_filtro) ?>" class="btn-ze-outline btn-ze-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="ze-local-info" style="background:#fff7ed; border-color:#fed7aa; color:#9a3412;">
            <span class="dashicons dashicons-info"></span>
            <span>Selecione um <strong>Local</strong> nos filtros acima para habilitar o cadastro de novas seções.</span>
        </div>
    <?php endif; ?>

    <div class="ze-card">
        <h2><span class="dashicons dashicons-list-view"></span> Seções Listadas (<?= count($secoes) ?>)</h2>
        <div class="ze-table-container">
            <table class="ze-table">
                <thead>
                    <tr>
                        <th style="width:80px;">Seção</th>
                        <th>Local de Votação</th>
                        <th>Zona / Município</th>
                        <th style="text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($secoes): foreach($secoes as $s): ?>
                        <tr>
                            <td><strong><?= $s->num_secao ?></strong></td>
                            <td><?= "{$s->num_local} - {$s->nom_local}" ?></td>
                            <td style="font-size:12px; color:var(--text-sub);"><?= "{$s->descricao} | {$s->nom_municipio}" ?></td>
                            <td style="text-align:right;">
                                <a href="<?= admin_url("admin.php?page={$slug_pagina}&editar={$s->id_secao}&id_local_filtro={$s->id_local}") ?>" class="btn-ze-outline" style="height:30px; padding:0 10px;">Editar</a>
                                <a href="<?= wp_nonce_url(admin_url("admin.php?page={$slug_pagina}&action=excluir&id_secao={$s->id_secao}"), 'ze_excluir_secao') ?>" class="btn-ze-outline btn-ze-danger" style="height:30px; padding:0 10px;" onclick="return confirm('Excluir seção?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:40px;">Nenhuma seção encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>