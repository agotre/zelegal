<?php
if (!defined('ABSPATH')) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix;
$table_locais     = $prefix . 'ze_tb_locais';
$table_municipios = $prefix . 'ze_tb_municipios';
$table_zonas      = $prefix . 'ze_tb_zonas';
$table_tipos      = $prefix . 'ze_tb_tipos_locais';

$mensagem = '';
$edit_data = null;

// --- 1. PROCESSAMENTO (SALVAR/ATUALIZAR) ---
if (isset($_POST['zelegal_salvar_local']) && check_admin_referer('zelegal_cadastro_local')) {
    $id_local = isset($_POST['id_local']) ? intval($_POST['id_local']) : 0;
    
    $dados = [
        'id_municipio'      => intval($_POST['id_municipio']),
        'id_zona'           => intval($_POST['id_zona']),
        'id_tipo_local'     => intval($_POST['id_tipo_local']),
        'nom_local'         => sanitize_text_field($_POST['nom_local']),
        'num_local'         => sanitize_text_field($_POST['num_local']),
        'endereco'          => sanitize_text_field($_POST['endereco']),
        'num_latitude'      => !empty($_POST['num_latitude']) ? floatval($_POST['num_latitude']) : null,
        'num_longitude'     => !empty($_POST['num_longitude']) ? floatval($_POST['num_longitude']) : null,
        'code_plus'         => sanitize_text_field($_POST['code_plus']),
        'contato_1_local'   => ze_telefone_mascara( sanitize_text_field($_POST['contato_1_local']) ),
        'contato_2_local'   => ze_telefone_mascara( sanitize_text_field($_POST['contato_2_local']) ),
        'email_local'       => sanitize_email($_POST['email_local']),
        'flg_acessibilidade'=> isset($_POST['flg_acessibilidade']) ? 1 : 0,
        'flg_rota'          => isset($_POST['flg_rota']) ? 1 : 0,
        'flg_tarefa'        => isset($_POST['flg_tarefa']) ? 1 : 0,
        'flg_check_in'      => isset($_POST['flg_check_in']) ? 1 : 0,
        
        'observacao'        => sanitize_textarea_field($_POST['observacao']),
        'updated_at'        => current_time('mysql'),
    ];

    if ($id_local > 0) {
        $wpdb->update($table_locais, $dados, ['id_local' => $id_local]);
        $mensagem = 'Local atualizado com sucesso!';
    } else {
        $dados['created_at'] = current_time('mysql');
        $wpdb->insert($table_locais, $dados);
        $mensagem = 'Local cadastrado com sucesso!';
    }
}

// --- 2. CARREGAR DADOS ---
$municipios = $wpdb->get_results("SELECT id_municipio, nom_municipio FROM $table_municipios ORDER BY nom_municipio");
$zonas      = $wpdb->get_results("SELECT id_zona, descricao FROM $table_zonas ORDER BY descricao");
$tipos      = $wpdb->get_results("SELECT id_tipo_local, ds_tipo_local FROM $table_tipos ORDER BY ds_tipo_local");

