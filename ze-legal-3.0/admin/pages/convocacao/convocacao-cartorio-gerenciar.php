<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

$id_convocacao = isset($_GET['id']) ? intval($_GET['id']) : 0;
$acao = isset($_POST['acao']) ? sanitize_text_field($_POST['acao']) : '';
$justificativa = isset($_POST['justificativa_dispensa']) ? sanitize_textarea_field($_POST['justificativa_dispensa']) : '';

/* ======================================================
   1. CONSULTA PRINCIPAL (DADOS CONGELADOS + RESPONSÁVEIS)
====================================================== */
$conv = $wpdb->get_row($wpdb->prepare(
    "SELECT cv.*, 
            col.nom_eleitor, col.num_cpf, col.num_telefone_eleitor, col.ds_status_eleitoral, col.num_inscricao,
            f.nom_funcao, l.nom_local, z.num_zona, p.ano as ano_pleito,
            u_cri.display_name as criador, 
            u_ent.display_name as nome_usuario_entrega,
            u_sei.display_name as responsavel_sei
     FROM {$prefix}tb_convocacao cv
     INNER JOIN {$prefix}tb_colaboradores col ON col.id_colaborador = cv.id_colaborador
     INNER JOIN {$prefix}tb_pleitos p ON p.id_pleito = cv.id_pleito
     LEFT JOIN {$prefix}tb_funcoes f ON f.id_funcao = cv.id_funcao
     LEFT JOIN {$prefix}tb_locais l ON l.id_local = cv.id_local
     LEFT JOIN {$prefix}tb_zonas z ON z.id_zona = l.id_zona
     LEFT JOIN {$wpdb->users} u_cri ON u_cri.ID = cv.id_usuario_criacao
     LEFT JOIN {$wpdb->users} u_ent ON u_ent.ID = cv.id_usuario_entrega
     LEFT JOIN {$wpdb->users} u_sei ON u_sei.ID = cv.id_usuario_juntada
     WHERE cv.id_convocacao = %d", 
    $id_convocacao
));
if (!$conv) wp_die('Convocação não encontrada.');

/* ======================================================
   2. PROCESSAMENTO DE AÇÕES E GRAVAÇÃO DE LOG
====================================================== */
if ($acao && check_admin_referer('ze_gerenciar_convocacao')) {
    
    $hoje = current_time('mysql');
    $id_user = get_current_user_id();
    $ip_origem = $_SERVER['REMOTE_ADDR'];
    $status_anterior = $conv->status_convocacao;
    $status_novo = $status_anterior;
    $log_desc = '';

    switch ($acao) {
        case 'aceitar_cartorio':
            $status_novo = 'ACEITA_CARTORIO';
            $log_desc = "Aceite presencial realizado no cartório.";

            $wpdb->update("{$prefix}tb_convocacao", [
                'status_convocacao' => $status_novo,
                'data_aceite' => $hoje,
                'entregue_em_maos' => 1,
                'data_entrega_em_maos' => $hoje,
                'id_usuario_entrega' => $id_user
            ], ['id_convocacao' => $id_convocacao]);

            $wpdb->update("{$prefix}tb_vagas_pleitos", ['status_vaga' => $status_novo], ['id_vaga_pleito' => $conv->id_vaga_pleito]);
            $wpdb->update("{$prefix}tb_colaboradores", ['ds_status_eleitoral' => $status_novo], ['id_colaborador' => $conv->id_colaborador]);
            break;

        case 'dispensar':
            $status_novo = 'DISPENSADO';
            $log_desc = "Dispensa realizada. Motivo: " . ($justificativa ?: 'Não informado');

            $wpdb->update("{$prefix}tb_vagas_pleitos", ['status_vaga' => 'DISPONIVEL', 'id_colaborador' => NULL], ['id_vaga_pleito' => $conv->id_vaga_pleito]);
            $wpdb->update("{$prefix}tb_colaboradores", ['ds_status_eleitoral' => $status_novo], ['id_colaborador' => $conv->id_colaborador]);
            $wpdb->update("{$prefix}tb_convocacao", ['status_convocacao' => $status_novo], ['id_convocacao' => $id_convocacao]);
            break;

        case 'liberar_colaborador':
            $log_desc = "Colaborador liberado manualmente para o status DISPONIVEL.";
            $wpdb->update("{$prefix}tb_colaboradores", ['ds_status_eleitoral' => 'DISPONIVEL'], ['id_colaborador' => $conv->id_colaborador]);
            break;
        
        case 'registrar_sei':
            $num_sei = sanitize_text_field($_POST['numero_sei']);
            if (!empty($num_sei)) {
                $wpdb->update("{$prefix}tb_convocacao", [
                    'id_evento_sei' => $num_sei,
                    'data_juntada_sei' => $hoje,
                    'id_usuario_juntada' => $id_user
                ], ['id_convocacao' => $id_convocacao]);
                
                $log_desc = "Juntada ao SEI realizada. Evento nº: " . $num_sei;
                $status_novo = $status_anterior; // O status da convocação não muda, apenas formaliza
            }
            break;
    }

    $wpdb->insert("{$prefix}tb_log_convocacao", [
        'id_convocacao'   => $id_convocacao,
        'status_anterior' => $status_anterior,
        'status_novo'     => $status_novo,
        'acao'            => $acao,
        'descricao'       => $log_desc,
        'id_usuario'      => $id_user,
        'ip_origem'       => $ip_origem,
        'data_evento'     => $hoje
    ]);

    wp_safe_redirect(admin_url('admin.php?page=ze-legal-convocacao-cartorio-gerenciar&id=' . $id_convocacao));
    exit;
}

