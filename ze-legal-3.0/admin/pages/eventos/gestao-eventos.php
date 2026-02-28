<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('ze_cadastro_adm_zona')) {
    wp_die('Acesso não autorizado.');
}

global $wpdb;
$prefix = $wpdb->prefix;
$table_eventos_vagas = $prefix . 'ze_tb_eventos_vagas';
$table_tipos_eventos = $prefix . 'ze_tb_tipos_eventos';
$vw_eventos = $prefix . 'ze_vw_eventos_vagas_locais';

/* ==========================================================
   PROCESSAMENTO: EXCLUSÃO DE EVENTO
========================================================== */
if (isset($_POST['acao_excluir_evento'])) {
    check_admin_referer('ze_excluir_evento_action');
    $id_ev = intval($_POST['id_evento_vaga']);
    
    $wpdb->delete($table_eventos_vagas, ['id_evento_vaga' => $id_ev], ['%d']);
    echo '<div class="notice notice-warning is-dismissible"><p>Registro excluído permanentemente.</p></div>';
}

/* ==========================================================
   PROCESSAMENTO: EDIÇÃO COMPLETA
========================================================== */
if (isset($_POST['acao_editar_evento'])) {
    check_admin_referer('ze_edit_evento_action');
    
    $id_ev = intval($_POST['id_evento_vaga']);
    $dados_update = [
        'id_tipo_evento'   => intval($_POST['id_tipo_evento']),
        'compareceu'       => isset($_POST['compareceu']) ? 1 : 0,
        'vale_alimentacao' => isset($_POST['vale_alimentacao']) ? 1 : 0,
        'ds_local_evento'  => sanitize_text_field($_POST['ds_local_evento']),
        'data_evento'      => sanitize_text_field($_POST['data_evento']),
        'hora_inicio'      => sanitize_text_field($_POST['hora_inicio']),
        'hora_fim'         => sanitize_text_field($_POST['hora_fim'])
    ];

    $wpdb->update($table_eventos_vagas, $dados_update, ['id_evento_vaga' => $id_ev]);
    echo '<div class="notice notice-success is-dismissible"><p>Evento atualizado com sucesso!</p></div>';
}

/* ==========================================================
   LÓGICA DE FILTROS E BUSCA (MANTIDA)
========================================================== */
$where = ["1=1"];
if (!empty($_GET['f_data']))     $where[] = $wpdb->prepare("data_evento = %s", $_GET['f_data']);
if (!empty($_GET['f_tipo']))     $where[] = $wpdb->prepare("ds_tipo_evento = %s", $_GET['f_tipo']);
if (!empty($_GET['f_local']))    $where[] = $wpdb->prepare("nom_local = %s", $_GET['f_local']);
if (!empty($_GET['f_colab_id'])) $where[] = $wpdb->prepare("id_colaborador = %d", $_GET['f_colab_id']);

if (isset($_GET['f_presenca_ok']))  $where[] = "compareceu = 1";
if (isset($_GET['f_presenca_no']))  $where[] = "compareceu = 0";
if (isset($_GET['f_vale_ok']))      $where[] = "vale_alimentacao = 1";
if (isset($_GET['f_vale_no']))      $where[] = "vale_alimentacao = 0";

$sql_final = "SELECT * FROM {$vw_eventos} WHERE " . implode(' AND ', $where) . " ORDER BY data_evento DESC, hora_inicio ASC";
$eventos = $wpdb->get_results($sql_final);

$datas_existentes   = $wpdb->get_col("SELECT DISTINCT data_evento FROM {$vw_eventos} ORDER BY data_evento DESC");
$tipos_existentes   = $wpdb->get_col("SELECT DISTINCT ds_tipo_evento FROM {$vw_eventos} ORDER BY ds_tipo_evento ASC");
$locais_existentes  = $wpdb->get_col("SELECT DISTINCT nom_local FROM {$vw_eventos} ORDER BY nom_local ASC");
$colabs_existentes  = $wpdb->get_results("SELECT DISTINCT id_colaborador, nom_eleitor, num_cpf FROM {$vw_eventos} WHERE id_colaborador IS NOT NULL ORDER BY nom_eleitor ASC");
$todos_tipos_evento = $wpdb->get_results("SELECT id_tipo_evento, ds_tipo_evento FROM {$table_tipos_eventos} ORDER BY ds_tipo_evento ASC");
?>