// NOVA QUERY: Contagem de locais por tipo (somente os criados/existentes)
$stats_tipos = $wpdb->get_results("
    SELECT t.ds_tipo_local, COUNT(l.id_local) as total 
    FROM $table_tipos t
    INNER JOIN $table_locais l ON t.id_tipo_local = l.id_tipo_local 
    GROUP BY t.id_tipo_local 
    ORDER BY total DESC
");

// --- 3. FILTROS E QUERY ---
$f_nome = isset($_GET['f_nome']) ? sanitize_text_field($_GET['f_nome']) : '';
$f_tipo = isset($_GET['f_tipo']) ? intval($_GET['f_tipo']) : '';
$f_mun  = isset($_GET['f_municipio']) ? intval($_GET['f_municipio']) : '';

$sql = "SELECT l.*, m.nom_municipio, z.descricao as nom_zona, t.ds_tipo_local 
        FROM $table_locais l
        LEFT JOIN $table_municipios m ON l.id_municipio = m.id_municipio
        LEFT JOIN $table_zonas z      ON l.id_zona = z.id_zona
        LEFT JOIN $table_tipos t      ON l.id_tipo_local = t.id_tipo_local
        WHERE 1=1";

if ($f_nome) $sql .= $wpdb->prepare(" AND l.nom_local LIKE %s", '%'.$f_nome.'%');
if ($f_tipo) $sql .= $wpdb->prepare(" AND l.id_tipo_local = %d", $f_tipo);
if ($f_mun)  $sql .= $wpdb->prepare(" AND l.id_municipio = %d", $f_mun);

$sql .= " ORDER BY l.nom_local";
$locais = $wpdb->get_results($sql);
?>

<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>
    
    <header class="ze-header-main">
        <h1 class="ze-page-title"></span> Cadastro de Locias</h1>
        <p>Compreende aos locais de votação, cartórios, pontos de apoio, e equipes.</p>
    </header>
    
    </header>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <?php if ($stats_tipos): foreach ($stats_tipos as $stat): ?>
            <div class="ze-card" style="margin-bottom: 0; padding: 15px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <span style="display: block; font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">
                        <?= esc_html($stat->ds_tipo_local); ?>
                    </span>
                    <span style="font-size: 24px; font-weight: 700; color: #1e293b;">
                        <?= $stat->total; ?>
                    </span>
                </div>
                <div style="background: #eff6ff; color: #3b82f6; padding: 10px; border-radius: 8px;">
                    <span class="dashicons dashicons-location-alt" style="font-size: 20px; width: 20px; height: 20px;"></span>
                </div>
            </div>
        <?php endforeach; else: ?>
             <div class="ze-card" style="margin-bottom: 0; padding: 15px; color: #64748b; font-style: italic; font-size: 13px;">
                Nenhum local cadastrado até o momento.
            </div>
        <?php endif; ?>
    </div>

    <?php if ($mensagem): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensagem); ?></p>
        </div>
    <?php endif; ?>

    <!-- ================= CARD FORM ================= -->
    <div class="ze-card">
        <h2><span class="dashicons dashicons-location-alt"></span> Detalhes do Local</h2>

        <form method="post" id="zelegal-form-local">
            <?php wp_nonce_field('zelegal_cadastro_local'); ?>
            <input type="hidden" name="id_local" id="id_local">

            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Zona Eleitoral</label>
                    <select name="id_zona" required>
                        <option value="">Selecione a Zona...</option>
                        <?php foreach($zonas as $z) echo "<option value='{$z->id_zona}'>{$z->descricao}</option>"; ?>
                    </select>
                </div>

                <div class="ze-form-group">
                    <label>Município</label>
                    <select name="id_municipio" required>
                        <option value="">Selecione o Município...</option>
                        <?php foreach($municipios as $m) echo "<option value='{$m->id_municipio}'>{$m->nom_municipio}</option>"; ?>
                    </select>
                </div>

                <div class="ze-form-group">
                    <label>Tipo de Local</label>
                    <select name="id_tipo_local" required>
                        <option value="">Selecione o Tipo...</option>
                        <?php foreach($tipos as $t) echo "<option value='{$t->id_tipo_local}'>{$t->ds_tipo_local}</option>"; ?>
                    </select>
                </div>
            </div>

            <div class="ze-form-grid">
                <div class="ze-form-group" style="grid-column: span 3">
                    <label>Nome do Local</label>
                    <input type="text" name="nom_local" required placeholder="Ex: Escola Municipal Marechal Rondon">
                </div>

                <div class="ze-form-group">
                    <label>Número (Cód)</label>
                    <input type="text" name="num_local" maxlength="4" placeholder="0000">
                </div>
            </div>

            <div class="ze-form-grid">
                <div class="ze-form-group" style="grid-column: span 3">
                    <label>Endereço Completo</label>
                    <input type="text" name="endereco" placeholder="Rua, Número, Bairro...">
                </div>
            </div>

            <div class="ze-form-grid">
                <div class="ze-form-group" style="grid-column: span 5">
                    <label>E-mail do Local</label>
                    <input type="email" name="email_local" placeholder="contato@local.com">
                </div>
            </div>

            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Telefone 1</label>
                    <input type="text" 
                           name="contato_1_local" 
                           class="zelegal-phone">
                </div>
            
                <div class="ze-form-group">
                    <label>Telefone 2</label>
                    <input type="text" 
                           name="contato_2_local" 
                           class="zelegal-phone">
                </div>
            </div>

            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Longitude</label>
                    <input type="text" name="num_longitude" placeholder="-63.899...">
                </div>

                <div class="ze-form-group">
                    <label>Latitude</label>
                    <input type="text" name="num_latitude" placeholder="-8.761...">
                </div>

                <div class="ze-form-group">
                    <label>Plus Code</label>
                    <input type="text" name="code_plus" placeholder="87G8H7M8+2X">
                </div>
            </div>

            <!-- CONFIGURAÇÕES -->
        <div class="ze-form-grid" style="margin-top:15px; background:#f9f9f9; padding:15px; border:1px solid #eee;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; vertical-align: top; padding-right: 20px;">
                        <label><strong>Configurações:</strong></label><br><br>
                        <label><input type="checkbox" name="flg_acessibilidade" value="1" <?php checked($edit_data ? $edit_data->flg_acessibilidade : 1, 1); ?>> Acessibilidade</label><br>
                        <label><input type="checkbox" name="flg_rota" value="1" <?php checked($edit_data ? $edit_data->flg_rota : 0, 1); ?>> Rota Ativa</label><br>
                        <label><input type="checkbox" name="flg_tarefa" value="1" <?php checked($edit_data ? $edit_data->flg_tarefa : 0, 1); ?>> Permite Tarefa</label><br>
                        <label><input type="checkbox" name="flg_check_in" value="1" <?php checked($edit_data ? $edit_data->flg_check_in : 0, 1); ?>> Exige Check-in</label>
                    </td>
        
                    <td style="width: 50%; vertical-align: top;">
                        <label><strong>Observações Internas:</strong></label><br><br>
                        <textarea name="observacao" rows="5" style="width: 100%; box-sizing: border-box;"></textarea>
                    </td>
                </tr>
            </table>
        </div>

            <div class="ze-form-footer">
                <button type="submit" name="zelegal_salvar_local" class="ze-btn-submit">
                    Salvar Dados do Local
                </button>

                <button type="reset" class="btn-ze-outline"
                    onclick="window.location.href='?page=<?php echo $_GET['page']; ?>'">
                    Limpar / Novo
                </button>
            </div>

        </form>
    </div>

    <!-- ================= CARD LISTAGEM ================= -->
    <div class="ze-card">
        <header style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">
                <span class="dashicons dashicons-list-view"></span> Locais Registrados
            </h2>

            <form method="get" style="display:flex; gap:10px; flex-wrap: wrap;">
                <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">

                <input type="text" name="f_nome" placeholder="Filtrar por nome..."
                    value="<?php echo esc_attr($f_nome); ?>" style="width:160px;">

                <select name="f_municipio" style="width:140px;">
                    <option value="">Município...</option>
                    <?php foreach($municipios as $m)
                        echo "<option value='{$m->id_municipio}' "
                        . selected($f_mun, $m->id_municipio, false)
                        . ">{$m->nom_municipio}</option>";
                    ?>
                </select>

                <select name="f_tipo" style="width:140px;">
                    <option value="">Tipo...</option>
                    <?php foreach($tipos as $t)
                        echo "<option value='{$t->id_tipo_local}' "
                        . selected($f_tipo, $t->id_tipo_local, false)
                        . ">{$t->ds_tipo_local}</option>";
                    ?>
                </select>

                <button type="submit" class="btn-ze-outline">Filtrar</button>
            </form>
        </header>

        <div class="ze-table-container">
            <table class="ze-table">
                <thead>
                    <tr>
                        <th style="width:40px;">ID</th>
                        <th>Nome do Local</th>
                        <th>Zona</th>
                        <th>Município</th>
                        <th>Tipo</th>
                        <th style="text-align:right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($locais): foreach ($locais as $l): ?>
                    <tr>
                        <td><small>#<?= $l->id_local; ?></small></td>

                        <td>
                            <strong><?= esc_html($l->nom_local); ?></strong>
                            <?php if($l->num_local): ?>
                                <br>
                                <span style="font-size:11px; color:#64748b;">
                                    Código: <?= esc_html($l->num_local); ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td><?= esc_html($l->nom_zona); ?></td>
                        <td><?= esc_html($l->nom_municipio); ?></td>
                        <td><span class="ze-badge"><?= esc_html($l->ds_tipo_local); ?></span></td>

                        <td style="text-align:right;">
                            <button class="btn-ze-outline zelegal-editar-local"
                                data-local='<?= esc_attr(json_encode($l)); ?>'>
                                Editar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:40px;">
                                Nenhum local encontrado com estes filtros.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<script>
