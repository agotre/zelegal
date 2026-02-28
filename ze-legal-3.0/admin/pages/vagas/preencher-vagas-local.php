<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

/* =========================================================
 * 1. TABELAS E PARÂMETROS
 * ========================================================= */
$tbl_pleitos  = "{$prefix}tb_pleitos";
$tbl_vagas    = "{$prefix}tb_vagas_pleitos";
$tbl_locais   = "{$prefix}tb_locais";
$tbl_funcoes  = "{$prefix}tb_funcoes";
$tbl_colabs   = "{$prefix}tb_colaboradores";
$tbl_conv     = "{$prefix}tb_convocacao";
$tbl_log_vaga = "{$prefix}tb_log_vagas_pleitos";
$tbl_zonas    = "{$prefix}tb_zonas"; 

$id_local = isset($_GET['id_local']) ? intval($_GET['id_local']) : 0;
if ( ! $id_local ) return;

/* =========================
   PROCESSAR AÇÕES (POST)
========================= */
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('ze_vagas_acoes_lista') ) {
    $acao    = sanitize_text_field($_POST['acao']);
    $id_vaga = intval($_POST['id_vaga_pleito']);
    $id_colab = intval($_POST['id_colaborador']);
    $agora   = current_time('mysql');
    $usuario = get_current_user_id();

    // --- AÇÃO: ESVAZIAR VAGA ---
    if ( $acao === 'esvaziar_vaga' ) {
        $wpdb->update($tbl_colabs, ['ds_status_eleitoral' => 'DISPONIVEL', 'updated_at' => $agora], ['id_colaborador' => $id_colab]);
        $wpdb->update($tbl_vagas, ['id_colaborador' => null, 'status_vaga' => 'DISPONIVEL', 'updated_at' => $agora], ['id_vaga_pleito' => $id_vaga]);
        $wpdb->insert($tbl_log_vaga, [
            'id_vaga_pleito' => $id_vaga, 'status_anterior' => 'PRE_SELECIONADO', 'status_novo' => 'DISPONIVEL',
            'id_colaborador_anterior' => $id_colab, 'motivo' => 'Esvaziada via listagem rápida',
            'id_user' => $usuario, 'data_evento' => $agora
        ]);
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    // --- AÇÃO: RESERVAR ---
    if ( $acao === 'reservar' ) {
        $vaga_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_vagas} WHERE id_vaga_pleito = %d", $id_vaga));
        
        if ($vaga_info) {
            // 1. GARANTE QUE O USUÁRIO EXISTE (USANDO O MOTOR ÚNICO)
            if ( function_exists('ze_legal_gerar_acesso_completo_colaborador') ) {
                ze_legal_gerar_acesso_completo_colaborador( $id_colab );
            }

            // 2. PREPARA EVENTOS PARA SNAPSHOT
            $tbl_eventos = "{$prefix}tb_eventos_vaga";
            $tbl_tipos_ev = "{$prefix}tb_tipos_evento";
            $eventos_dados = $wpdb->get_results($wpdb->prepare("SELECT e.*, t.ds_tipo_evento as tipo_descricao FROM {$tbl_eventos} e LEFT JOIN {$tbl_tipos_ev} t ON e.id_tipo_evento = t.id_tipo_evento WHERE e.id_vaga_pleito = %d ORDER BY e.data_evento ASC", $id_vaga), ARRAY_A);
            $eventos_json = !empty($eventos_dados) ? json_encode($eventos_dados) : null;
            $prazo = date('Y-m-d H:i:s', strtotime('+72 hours'));

            // 3. ATUALIZA VAGA E COLABORADOR
            $wpdb->update($tbl_vagas, ['status_vaga' => 'RESERVADO', 'dt_designacao' => $agora, 'updated_at' => $agora], ['id_vaga_pleito' => $id_vaga]);
            $wpdb->update($tbl_colabs, ['ds_status_eleitoral' => 'RESERVADO', 'updated_at' => $agora], ['id_colaborador' => $id_colab]);

            // 4. GERA CONVOCAÇÃO
            $wpdb->insert($tbl_conv, [
                'id_pleito' => $vaga_info->id_pleito, 
                'id_vaga_pleito' => $id_vaga, 
                'id_colaborador' => $id_colab,
                'id_local' => $vaga_info->id_local, 
                'num_secao' => $vaga_info->num_secao, 
                'tp_secao_mrv' => $vaga_info->tp_secao_mrv,
                'id_funcao' => $vaga_info->id_funcao, 
                'dt_designacao' => $agora, 
                'id_usuario_responsavel' => $usuario,
                'eventos' => $eventos_json, 
                'status_convocacao' => 'AGUARDANDO_ACEITE', 
                'data_criacao' => $agora, 
                'data_limite_aceite' => $prazo, 
                'id_usuario_criacao' => $usuario, 
                'created_at' => $agora
            ]);

            $id_nova_convocacao = $wpdb->insert_id;

            // 5. LOGS
            $wpdb->insert("{$prefix}tb_log_convocacao", [
                'id_convocacao' => $id_nova_convocacao, 
                'status_anterior' => 'NENHUM', 
                'status_novo' => 'AGUARDANDO_ACEITE', 
                'acao' => 'reserva_inicial', 
                'descricao' => 'Convocação gerada via reserva rápida.', 
                'id_usuario' => $usuario, 
                'ip_origem' => $_SERVER['REMOTE_ADDR'], 
                'data_evento' => $agora
            ]);

            $wpdb->insert($tbl_log_vaga, [
                'id_vaga_pleito' => $id_vaga, 
                'status_anterior' => $vaga_info->status_vaga, 
                'status_novo' => 'RESERVADO', 
                'id_colaborador_novo' => $id_colab, 
                'motivo' => 'Reservada via listagem rápida', 
                'id_user' => $usuario, 
                'data_evento' => $agora
            ]);

            // REDIRECIONA PARA ATUALIZAR A TELA
            wp_safe_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

/* =========================================================
 * 3. BUSCA DE DADOS E VOLTAR
 * ========================================================= */
$pleito_ativo = $wpdb->get_row("SELECT * FROM {$tbl_pleitos} WHERE status_pleito = 1 LIMIT 1");
$local = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$tbl_locais} WHERE id_local = %d", $id_local));
$id_zona_voltar = ($local && $local->id_zona) ? $local->id_zona : 0;
$url_voltar = admin_url('admin.php?page=ze-legal-preencher-vagas-consulta&id_zona=' . $id_zona_voltar);

$sql = "SELECT v.*, f.num_funcao, f.nom_funcao, v.num_secao,
               c.id_colaborador, c.nom_eleitor, c.ds_status_eleitoral, c.num_cpf, 
               c.num_telefone_eleitor, l.nom_local, l.endereco as end_local, z.descricao as ds_zona, 
               z.email, z.chefe_cartorio, z.contato_1, z.contato_2, z.contato_3,
               z.endereco as end_zona 
        FROM {$tbl_vagas} v 
        JOIN {$tbl_funcoes} f ON f.id_funcao = v.id_funcao
        JOIN {$tbl_locais} l ON l.id_local = v.id_local
        JOIN {$tbl_zonas} z ON z.id_zona = l.id_zona
        LEFT JOIN {$tbl_colabs} c ON c.id_colaborador = v.id_colaborador
        WHERE v.id_pleito = %d AND v.id_local = %d 
        ORDER BY CAST(f.num_funcao AS UNSIGNED), v.num_secao";
$vagas = $wpdb->get_results($wpdb->prepare($sql, $pleito_ativo->id_pleito, $id_local));

$filtros_funcoes = []; $filtros_status = [];
foreach($vagas as $v) {
    $nome_f = $v->num_funcao . ' - ' . $v->nom_funcao;
    $filtros_funcoes[$nome_f] = $nome_f;
    $filtros_status[$v->status_vaga] = $v->status_vaga;
}
asort($filtros_funcoes); ksort($filtros_status);
?>

<style>
    :root { --ze-primary: #2563eb; --ze-border: #e2e8f0; }
    .ze-premium-wrap { margin: 20px 20px 0 0; font-family: 'Segoe UI', system-ui, sans-serif; }
    .ze-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .ze-header h1 { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; }
    .ze-filters { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--ze-border); display: flex; gap: 15px; align-items: flex-end; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .ze-filter-item { display: flex; flex-direction: column; gap: 6px; }
    .ze-filter-item label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .ze-filters input, .ze-filters select { border-radius: 8px; border: 1px solid var(--ze-border); padding: 8px 12px; font-size: 14px; background: #fcfcfd; }
    .ze-table-card { background: #fff; border-radius: 12px; border: 1px solid var(--ze-border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; }
    .ze-table { width: 100%; border-collapse: collapse; text-align: left; }
    .ze-table th { background: #f8fafc; padding: 15px; font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
    .ze-table td { padding: 16px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .status-badge { padding: 5px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; border: 1px solid transparent; }
    .badge-DISPONIVEL { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }
    .badge-PRE_SELECIONADA { background: #fffbeb; color: #92400e; border-color: #fef3c7; }
    .badge-RESERVADA { background: #eff6ff; color: #1e40af; border-color: #dbeafe; }
    .btn-wa-action { background: #25D366; color: white !important; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: bold; display: inline-flex; align-items: center; gap: 4px; }
    .btn-wa-token { background: #0ea5e9; color: white !important; padding: 6px 10px; border-radius: 6px; font-size: 11px; font-weight: bold; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
    .btn-grid-action { padding: 8px 14px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; transition: 0.2s; border: none; }
</style>

<div class="ze-admin-container">
        <a href="<?php echo esc_url($url_voltar); ?>" class="ze-btn-back">
            <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para Consulta
        </a>
    
        <div class="ze-header">
            <h1><span class="dashicons dashicons-layout" style="vertical-align: middle; margin-right: 8px;"></span> Gestão de Vagas: <?php echo esc_html($local->nom_local. ' - ' .$local->endereco); ?></h1>
        </div>

    <div class="ze-filters">
        <div class="ze-filter-item" style="flex: 1;"><label>Pesquisar Colaborador</label><input type="text" id="filter_nome" placeholder="Digite um nome..."></div>
        <div class="ze-filter-item"><label>Função</label><select id="filter_funcao" style="width: 220px;"><option value="">Todas as Funções</option><?php foreach($filtros_funcoes as $fn): ?><option value="<?php echo esc_attr($fn); ?>"><?php echo esc_html($fn); ?></option><?php endforeach; ?></select></div>
        <div class="ze-filter-item"><label>Status</label><select id="filter_status" style="width: 180px;"><option value="">Todos os Status</option><?php foreach($filtros_status as $st): ?><option value="<?php echo esc_attr($st); ?>"><?php echo esc_html($st); ?></option><?php endforeach; ?></select></div>
    </div>

    <div class="ze-table-card">
        <table class="ze-table" id="tabela_gestao_vagas">
            <thead>
                <tr>
                    <th style="width: 250px;">Função / Seção</th>
                    <th style="width: 150px;">Status Vaga</th>
                    <th>Informações do Colaborador</th>
                    <th style="text-align: right; padding-right: 20px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $vagas as $v ) : 
                    $nome_f = $v->num_funcao . ' - ' . $v->nom_funcao;
                    $nome_c = $v->nom_eleitor ? mb_strtolower($v->nom_eleitor) : '';
                ?>
                <tr class="vaga-row" data-funcao="<?php echo esc_attr($nome_f); ?>" data-status="<?php echo esc_attr($v->status_vaga); ?>" data-nome="<?php echo esc_attr($nome_c); ?>">
                    <td>
                        <div style="font-weight: 800; color: var(--ze-primary);"><?php echo esc_html($nome_f); ?></div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Seção: <strong><?php echo $v->num_secao; ?></strong></div>
                    </td>
                    <td><span class="status-badge badge-<?php echo $v->status_vaga; ?>"><?php echo str_replace('_', ' ', $v->status_vaga); ?></span></td>
                    <td>
                        <?php if ( $v->nom_eleitor ) : ?>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 14px;"><?php echo esc_html($v->nom_eleitor); ?></div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                        CPF: <?php echo $v->num_cpf; ?> | 
                                        <span style="font-weight: 800; color: #1e293b;"><?php echo ze_telefone_mascara($v->num_telefone_eleitor); ?></span>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 6px;">
                                    <?php 
                                    $fone_raw = preg_replace('/\D/', '', $v->num_telefone_eleitor);
                                    if(!empty($fone_raw)):
                                        $fone_raw = (strlen($fone_raw) <= 11) ? "55" . $fone_raw : $fone_raw;
                                        $msg_convite ="Olá, *" . mb_strtoupper($v->nom_eleitor) . "*! \n\n" .
                                                        
                                                        "A *" . $v->ds_zona . "* iniciou o processo de contato para verificar a *disponibilidade de colaboradoras(es)* interessados em compor a sua equipe eleitoral.\n\n" .
                                                        
                                                        "Você foi *pré-selecionado(a)* para exercer a função de *" . $v->nom_funcao . "* Seção: *" . $v->num_secao . "* no(a): *" . $v->nom_local . "* situado no(a) *" . $v->end_local . "*\n\n" .
                                                        
                                                        "Solicitamos que o contato seja realizado *em até 3 (três) dias*, de forma *exclusiva*, diretamente com a *Zona Eleitoral*, por um dos *canais oficiais* abaixo.\n\n" .
                                                        
                                                        "*Contatos Oficiais da Zona Eleitoral*\n" .
                                                        "• Zona: " . $v->ds_zona . "\n" .
                                                        "• Chefe de Cartório: " . ($v->chefe_cartorio ?: '—') . "\n" .
                                                        "• E-mail: " . ($v->email ?: '—') . "\n" .
                                                        "• Telefones: " . ($v->contato_1 ? ze_telefone_mascara($v->contato_1) : '—' ) . " - " . ($v->contato_2? ze_telefone_mascara($v->contato_2) : '—') . "\n" .
                                                        "• WhatsApp: *" . ($v->contato_3 ? ze_telefone_mascara($v->contato_3) : '—' ) . "*\n" .
                                                        "• Endereço: " . ($v->end_zona ?: '—') . "\n\n" .
                                                        
                                                        "️ *Importante*\n" .
                                                        "Os contatos oficiais da Zona Eleitoral podem ser confirmados no site do Tribunal Regional Eleitoral de Rondônia:\n" .
                                                        " www.tre-ro.jus.br\n\n" .
                                                        
                                                        "Após o contato, aguarde as orientações da equipe do Cartório Eleitoral.\n\n" .
                                                        
                                                        "Agradecemos desde já a sua atenção e colaboração. ";
                                    ?>
                                        <a href="https://wa.me/<?php echo $fone_raw; ?>?text=<?php echo urlencode($msg_convite); ?>" target="_blank" class="btn-wa-action"><span class="dashicons dashicons-whatsapp"></span> Convite</a>

                                        <?php if ( $v->status_vaga !== 'PRE_SELECIONADO' ) : ?>
                                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" target="_blank" style="margin:0;">
                                                <input type="hidden" name="action" value="ze_enviar_token">
                                                <input type="hidden" name="id_colaborador" value="<?php echo $v->id_colaborador; ?>">
                                                <?php wp_nonce_field('ze_token_action_' . $v->id_colaborador); ?>
        
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else : ?>
                            <span style="color: #cbd5e1; font-style: italic; font-size: 13px;">Vaga disponível</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <?php 
                            // Lógica de visibilidade dos botões baseada no status
                            if ( $v->status_vaga === 'DISPONIVEL' || $v->status_vaga === 'PRE_SELECIONADO' ) : ?>
                                
                                <?php if ( $v->status_vaga === 'PRE_SELECIONADO' ) : ?>
                                    <form method="post" style="margin:0;">
                                        <?php wp_nonce_field('ze_vagas_acoes_lista'); ?>
                                        <input type="hidden" name="id_vaga_pleito" value="<?php echo $v->id_vaga_pleito; ?>">
                                        <input type="hidden" name="id_colaborador" value="<?php echo $v->id_colaborador; ?>">
                                        <button type="submit" name="acao" value="reservar" class="btn-grid-action" style="background: var(--ze-primary); color: #fff;">📅 Reservar</button>
                                        <button type="submit" name="acao" value="esvaziar_vaga" class="btn-grid-action" style="background: #fef2f2; color: #dc2626;" onclick="return confirm('Esvaziar vaga?');"><span class="dashicons dashicons-trash"></span></button>
                                    </form>
                                <?php else : ?>
                                    <a href="<?php echo admin_url('admin.php?page=ze-legal-preencher-vagas-selecionar&id_vaga_pleito='.$v->id_vaga_pleito); ?>" class="btn-grid-action" style="background: #fff; border: 1px solid var(--ze-border); color: var(--ze-text); text-decoration: none;">🔍 Selecionar</a>
                                <?php endif; ?>
                    
                            <?php else : 
                                // Para qualquer outro status (RESERVADO, CONVOCADO, etc), mostra Gerenciar
                                $id_conv_link = $wpdb->get_var($wpdb->prepare("SELECT id_convocacao FROM {$tbl_conv} WHERE id_vaga_pleito = %d AND status_convocacao != 'DISPENSADO' ORDER BY id_convocacao DESC LIMIT 1", $v->id_vaga_pleito));
                            ?>
                                <a href="<?= admin_url('admin.php?page=ze-legal-convocacao-cartorio-gerenciar&id=' . $id_conv_link) ?>" class="btn-grid-action" style="background: #f1f5f9; color: #475569; text-decoration: none;">⚙️ Gerenciar</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    const filterNome = document.getElementById('filter_nome');
    const filterFuncao = document.getElementById('filter_funcao');
    const filterStatus = document.getElementById('filter_status');
    const rows = document.querySelectorAll('.vaga-row');
    function aplicarFiltros() {
        const nomeVal = filterNome.value.toLowerCase();
        const funcaoVal = filterFuncao.value;
        const statusVal = filterStatus.value;
        rows.forEach(row => {
            const matchNome = !nomeVal || row.getAttribute('data-nome').includes(nomeVal);
            const matchFuncao = !funcaoVal || row.getAttribute('data-funcao') === funcaoVal;
            const matchStatus = !statusVal || row.getAttribute('data-status') === statusVal;
            row.style.display = (matchNome && matchFuncao && matchStatus) ? '' : 'none';
        });
    }
    filterNome.addEventListener('input', aplicarFiltros);
    filterFuncao.addEventListener('change', aplicarFiltros);
    filterStatus.addEventListener('change', aplicarFiltros);
})();
</script>