<style>
    :root { --ze-primary: #0f172a; --ze-accent: #2563eb; --ze-danger: #dc2626; --ze-bg: #f8fafc; }
    .ze-gestao-wrap { padding: 20px; background: var(--ze-bg); font-family: 'Inter', system-ui, sans-serif; }
    .ze-filter-container { background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 30px; border: 1px solid #e2e8f0; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .ze-filter-row-main { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 20px; }
    .ze-filter-row-status { display: flex; flex-wrap: wrap; gap: 20px; padding-top: 20px; border-top: 1px solid #f1f5f9; align-items: center; }
    .f-label { display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    .ze-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; font-size: 13px; }
    .ze-check-group { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer; }
    .ze-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px; }
    .ze-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; transition: 0.2s; }
    .ze-card:hover { border-color: var(--ze-accent); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    .ze-card-header { padding: 20px; border-bottom: 1px solid #f1f5f9; background: #fafafa; }
    .ze-card-body { padding: 20px; flex-grow: 1; }
    .ze-card-footer { padding: 15px 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .badge { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
    .badge-presente { background: #dcfce7; color: #166534; }
    .badge-ausente { background: #fee2e2; color: #991b1b; }
    .btn-delete { color: var(--ze-danger); background: none; border: none; cursor: pointer; font-size: 12px; font-weight: 600; padding: 5px; }
    .btn-delete:hover { text-decoration: underline; }
    #zeModal { display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter: blur(3px); }
    .modal-content { background:#fff; margin:5% auto; padding:30px; border-radius:16px; width:520px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
</style>

<div class="ze-gestao-wrap">
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 26px; font-weight: 800; color: var(--ze-primary); margin: 0;">Gestão de Eventos</h1>
            <p style="color: #64748b; margin: 5px 0 0 0;">Painel de controle de eventos e presença.</p>
        </div>
        <div style="font-weight: 800; color: var(--ze-accent); background: #fff; padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <?= count($eventos) ?> <span style="color:#94a3b8; font-size:12px;">REGISTROS</span>
        </div>
    </div>

    <div class="ze-filter-container">
        <form method="get">
            <input type="hidden" name="page" value="<?= esc_attr($_GET['page']) ?>">
            <div class="ze-filter-row-main">
                <div>
                    <label class="f-label">Colaborador Ativo</label>
                    <select name="f_colab_id" class="ze-input">
                        <option value="">Todos os colaboradores registrados</option>
                        <?php foreach($colabs_existentes as $c): ?>
                            <option value="<?= $c->id_colaborador ?>" <?= (($_GET['f_colab_id'] ?? '') == $c->id_colaborador) ? 'selected' : '' ?>>
                                <?= esc_html($c->nom_eleitor) ?> (<?= esc_html($c->num_cpf) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="f-label">Data</label>
                    <select name="f_data" class="ze-input">
                        <option value="">Todas as datas</option>
                        <?php foreach($datas_existentes as $d): ?>
                            <option value="<?= $d ?>" <?= (($_GET['f_data'] ?? '') == $d) ? 'selected' : '' ?>><?= date('d/m/Y', strtotime($d)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="f-label">Tipo de Evento</label>
                    <select name="f_tipo" class="ze-input">
                        <option value="">Todos os tipos</option>
                        <?php foreach($tipos_existentes as $t): ?>
                            <option value="<?= $t ?>" <?= (($_GET['f_tipo'] ?? '') == $t) ? 'selected' : '' ?>><?= esc_html($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="f-label">Local Cadastrado</label>
                    <select name="f_local" class="ze-input">
                        <option value="">Todos os locais</option>
                        <?php foreach($locais_existentes as $l): ?>
                            <option value="<?= $l ?>" <?= (($_GET['f_local'] ?? '') == $l) ? 'selected' : '' ?>><?= esc_html($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="ze-filter-row-status">
                <span class="f-label" style="margin-bottom:0; margin-right: 10px;">Filtrar Presença/Vale:</span>
                <label class="ze-check-group"><input type="checkbox" name="f_presenca_ok" value="1" <?= isset($_GET['f_presenca_ok']) ? 'checked' : '' ?>> Presentes</label>
                <label class="ze-check-group"><input type="checkbox" name="f_presenca_no" value="1" <?= isset($_GET['f_presenca_no']) ? 'checked' : '' ?>> Ausentes</label>
                <label class="ze-check-group" style="margin-left: 15px; border-left: 2px solid #f1f5f9; padding-left: 20px;"><input type="checkbox" name="f_vale_ok" value="1" <?= isset($_GET['f_vale_ok']) ? 'checked' : '' ?>> Com Vale</label>
                <label class="ze-check-group"><input type="checkbox" name="f_vale_no" value="1" <?= isset($_GET['f_vale_no']) ? 'checked' : '' ?>> Sem Vale</label>

                <div style="margin-left: auto; display: flex; gap: 10px;">
                    <a href="?page=<?= esc_attr($_GET['page']) ?>" class="button">Limpar</a>
                    <button type="submit" class="button button-primary" style="padding: 0 25px;">Aplicar Filtros</button>
                </div>
            </div>
        </form>
    </div>

    <div class="ze-card-grid">
        <?php if($eventos): foreach($eventos as $ev): ?>
            <div class="ze-card">
                <div class="ze-card-header">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <span style="font-size:10px; font-weight:800; color:var(--ze-accent); text-transform:uppercase;"><?= esc_html($ev->ds_tipo_evento) ?></span>
                        <span class="badge <?= $ev->compareceu ? 'badge-presente' : 'badge-ausente' ?>">
                            <?= $ev->compareceu ? 'Presente' : 'Ausente' ?>
                        </span>
                    </div>
                    <h3 style="margin:0; font-size:15px; color:#1e293b;"><?= esc_html($ev->nom_eleitor) ?></h3>
                    <p style="margin:4px 0 0 0; font-size:11px; color:#64748b;">CPF: <?= esc_html($ev->num_cpf) ?> | Função: <?= esc_html($ev->nom_funcao) ?></p>
                </div>
                <div class="ze-card-body">
                    <div style="font-size:13px; font-weight:700; color:#334155; margin-bottom:12px;">
                        📅 <?= date('d/m/Y', strtotime($ev->data_evento)) ?> | 🕒 <?= substr($ev->hora_inicio,0,5) ?>h às <?= substr($ev->hora_fim,0,5) ?>h
                    </div>
                    <div style="font-size:12px; color:#475569; line-height:1.4;">
                        📍 <strong>Onde ocorreu:</strong> <?= esc_html($ev->ds_local_evento) ?><br>
                        <small style="color:#94a3b8">📍 <strong>Base:</strong> <?= esc_html($ev->nom_local) ?> (Seção <?= $ev->num_secao ?>)</small>
                    </div>
                </div>
                <div class="ze-card-footer">
                    <form method="post" onsubmit="return confirmarExclusao('<?= esc_js($ev->nom_eleitor) ?>');" style="margin:0;">
                        <?php wp_nonce_field('ze_excluir_evento_action'); ?>
                        <input type="hidden" name="id_evento_vaga" value="<?= $ev->id_evento_vaga ?>">
                        <button type="submit" name="acao_excluir_evento" class="btn-delete">Excluir</button>
                    </form>
                    
                    <button class="button button-small" onclick='abrirEdicao(<?= json_encode($ev) ?>)'>Editar</button>
                </div>
            </div>
        <?php endforeach; else: ?>
            <div style="grid-column: 1/-1; text-align:center; padding:80px; background:#fff; border-radius:12px; border:2px dashed #e2e8f0; color:#94a3b8;">Nenhum registro encontrado.</div>
        <?php endif; ?>
    </div>
</div>

<div id="zeModal">
    <div class="modal-content">
        <h2 style="margin-top:0; font-size:18px;">Ajuste de Registro</h2>
        <p id="m_nome_colab" style="font-weight:700; color:var(--ze-accent); margin-bottom:20px; font-size:14px;"></p>
        
        <form method="post">
            <?php wp_nonce_field('ze_edit_evento_action'); ?>
            <input type="hidden" name="id_evento_vaga" id="m_id">
            
            <div style="margin-bottom:15px;">
                <label class="f-label">Tipo de Evento</label>
                <select name="id_tipo_evento" id="m_tipo_id" class="ze-input" required>
                    <?php foreach($todos_tipos_evento as $te): ?>
                        <option value="<?= $te->id_tipo_evento ?>"><?= esc_html($te->ds_tipo_evento) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label class="f-label">Local de Realização</label>
                <input type="text" name="ds_local_evento" id="m_local" class="ze-input" required>
            </div>

            <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:10px; margin-bottom:20px;">
                <div><label class="f-label">Data</label><input type="date" name="data_evento" id="m_data" class="ze-input" required></div>
                <div><label class="f-label">Início</label><input type="time" name="hora_inicio" id="m_inicio" class="ze-input" required></div>
                <div><label class="f-label">Fim</label><input type="time" name="hora_fim" id="m_fim" class="ze-input"></div>
            </div>

            <div style="background:#f8fafc; padding:15px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:25px;">
                <label class="ze-check-group" style="margin-bottom:10px;"><input type="checkbox" name="compareceu" id="m_presenca" value="1"> Presença Confirmada</label>
                <label class="ze-check-group"><input type="checkbox" name="vale_alimentacao" id="m_vale" value="1"> Direito a Vale Alimentação</label>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="button" onclick="fecharModal()">Cancelar</button>
                <button type="submit" name="acao_editar_evento" class="button button-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
// Nova função de confirmação para o botão excluir
function confirmarExclusao(nome) {
    return confirm("Tem certeza que deseja excluir permanentemente o registro de " + nome + "?\nEsta ação não poderá ser desfeita.");
}

function abrirEdicao(dados) {
    document.getElementById('m_id').value = dados.id_evento_vaga;
    document.getElementById('m_nome_colab').innerText = dados.nom_eleitor + " (" + dados.num_cpf + ")";
    document.getElementById('m_tipo_id').value = dados.id_tipo_evento;
    document.getElementById('m_local').value = dados.ds_local_evento;
    document.getElementById('m_data').value = dados.data_evento;
    document.getElementById('m_inicio').value = dados.hora_inicio;
    document.getElementById('m_fim').value = dados.hora_fim;
    document.getElementById('m_presenca').checked = (dados.compareceu == 1);
    document.getElementById('m_vale').checked = (dados.vale_alimentacao == 1);
    document.getElementById('zeModal').style.display = 'block';
}
function fecharModal() { document.getElementById('zeModal').style.display = 'none'; }
window.onclick = function(e) { if (e.target == document.getElementById('zeModal')) fecharModal(); }
</script>