jQuery(document).ready(function($) {
    $('.zelegal-editar-local').on('click', function() {
        const data = $(this).data('local');
        const form = $('#zelegal-form-local');
        
        // Preenche campos básicos
        form.find('#id_local').val(data.id_local);
        form.find('[name="nom_local"]').val(data.nom_local);
        form.find('[name="num_local"]').val(data.num_local);
        form.find('[name="endereco"]').val(data.endereco);
        form.find('[name="email_local"]').val(data.email_local);
        
        // CORREÇÃO: Removida a função ze_telefone_mascara que causava erro no JS
        form.find('[name="contato_1_local"]').val(data.contato_1_local);
        form.find('[name="contato_2_local"]').val(data.contato_2_local);
        
        form.find('[name="num_longitude"]').val(data.num_longitude);
        form.find('[name="num_latitude"]').val(data.num_latitude);
        form.find('[name="code_plus"]').val(data.code_plus);
        form.find('[name="observacao"]').val(data.observacao);

        // Preenche selects
        form.find('[name="id_zona"]').val(data.id_zona);
        form.find('[name="id_municipio"]').val(data.id_municipio);
        form.find('[name="id_tipo_local"]').val(data.id_tipo_local);
        
        // Preenche checkboxes (converte para booleano)
        form.find('[name="flg_acessibilidade"]').prop('checked', parseInt(data.flg_acessibilidade) === 1);
        form.find('[name="flg_rota"]').prop('checked', parseInt(data.flg_rota) === 1);
        form.find('[name="flg_tarefa"]').prop('checked', parseInt(data.flg_tarefa) === 1);
        form.find('[name="flg_check_in"]').prop('checked', parseInt(data.flg_check_in) === 1);

        $('html, body').animate({ scrollTop: form.offset().top - 100 }, 400);
        $('.ze-card h2').first().html('<span class="dashicons dashicons-edit"></span> Editando Local: ' + data.nom_local);
    });
});
</script>

<style>
    /* Complementos de estilo para badges */
    .ze-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
</style>

<script>
document.querySelectorAll('.zelegal-phone').forEach(function(i){
    i.addEventListener('input', function(){
        let v = i.value.replace(/\D/g,'').slice(0,11);

        if(v.length >= 2){
            v = '(' + v.slice(0,2) + ') ' + v.slice(2);
        }

        if(v.length >= 10){
            v = v.slice(0,10) + '-' + v.slice(10);
        }

        i.value = v;
    });
});
</script>