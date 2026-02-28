<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

/* =========================
   TABELAS
========================= */
$tbl_pleitos  = "{$prefix}tb_pleitos";
$tbl_vagas    = "{$prefix}tb_vagas_pleitos";
$tbl_locais   = "{$prefix}tb_locais";
$tbl_funcoes  = "{$prefix}tb_funcoes";
$tbl_municipios  = "{$prefix}tb_municipios";
$tbl_colabs   = "{$prefix}tb_colaboradores";
$tbl_log_vaga = "{$prefix}tb_log_vagas_pleitos";

/* =========================
   PARÂMETRO
========================= */
$id_vaga_pleito = isset($_GET['id_vaga_pleito']) ? intval($_GET['id_vaga_pleito']) : 0;

if ( ! $id_vaga_pleito ) {
    echo '<div class="notice notice-error"><p>Vaga não informada.</p></div>';
    return;
}

/* =========================
   BUSCAR VAGA COMPLETA + COLAB ATUAL
========================= */
$vaga = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT
            v.*, p.ano AS ano_pleito, p.status_pleito, l.nom_local, l.num_local, f.num_funcao, f.nom_funcao,
            c.nom_eleitor, c.ds_status_eleitoral, c.num_cpf, c.num_inscricao, c.num_telefone_eleitor, 
            c.num_telefone_eleitor_2, c.num_zona_votacao, c.num_secao_votacao, c.num_local_votacao, c.id_upload_foto, c.nom_municipio_votacao, m.nom_municipio as municipio_local
         FROM {$tbl_vagas} v
         JOIN {$tbl_pleitos} p ON p.id_pleito = v.id_pleito
         JOIN {$tbl_locais}  l ON l.id_local  = v.id_local
         JOIN {$tbl_funcoes} f ON f.id_funcao = v.id_funcao
         JOIN {$tbl_municipios} m ON m.id_municipio = l.id_municipio
         LEFT JOIN {$tbl_colabs} c ON c.id_colaborador = v.id_colaborador
         WHERE v.id_vaga_pleito = %d",
        $id_vaga_pleito
    )
);

if ( ! $vaga || $vaga->status_pleito !== '1' ) {
    echo '<div class="notice notice-warning"><p>Vaga inválida ou pleito inativo.</p></div>';
    return;
}

/* =========================
   AÇÃO: PRÉ-SELECIONAR / SUBSTITUIR (POST)
========================= */
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'pre_selecionar_imediato' ) {
    check_admin_referer('ze_pre_selecionar_colab');
    
    $agora         = current_time('mysql');
    $usuario       = get_current_user_id();
    $id_colab_novo = intval($_POST['id_colaborador']);
    $id_colab_ant  = $vaga->id_colaborador ? intval($vaga->id_colaborador) : null;

    // 1. Se houver colaborador antigo, volta o status dele para DISPONÍVEL
    if ( $id_colab_ant ) {
        $wpdb->update($tbl_colabs, 
            ['ds_status_eleitoral' => 'DISPONIVEL', 'updated_at' => $agora], 
            ['id_colaborador' => $id_colab_ant]
        );
    }

    // 2. Atualiza a Vaga com o Novo
    $wpdb->update($tbl_vagas, [
        'id_colaborador' => $id_colab_novo,
        'status_vaga'    => 'PRE_SELECIONADO',
        'dt_designacao'  => $agora,
        'updated_at'     => $agora
    ], [ 'id_vaga_pleito' => $vaga->id_vaga_pleito ]);

    // 3. Atualiza status do Novo Colaborador
    $wpdb->update($tbl_colabs, [
        'ds_status_eleitoral' => 'PRE_SELECIONADO',
        'updated_at' => $agora
    ], [ 'id_colaborador' => $id_colab_novo ]);

    // 4. Grava Log
    $wpdb->insert($tbl_log_vaga, [
        'id_vaga_pleito'          => $vaga->id_vaga_pleito,
        'status_anterior'         => $vaga->status_vaga,
        'status_novo'             => 'PRE_SELECIONADO',
        'id_colaborador_anterior' => $id_colab_ant,
        'id_colaborador_novo'     => $id_colab_novo,
        'motivo'                  => $id_colab_ant ? 'Substituição direta via busca' : 'Pré-seleção direta via busca',
        'id_usuario_responsavel'  => $usuario,
        'data_evento'             => $agora
    ]);
    wp_safe_redirect( add_query_arg( 'id_vaga_pleito', $vaga->id_vaga_pleito, admin_url('admin.php?page=ze-legal-preencher-vagas-selecionar') ) );

    /**
     * SINCRONIZA colaborador da vaga para eventos futuros
     */
    require_once ZE_LEGAL_PATH . 'domain/vagas/sincroniza-eventos.php';
    exit;
}

/* =========================
   BUSCA COLABORADORES
========================= */
$busca = isset($_GET['busca']) ? trim( sanitize_text_field($_GET['busca']) ) : '';
$where = " AND ds_status_eleitoral = 'DISPONIVEL' ";
$params = [];

