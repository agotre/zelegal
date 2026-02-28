<?php
if (!defined('ABSPATH')) exit;

// Segurança: Verifica permissão
if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';
$tbl_veiculos = "{$prefix}tb_veiculos";
$tbl_colab    = "{$prefix}tb_colaboradores";

/* ==========================================================================
   1. PROCESSAMENTO DE AÇÕES (POST/GET)
   ========================================================================== */
if ( isset($_POST['acao']) && $_POST['acao'] === 'salvar_veiculo' ) {
    check_admin_referer('ze_veiculo_nonce');

    $wpdb->hide_errors();

    $placa   = strtoupper(sanitize_text_field($_POST['placa']));
    $id_edit = !empty($_POST['id_veiculo_edit']) ? intval($_POST['id_veiculo_edit']) : 0;

    // --- CHECAGEM DE PLACA DUPLICADA ---
    $existe_placa = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tbl_veiculos WHERE placa = %s AND id_veiculo != %d", 
        $placa, 
        $id_edit
    ));

    if ($existe_placa > 0) {
        wp_die(
            '<strong>Erro:</strong> Já existe um veículo cadastrado com a placa ' . esc_html($placa) . '.<br><br><a href="javascript:history.back()" class="ze-btn-secondary">Voltar e Corrigir</a>',
            'Placa Duplicada',
            ['response' => 409]
        );
    }

    $dados = [
        'placa'          => $placa,
        'marca'          => sanitize_text_field($_POST['marca']),
        'modelo'         => sanitize_text_field($_POST['modelo']),
        'cor'            => sanitize_text_field($_POST['cor']),
        'ds_veiculo'     => sanitize_text_field($_POST['ds_veiculo']),
        'ds_combustivel' => sanitize_text_field($_POST['ds_combustivel']),
        'status_veiculo' => 1,
    ];

    try {
        if ($id_edit > 0) {
            $wpdb->update($tbl_veiculos, $dados, ['id_veiculo' => $id_edit]);
            wp_redirect(admin_url("admin.php?page={$_GET['page']}&msg=updated"));
            exit;
        } else {
            $dados['created_at'] = current_time('mysql');
            $dados['id_usuario_criacao'] = get_current_user_id();
            $wpdb->insert($tbl_veiculos, $dados);
            wp_redirect(admin_url("admin.php?page={$_GET['page']}&vincular_id=" . $wpdb->insert_id));
            exit;
        }
    } catch (Exception $e) {
        wp_die('Erro ao salvar veículo: ' . esc_html($e->getMessage()));
    } finally {
        $wpdb->show_errors();
    }
}

// Ações de Vínculo e Exclusão
if (isset($_GET['confirmar_vinculo']) && isset($_GET['vincular_id'])) {
    $wpdb->update($tbl_veiculos, ['id_motorista' => intval($_GET['confirmar_vinculo'])], ['id_veiculo' => intval($_GET['vincular_id'])]);
    wp_redirect(admin_url("admin.php?page={$_GET['page']}&msg=assoc_ok")); exit;
}

if (isset($_GET['del'])) {
    $wpdb->delete($tbl_veiculos, ['id_veiculo' => intval($_GET['del'])]);
    wp_redirect(admin_url("admin.php?page={$_GET['page']}")); exit;
}

$edit_data = isset($_GET['edit']) ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl_veiculos WHERE id_veiculo = %d", $_GET['edit'])) : null;

// Lógica de Filtros e Listagem
$where_frota = " WHERE 1=1 ";
if (!empty($_GET['f_placa'])) $where_frota .= $wpdb->prepare(" AND v.placa LIKE %s ", '%' . $wpdb->esc_like(sanitize_text_field($_GET['f_placa'])) . '%');
if (!empty($_GET['f_marca'])) $where_frota .= $wpdb->prepare(" AND v.marca = %s ", sanitize_text_field($_GET['f_marca']));
if (!empty($_GET['f_modelo'])) $where_frota .= $wpdb->prepare(" AND v.modelo = %s ", sanitize_text_field($_GET['f_modelo']));