/* ======================================================
   3. CONSULTAS SECUNDÁRIAS
====================================================== */
$vaga_atual = $wpdb->get_row($wpdb->prepare(
    "SELECT vp.*, l.nom_local, col.nom_eleitor as nom_colaborador_vaga
     FROM {$prefix}tb_vagas_pleitos vp
     LEFT JOIN {$prefix}tb_locais l ON l.id_local = vp.id_local
     LEFT JOIN {$prefix}tb_colaboradores col ON col.id_colaborador = vp.id_colaborador
     WHERE vp.id_vaga_pleito = %d",
    $conv->id_vaga_pleito
));

$logs = $wpdb->get_results($wpdb->prepare(
    "SELECT l.*, u.display_name FROM {$prefix}tb_log_convocacao l 
     LEFT JOIN {$wpdb->users} u ON u.ID = l.id_usuario 
     WHERE l.id_convocacao = %d ORDER BY l.data_evento DESC", 
    $id_convocacao
));

function ze_formatar_tipo_ip($ip) {
    if (empty($ip)) return '-';
    return (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6: ' : 'IPv4: ') . $ip;
}

// URL de retorno padronizada
$url_retorno = admin_url('admin.php?page=ze-legal-convocacao-cartorio-consulta');
?>

<style>
    .ze-manager { margin: 20px 20px 0 0; max-width: 1100px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .ze-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .ze-stack { display: flex; flex-direction: column; gap: 20px; }
    .ze-grid-side { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 768px) { .ze-grid-side { grid-template-columns: 1fr; } }
    
    .ze-panel { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column; }
    .ze-panel-h { padding: 12px 15px; border-bottom: 1px solid #f0f0f1; font-weight: 700; color: #1d2327; background: #f8f9fa; }
    .ze-panel-b { padding: 18px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; flex-grow: 1; }
    .ze-panel-f { padding: 10px 15px; background: #fafafa; border-top: 1px solid #f0f0f1; display: flex; justify-content: flex-end; align-items: center; }
    
    .panel-success { background-color: #f0f9f1 !important; border-color: #c3e6cb !important; }
    .panel-success .ze-panel-h { background-color: #e2f2e4 !important; }

    .ze-lab { font-size: 10px; color: #646970; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px; }
    .ze-val { font-size: 13px; color: #1d2327; line-height: 1.4; }
    .ze-badge { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 800; border: 1px solid transparent; text-transform: uppercase; }
    .st-vga { background: #edf7ed; color: #1e4620; border-color: #c3e6cb; }
    .st-pendente { background: #fff8e5; color: #856404; border-color: #ffeeba; }

    .ze-actions { margin-top: 30px; background: #2c3338; padding: 25px; border-radius: 12px; }
    .ze-action-form { display: flex; flex-direction: column; gap: 20px; align-items: center; }
    .ze-btn-group { display: flex; gap: 15px; flex-wrap: wrap; justify-content: center; }
    .ze-btn { padding: 12px 24px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; }
    .btn-blue { background: #2271b1; color: #fff; }
    .btn-red { background: #d63638; color: #fff; }
    .btn-grey { background: #f6f7f7; color: #2271b1; border: 1px solid #dcdcde; }
    .btn-outline-white { background: transparent; border: 1px solid #fff; color: #fff; }
    .btn-outline-white:hover { background: #fff; color: #2c3338; }

    .dispensa-box { background: #3f4347; padding: 15px; border-radius: 8px; width: 100%; max-width: 600px; display: none; border: 1px solid #d63638; }
    .dispensa-box textarea { width: 100%; background: #fff; border: none; border-radius: 4px; padding: 10px; font-size: 13px; margin-top: 10px; }
</style>

<div class="ze-admin-container">
    <div class="ze-header-flex">
        <h1 style="margin:0;">Gestão Administrativa #<?= $id_convocacao ?></h1>
        <a href="<?= $url_retorno ?>" class="ze-btn btn-grey">← Voltar para Consultas</a>
    </div>

    <div class="ze-stack">
        
        <div class="ze-panel">
            <div class="ze-panel-h">👤 Perfil do Colaborador</div>
            <div class="ze-panel-b">
                <div class="ze-item"><span class="ze-lab">Nome Eleitor</span><span class="ze-val"><strong><?= esc_html($conv->nom_eleitor) ?></strong></span></div>
                <div class="ze-item"><span class="ze-lab">CPF</span><span class="ze-val"><?= esc_html($conv->num_cpf) ?></span></div>
                <div class="ze-item"><span class="ze-lab">Inscrição</span><span class="ze-val"><?= esc_html($conv->num_inscricao) ?></span></div>
                <div class="ze-item"><span class="ze-lab">Telefone</span><span class="ze-val"><?= esc_html($conv->num_telefone_eleitor ?: 'N/I') ?></span></div>
            </div>
            <div class="ze-panel-f"><span class="ze-badge"><?= $conv->ds_status_eleitoral ?></span></div>
        </div>

        <?php 
            $has_download = !empty($conv->data_download); 
            $has_sei = !empty($conv->id_evento_sei);
        ?>
        <div class="ze-panel <?= ($has_download || $has_sei) ? 'panel-success' : '' ?>">
            <div class="ze-panel-h">📋 Registro de Designação e Formalização (SEI)</div>
            <div class="ze-panel-b" style="grid-template-columns: repeat(3, 1fr);">
                <div class="ze-sub-group">
                    <span class="ze-lab">Pleito / Função</span>
                    <span class="ze-val"><?= $conv->ano_pleito ?> — <strong><?= esc_html($conv->nom_funcao) ?></strong></span>
                    
                    <span class="ze-lab" style="margin-top:10px;">Local / Seção</span>
                    <span class="ze-val"><strong><?= esc_html($conv->nom_local) ?> (<?= esc_html($conv->num_secao) ?>)</strong></span>
                </div>
        
                <div class="ze-sub-group" style="border-left: 1px solid #f0f0f1; padding-left: 15px;">
                    <span class="ze-lab">ID Evento SEI</span>
                    <span class="ze-val"><strong><?= $conv->id_evento_sei ?: '<span style="color:#cc0000">Não Informado</span>' ?></strong></span>
                    
                    <span class="ze-lab" style="margin-top:10px;">Juntada ao SEI em</span>
                    <span class="ze-val">
                        <?= $conv->data_juntada_sei ? date('d/m/Y H:i', strtotime($conv->data_juntada_sei)) : 'Pendente' ?>
                        <?php if ($conv->responsavel_sei): ?>
                            <small style="display:block; font-size:10px; color:#666;">Por: <?= esc_html($conv->responsavel_sei) ?></small>
                        <?php endif; ?>
                    </span>
                </div>
        
                <div class="ze-sub-group" style="border-left: 1px solid #f0f0f1; padding-left: 15px;">
                    <span class="ze-lab">Último Download (Cidadão)</span>
                    <span class="ze-val"><?= $has_download ? date('d/m/Y H:i', strtotime($conv->data_download)) : 'Nunca baixada' ?></span>
                    
                    <span class="ze-lab" style="margin-top:10px;">Protocolo IP</span>
                    <span class="ze-val ze-ip-tag"><?= ze_formatar_tipo_ip($conv->ip_download) ?></span>
                </div>
            </div>
            <div class="ze-panel-f">
                <span class="ze-badge <?= $has_sei ? 'st-vga' : 'st-pendente' ?>">
                    <?= $has_sei ? 'FORMALIZADO NO SEI' : 'PENDENTE DE JUNTADA' ?>
                </span>
            </div>
        </div>

        <div class="ze-grid-side">
    
            <?php 
            // Só consideramos Aceite Web "REALIZADO" se houver um IP registrado pelo cidadão
            $is_real_web_aceite = !empty($conv->ip_aceite); 
            ?>
            <div class="ze-panel <?= $is_real_web_aceite ? 'panel-success' : '' ?>">
                <div class="ze-panel-h">🌐 Aceite Eletrônico (Cidadão)</div>
                <div class="ze-panel-b" style="grid-template-columns: 1fr;">
                    <div class="ze-item">
                        <span class="ze-lab">Data do Aceite Web</span>
                        <span class="ze-val"><?= !empty($conv->data_aceite) && $is_real_web_aceite ? date('d/m/Y H:i', strtotime($conv->data_aceite)) : 'Pendente ou Realizado via Cartório' ?></span>
                    </div>
                    <div class="ze-item">
                        <span class="ze-lab">IP de Aceite</span>
                        <span class="ze-val ze-ip-tag"><?= ze_formatar_tipo_ip($conv->ip_aceite) ?></span>
                    </div>
                </div>
                <div class="ze-panel-f">
                    <?php if ($is_real_web_aceite): ?>
                        <span class="ze-badge st-vga">REALIZADO VIA WEB</span>
                    <?php else: ?>
                        <span class="ze-badge st-pendente">AGUARDANDO CIDADÃO</span>
                    <?php endif; ?>
                </div>
            </div>
        
            <?php $has_entrega = !empty($conv->entregue_em_maos); ?>
            <div class="ze-panel <?= $has_entrega ? 'panel-success' : '' ?>">
                <div class="ze-panel-h">🏢 Gestão Presencial (Cartório)</div>
                <div class="ze-panel-b" style="grid-template-columns: 1fr;">
                    <div class="ze-item">
                        <span class="ze-lab">Entregue em Mãos?</span>
                        <span class="ze-val">
                            <strong><?= $has_entrega ? 'Sim' : 'Não' ?></strong>
                            <?php if ($has_entrega && !empty($conv->nome_usuario_entrega)): ?>
                                <small style="display:block; color: #1e4620;">Registrado por: <strong><?= esc_html($conv->nome_usuario_entrega) ?></strong></small>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="ze-item">
                        <span class="ze-lab">Data da Entrega / Aceite</span>
                        <span class="ze-val"><?= $conv->data_entrega_em_maos ? date('d/m/Y H:i', strtotime($conv->data_entrega_em_maos)) : '-' ?></span>
                    </div>
                </div>
                <div class="ze-panel-f">
                    <span class="ze-badge <?= $has_entrega ? 'st-vga' : 'st-pendente' ?>">
                        <?= $has_entrega ? 'EFETIVADO NO CARTÓRIO' : 'PENDENTE DE ENTREGA' ?>
                    </span>
                </div>
            </div>
        
        </div>

        <div class="ze-panel">
            <div class="ze-panel-h">🗳️ Situação Atual da Vaga (Logística)</div>
            <div class="ze-panel-b">
                <div class="ze-item"><span class="ze-lab">Local Atual / Seção</span><span class="ze-val"><?= $vaga_atual ? esc_html($vaga_atual->nom_local) . ' / ' . $vaga_atual->num_secao : 'N/A' ?></span></div>
                <div class="ze-item"><span class="ze-lab">Colaborador Atual</span><span class="ze-val"><?= $vaga_atual && $vaga_atual->nom_colaborador_vaga ? esc_html($vaga_atual->nom_colaborador_vaga) : 'Disponível' ?></span></div>
            </div>
            <div class="ze-panel-f"><span class="ze-badge st-vga"><?= $vaga_atual ? $vaga_atual->status_vaga : 'EXCLUÍDA' ?></span></div>
        </div>

        <div class="ze-panel">
            <div class="ze-panel-h">📜 Histórico de Auditoria</div>
            <div class="ze-panel-b" style="grid-template-columns: 1fr; max-height: 200px; overflow-y: auto;">
                <?php if ($logs) : foreach ($logs as $log) : ?>
                    <div style="padding: 8px 0; border-bottom: 1px solid #f0f0f1; font-size: 12px;">
                        <strong><?= date('d/m/Y H:i', strtotime($log->data_evento)) ?></strong> | 
                        <span style="color:#2271b1;"><?= $log->status_anterior ?> → <?= $log->status_novo ?></span><br>
                        <?= esc_html($log->descricao) ?> | <small>Por: <?= esc_html($log->display_name) ?></small>
                    </div>
                <?php endforeach; else: ?>
                    <p style="font-size: 13px;">Sem registros.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ze-actions">
        <form method="post" id="form-gerenciar" class="ze-action-form">
            <?php if (empty($conv->id_evento_sei)): ?>
                <div style="background: #f0f6fb; padding: 15px; border-radius: 8px; margin-bottom: 20px; width: 100%; max-width: 450px; border: 1px solid #2271b1; text-align: left;">
                    <label class="ze-lab" style="color:#2271b1">Registrar Número do Evento SEI:</label>
                    <div style="display:flex; gap:10px; margin-top:5px;">
                        <input type="text" name="numero_sei" placeholder="Ex: 1234567" style="flex:1; height:38px; border-radius:4px; border:1px solid #2271b1; padding: 0 10px;">
                        <button name="acao" value="registrar_sei" class="ze-btn btn-blue" style="height:38px;">Gravar SEI</button>
                    </div>
                </div>
            <?php endif; ?>
            
            
            
            <?php wp_nonce_field('ze_gerenciar_convocacao'); ?>
            
            <div id="box-justificativa" class="dispensa-box">
                <label style="color:#fff; font-size:11px; font-weight:bold; margin-bottom:5px; display:block;">JUSTIFICATIVA DA DISPENSA (Obrigatório para Log):</label>
                <textarea name="justificativa_dispensa" id="txt-justificativa" placeholder="Informe o motivo da dispensa..."></textarea>
                <div style="margin-top:10px; display:flex; gap:10px;">
                    <button type="submit" name="acao" value="dispensar" class="ze-btn btn-red">CONFIRMAR DISPENSA</button>
                    <button type="button" onclick="cancelarDispensa()" class="ze-btn btn-grey">CANCELAR</button>
                </div>
            </div>

            <div class="ze-btn-group" id="group-buttons">
                <?php if ($conv->status_convocacao === 'AGUARDANDO_ACEITE'): ?>
                    <button type="submit" name="acao" value="aceitar_cartorio" class="ze-btn btn-blue">EFETIVAR ACEITE CARTÓRIO</button>
                    <button type="button" onclick="mostrarDispensa()" class="ze-btn btn-red">DISPENSAR</button>
                
                <?php elseif (in_array($conv->status_convocacao, ['CONVOCACAO_ACEITA', 'ACEITA_CARTORIO'])): ?>
                    <a href="<?= admin_url('admin-post.php?action=ze_gerar_pdf_convocacao&id=' . $conv->id_convocacao) ?>" class="ze-btn btn-grey" target="_blank">GERAR CARTA (PDF)</a>
                    <button type="button" onclick="mostrarDispensa()" class="ze-btn btn-red">DISPENSAR COLABORADOR</button>
                
                <?php elseif ($conv->ds_status_eleitoral === 'DISPENSADO'): ?>
                    <?php if ($conv->ds_status_eleitoral !== 'DISPONIVEL'): ?>
                        <button type="submit" name="acao" value="liberar_colaborador" class="ze-btn btn-grey">TORNAR COLABORADOR DISPONÍVEL</button>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="<?= $url_retorno ?>" class="ze-btn btn-outline-white">SAIR</a>
            </div>
        </form>
    </div>
</div>

<script>
function mostrarDispensa() {
    document.getElementById('group-buttons').style.display = 'none';
    document.getElementById('box-justificativa').style.display = 'block';
    document.getElementById('txt-justificativa').focus();
}
function cancelarDispensa() {
    document.getElementById('group-buttons').style.display = 'flex';
    document.getElementById('box-justificativa').style.display = 'none';
}
</script>