if ( $busca !== '' ) {
    $like = '%' . $wpdb->esc_like( $busca ) . '%';
    $where .= " AND ( nom_eleitor LIKE %s OR num_cpf LIKE %s OR num_local_votacao LIKE %s OR num_inscricao LIKE %s OR ds_experiencia LIKE %s )";
    $params = [$like, $like, $like, $like, $like];
}

$sql_colabs = "SELECT * FROM {$tbl_colabs} WHERE 1=1 {$where} ORDER BY nom_eleitor LIMIT 30";
$colaboradores = $params ? $wpdb->get_results( $wpdb->prepare($sql_colabs, $params) ) : $wpdb->get_results( $sql_colabs );

$url_voltar = admin_url('admin.php?page=ze-legal-preencher-vagas-local&id_local=' . $vaga->id_local);
?>

<div class="ze-admin-container">
    <a href="<?php echo esc_url($url_voltar); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt" style="vertical-align: middle; font-size: 18px;"></span> 
        Voltar para Vagas
    </a>
    
     <h1 class="ze-page-title">Selecionar Colaboradores</h1>
    
    

    <hr class="wp-header-end">

    <div style="margin:16px 0;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        
        <div style="padding:16px; border-radius:14px; background:linear-gradient(135deg,#eff6ff,#ffffff); border:2px solid #bfdbfe;">
            <h3 style="margin:0 0 10px;color:#1e3a8a;">📌 Vaga</h3>
            <div style="font-size:15px;font-weight:900;color:#1e40af;"><?php echo esc_html($vaga->num_funcao.' — '.$vaga->nom_funcao); ?></div>
            <div style="margin-top:6px;font-size:13px;color:#374151;">📍 <?php echo esc_html($vaga->nom_local.' · '.$vaga->num_local.' · '.$vaga->municipio_local); ?></div>
            <div style="margin-top:6px;font-size:13px;"><strong>Seção:</strong> <span style="font-weight:900;"><?php echo esc_html($vaga->num_secao ?: '-'); ?></span></div>
            <div style="margin-top:10px;"><span style="padding:5px 12px; border-radius:999px; background:#dbeafe; color:#1e40af; font-weight:900; font-size:12px;"><?php echo esc_html($vaga->status_vaga); ?></span></div>
        </div>
    
        <div style="padding:16px; border-radius:14px; background:#ffffff; border:2px solid #e5e7eb;">
            <h3 style="margin:0 0 10px;color:#374151;">👤 Colaborador Atual</h3>
            <?php if ( $vaga->id_colaborador ) : ?>
                <div style="font-size:15px;font-weight:900;color:#111827;"><?php echo esc_html($vaga->nom_eleitor); ?></div>
                <div style="margin-top:6px;font-size:12px;color:#374151;">
                    CPF: <?php echo esc_html($vaga->num_cpf); ?><br>
                    Inscrição: <?php echo esc_html($vaga->num_inscricao); ?>
                </div>

                <?php if($vaga->num_telefone_eleitor || $vaga->num_telefone_eleitor_2): ?>
                <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
                    <?php if($vaga->num_telefone_eleitor): ?>
                        <span style="font-size:11px; background:#f0f9ff; color:#0369a1; padding:4px 8px; border-radius:6px; border:1px solid #bae6fd; font-weight:bold;">
                            📞 <?php echo esc_html($vaga->num_telefone_eleitor); ?>
                        </span>
                    <?php endif; ?>
                    <?php if($vaga->num_telefone_eleitor_2): ?>
                        <span style="font-size:11px; background:#f0f9ff; color:#0369a1; padding:4px 8px; border-radius:6px; border:1px solid #bae6fd; font-weight:bold;">
                            📞 <?php echo esc_html($vaga->num_telefone_eleitor_2); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div style="margin-top:8px;">
                    <span style="padding:5px 12px; border-radius:999px; background:#fee2e2; color:#991b1b; font-weight:900; font-size:12px;">
                        <?php echo esc_html($vaga->ds_status_eleitoral); ?>
                    </span>
                </div>
            <?php else : ?>
                <div style="color:#6b7280;font-size:13px;">Nenhum colaborador associado.</div>
            <?php endif; ?>
        </div>
    </div>

    <form method="get" style="margin-bottom:16px;display:flex;gap:10px;">
        <input type="hidden" name="page" value="ze-legal-preencher-vagas-selecionar">
        <input type="hidden" name="id_vaga_pleito" value="<?php echo esc_attr($id_vaga_pleito); ?>">
        <input type="text" name="busca" value="<?php echo esc_attr($busca); ?>" placeholder="Buscar por Nome, CPF, Título..." style="flex:1;padding:10px;border-radius:10px; border:1px solid #ccc;">
        <button class="button button-primary" style="height: auto; padding: 0 20px;">Buscar</button>
    </form>

    <?php foreach ( $colaboradores as $c ) : 
        $eh_mesmo_local = ( trim($c->num_local_votacao) == trim($vaga->num_local) );
        $card_border  = $eh_mesmo_local ? '#bbf7d0' : '#e5e7eb';
        
        // Lógica da Foto / Avatar
        $foto = '';
        if ( $c->id_upload_foto ) {
            $img = wp_get_attachment_image_src( $c->id_upload_foto, 'thumbnail' );
            if ( $img ) $foto = '<img src="'.$img[0].'" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #fff;box-shadow:0 2px 5px rgba(0,0,0,0.1);">';
        }
        if ( ! $foto ) {
            $avatar_bg = $eh_mesmo_local ? '#dcfce7' : '#eff6ff';
            $avatar_tx = $eh_mesmo_local ? '#166534' : '#1e40af';
            $foto = '<div style="width:64px;height:64px;border-radius:50%; background:'.$avatar_bg.'; color:'.$avatar_tx.'; display:flex;align-items:center;justify-content:center;font-weight:900;font-size:24px;border:1px solid '.$card_border.';">'.esc_html(mb_strtoupper(mb_substr($c->nom_eleitor,0,1))).'</div>';
        }
    ?>
        <div style="padding:20px; border-radius:12px; background:#fff; border:1px solid <?php echo $card_border; ?>; box-shadow:0 2px 4px rgba(0,0,0,.04); margin-bottom:16px; position:relative; display:flex; align-items:flex-start; gap:20px;">
            
            <div style="flex-shrink:0;">
                <?php echo $foto; ?>
            </div>

            <div style="flex-grow:1;">
                
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                    <div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-weight:900; font-size:17px; color:#1e293b; text-transform:uppercase; letter-spacing:-0.5px;"><?php echo esc_html($c->nom_eleitor); ?></span>
                            <?php if($eh_mesmo_local): ?>
                                <span style="background:#166534; color:#fff; font-size:9px; padding:2px 10px; border-radius:999px; font-weight:bold; text-transform:uppercase;">📍 Mesmo Local</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:13px; color:#64748b; margin-top:2px;">
                            <span style="font-weight:600;">CPF:</span> <?php echo esc_html($c->num_cpf); ?> <span style="margin:0 8px; color:#cbd5e1;">|</span> 
                            <span style="font-weight:600;">Inscrição:</span> <?php echo esc_html($c->num_inscricao); ?>
                        </div>
                    </div>

                    <form method="post" onsubmit="return confirm('Confirmar ação para este colaborador?');" style="margin:0;">
                        <?php wp_nonce_field('ze_pre_selecionar_colab'); ?>
                        <input type="hidden" name="acao" value="pre_selecionar_imediato">
                        <input type="hidden" name="id_colaborador" value="<?php echo $c->id_colaborador; ?>">
                        
                        <?php if ( $vaga->id_colaborador ) : ?>
                            <button type="submit" class="button" style="background:#f59e0b; color:#fff; border:none; font-weight:bold; padding:8px 16px; border-radius:8px; display:flex; align-items:center; gap:5px; transition:0.2s;">
                                <span class="dashicons dashicons-update" style="font-size:18px; width:18px; height:18px;"></span> Substituir
                            </button>
                        <?php else : ?>
                            <button type="submit" class="button" style="background:<?php echo $eh_mesmo_local ? '#166534' : '#2271b1'; ?>; color:#fff; border:none; font-weight:bold; padding:8px 16px; border-radius:8px; display:flex; align-items:center; gap:5px;">
                                <span class="dashicons dashicons-search" style="font-size:18px; width:18px; height:18px;"></span> Pré-Selecionar
                            </button>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="display:flex; align-items:center; gap:20px; margin-bottom:12px; flex-wrap:wrap;">
                    <?php if($c->num_telefone_eleitor): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/\D/','',$c->num_telefone_eleitor); ?>" target="_blank" style="text-decoration:none; display:flex; align-items:center; gap:5px; font-size:13px; color:#0369a1; background:#f0f9ff; padding:4px 10px; border-radius:6px; border:1px solid #bae6fd; font-weight:600;">
                        <span class="dashicons dashicons-whatsapp" style="font-size:16px; width:16px; height:16px;"></span> <?php echo esc_html($c->num_telefone_eleitor); ?>
                    </a>
                    <?php endif; ?>

                    <div style="font-size:13px; color:#475569;">
                        Zona: <strong style="color:#1e293b;"><?php echo esc_html($c->num_zona_votacao); ?></strong> · 
                        Local: <strong style="color:#1e293b;"><?php echo esc_html($c->num_local_votacao); ?></strong> · 
                        Seção: <strong style="color:#1e293b;"><?php echo esc_html($c->num_secao_votacao); ?></strong> .
                        Municipio: <strong style="color:#1e293b;"><?php echo esc_html($c->nom_municipio_votacao); ?></strong>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="font-size:10px; text-transform:uppercase; padding:3px 10px; border-radius:6px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; font-weight:800; letter-spacing:0.5px;">
                        <?php echo esc_html($c->ds_status_eleitoral); ?>
                    </span>

                    <?php if ( $c->ds_experiencia ) : ?>
                        <div style="font-size:12px; color:#64748b; font-style:italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 400px;" title="<?php echo esc_attr($c->ds_experiencia); ?>">
                            <span class="dashicons dashicons-media-text" style="font-size:14px; vertical-align:middle;"></span> <?php echo esc_html($c->ds_experiencia); ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php endforeach; ?>