$veiculos = $wpdb->get_results("SELECT v.*, c.nom_eleitor, c.num_cpf, c.num_telefone_eleitor FROM $tbl_veiculos v LEFT JOIN $tbl_colab c ON c.id_colaborador = v.id_motorista $where_frota ORDER BY v.id_veiculo DESC");
$total_veiculos = count($veiculos);

function ze_opt($tb_alvo, $campo) {
    global $wpdb;
    $table = $wpdb->prefix . 'ze_tb_enums';
    return $wpdb->get_results($wpdb->prepare("SELECT ds_enum FROM {$table} WHERE tb_alvo_enum = %s AND campo_alvo_enum = %s AND status_enum = 1 ORDER BY num_orden_enum ASC", $tb_alvo, $campo));
}
?>

<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>
    
    <div class="ze-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 class="ze-page-title"></span> Cadastro de Veiculos</h1>
        </div>
        <div class="ze-card" style="padding: 15px 25px; margin: 0; text-align: center; min-width: 150px;">
            <div style="font-size: 11px; text-transform: uppercase; color: var(--ze-text-sub); font-weight: 700;">Veículos</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--ze-primary);"><?= $total_veiculos ?></div>
        </div>
    </div>

    <div class="ze-card">
        <div class="ze-section-title">
            <span class="dashicons dashicons-plus-alt"></span> <?= $edit_data ? 'Editar Veículo' : 'Cadastrar Novo Veículo' ?>
        </div>
        <form method="post">
            <?php wp_nonce_field('ze_veiculo_nonce'); ?>
            <input type="hidden" name="acao" value="salvar_veiculo">
            <input type="hidden" name="id_veiculo_edit" value="<?= $edit_data ? $edit_data->id_veiculo : '' ?>">
            
            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Placa</label>
                    <input type="text" name="placa" id="placa" required value="<?= $edit_data ? $edit_data->placa : '' ?>" placeholder="AAA-0000">
                </div>
                <div class="ze-form-group">
                    <label>Marca</label>
                    <select name="marca">
                        <?php foreach (ze_opt('tb_veiculos','marca') as $o): ?>
                            <option value="<?= esc_attr($o->ds_enum) ?>" <?= selected($edit_data->marca ?? '', $o->ds_enum); ?>><?= esc_html($o->ds_enum) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ze-form-group">
                    <label>Modelo</label>
                    <select name="modelo">
                        <?php foreach (ze_opt('tb_veiculos','modelo') as $o): ?>
                            <option value="<?= esc_attr($o->ds_enum) ?>" <?= selected($edit_data->modelo ?? '', $o->ds_enum); ?>><?= esc_html($o->ds_enum) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ze-form-group">
                    <label>Cor</label>
                    <select name="cor">
                        <?php foreach (ze_opt('tb_veiculos','cor') as $o): ?>
                            <option value="<?= esc_attr($o->ds_enum) ?>" <?= selected($edit_data->cor ?? '', $o->ds_enum); ?>><?= esc_html($o->ds_enum) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="ze-form-grid" style="margin-top:20px;">
                <div class="ze-form-group">
                    <label>Combustível</label>
                    <select name="ds_combustivel">
                        <?php foreach (ze_opt('tb_veiculos','ds_combustivel') as $o): ?>
                            <option value="<?= esc_attr($o->ds_enum) ?>" <?= selected($edit_data->ds_combustivel ?? '', $o->ds_enum); ?>><?= esc_html($o->ds_enum) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ze-form-group">
                    <label>Tipo de Veículo</label>
                    <select name="ds_veiculo">
                        <?php foreach (ze_opt('tb_veiculos','ds_tipo') as $o): ?>
                            <option value="<?= esc_attr($o->ds_enum) ?>" <?= selected($edit_data->ds_veiculo ?? '', $o->ds_enum); ?>><?= esc_html($o->ds_enum) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="ze-form-footer">
                <?php if($edit_data): ?><a href="admin.php?page=<?= $_GET['page'] ?>" class="ze-btn-secondary">Cancelar</a><?php endif; ?>
                <button type="submit" class="ze-btn-submit">
                    <span class="dashicons dashicons-saved"></span> <?= $edit_data ? 'Salvar Alterações' : 'Gravar e Vincular Motorista' ?>
                </button>
            </div>
        </form>
    </div>

    <div class="ze-card">
        <div class="ze-section-title"><span class="dashicons dashicons-filter"></span> Filtrar Resultados</div>
        <form method="get" class="ze-form-grid" style="grid-template-columns: 1fr 1fr 1fr auto;">
            <input type="hidden" name="page" value="<?= $_GET['page'] ?>">
            <div class="ze-form-group"><input type="text" name="f_placa" placeholder="Placa..." value="<?= $_GET['f_placa'] ?? '' ?>"></div>
            <div class="ze-form-group">
                <select name="f_marca">
                    <option value="">Todas as Marcas</option>
                    <?php foreach (ze_opt('tb_veiculos','marca') as $o) echo "<option value='{$o->ds_enum}' ".selected($_GET['f_marca']??'',$o->ds_enum,false).">{$o->ds_enum}</option>"; ?>
                </select>
            </div>
            <div class="ze-form-group">
                <select name="f_modelo">
                    <option value="">Todos os Modelos</option>
                    <?php foreach (ze_opt('tb_veiculos','modelo') as $o) echo "<option value='{$o->ds_enum}' ".selected($_GET['f_modelo']??'',$o->ds_enum,false).">{$o->ds_enum}</option>"; ?>
                </select>
            </div>
            <div style="display:flex; gap:8px; align-items:flex-end; padding-bottom:4px;">
                <button type="submit" class="ze-btn-submit">Filtrar</button>
                <a href="admin.php?page=<?= $_GET['page'] ?>" class="ze-btn-secondary">Limpar</a>
            </div>
        </form>
    </div>

    <div class="ze-card no-padding">
        <div style="padding: 20px 24px; font-weight: 700; border-bottom: 1px solid var(--ze-border); background: var(--ze-table-head-bg);">Frota Cadastrada</div>
        <table class="ze-table">
            <thead>
                <tr>
                    <th width="120">Placa</th>
                    <th>Marca / Modelo</th>
                    <th>Detalhes</th>
                    <th>Motorista</th>
                    <th width="150" style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if($veiculos): foreach($veiculos as $v): ?>
                <tr>
                    <td><strong style="letter-spacing: 1px;"><?= $v->placa ?></strong></td>
                    <td>
                        <div style="font-weight: 600;"><?= $v->marca ?> <?= $v->modelo ?></div>
                        <div style="font-size: 12px; color: var(--ze-text-sub);"><?= $v->cor ?></div>
                    </td>
                    <td>
                        <div style="font-size: 12px;"><strong>Tipo:</strong> <?= $v->ds_veiculo ?></div>
                        <div style="font-size: 12px;"><strong>Comb.:</strong> <?= $v->ds_combustivel ?></div>
                    </td>
                    <td>
                        <?php if($v->nom_eleitor): ?>
                            <span class="ze-badge ze-badge-success">✅ <?= esc_html($v->nom_eleitor) ?></span>
                        <?php else: ?>
                            <span class="ze-badge ze-badge-danger">⚠️ Sem Motorista</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <a href="?page=<?= $_GET['page'] ?>&vincular_id=<?= $v->id_veiculo ?>" class="ze-edit-link" title="Vincular" style="margin-right: 12px;"><span class="dashicons dashicons-admin-users"></span></a>
                        <a href="?page=<?= $_GET['page'] ?>&edit=<?= $v->id_veiculo ?>" class="ze-edit-link" style="margin-right: 12px;"><span class="dashicons dashicons-edit"></span></a>
                        <a href="?page=<?= $_GET['page'] ?>&del=<?= $v->id_veiculo ?>" class="ze-edit-link" style="color:var(--ze-danger-text)" onclick="return confirm('Excluir?')"><span class="dashicons dashicons-trash"></span></a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" style="text-align:center; padding:40px;">Nenhum veículo encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const p = document.getElementById('placa');
    if(p) {
        p.addEventListener('input', e => {
            let v = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g,'');
            if (v.length > 3) v = v.slice(0,3) + '-' + v.slice(3,7);
            e.target.value = v;
        });
    }
});
</script>