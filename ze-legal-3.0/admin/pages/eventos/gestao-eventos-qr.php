<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$prefix = $wpdb->prefix;
$vw_eventos = $prefix . 'ze_vw_eventos_vagas_locais';
$table_eventos = $prefix . 'ze_tb_eventos_vagas';

/* ==========================================================
   AÇÃO: GERAR CÓDIGOS DE ACESSO
========================================================== */
if (isset($_POST['acao_gerar_qr'])) {
    check_admin_referer('ze_gerar_qr_action');
    $data_sel = sanitize_text_field($_POST['data_evento']);
    
    $cod_manha = strtoupper(substr(md5($data_sel . 'AM' . uniqid()), 0, 6));
    $cod_tarde = strtoupper(substr(md5($data_sel . 'PM' . uniqid()), 0, 6));

    $wpdb->query($wpdb->prepare("UPDATE $table_eventos SET codigo_acesso = %s WHERE data_evento = %s AND hora_inicio < '12:00:00'", $cod_manha, $data_sel));
    $wpdb->query($wpdb->prepare("UPDATE $table_eventos SET codigo_acesso = %s WHERE data_evento = %s AND hora_inicio >= '12:00:00'", $cod_tarde, $data_sel));

    echo '<div class="updated"><p>Códigos QR gerados com sucesso!</p></div>';
}

// Busca datas e verifica turnos existentes
$datas_eventos = $wpdb->get_results("
    SELECT 
        data_evento, 
        COUNT(CASE WHEN hora_inicio < '12:00:00' THEN 1 END) as tem_manha,
        COUNT(CASE WHEN hora_inicio >= '12:00:00' THEN 1 END) as tem_tarde
    FROM $vw_eventos 
    GROUP BY data_evento 
    ORDER BY data_evento DESC
");
?>

<div class="wrap">
    <h1>Gerador de Cartazes QR Code</h1>
    <div class="ze-qr-container" style="margin-top:20px;">
        <?php foreach ($datas_eventos as $d): 
            // Verifica se já tem código gerado para o dia
            $codigo_existe = $wpdb->get_var($wpdb->prepare("SELECT codigo_acesso FROM $table_eventos WHERE data_evento = %s AND codigo_acesso IS NOT NULL LIMIT 1", $d->data_evento));
        ?>
            <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:8px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong style="font-size:16px;">📅 <?= date('d/m/Y', strtotime($d->data_evento)) ?></strong>
                </div>

                <div style="display:flex; gap:10px;">
                    <?php if (!$codigo_existe): ?>
                        <form method="post">
                            <?php wp_nonce_field('ze_gerar_qr_action'); ?>
                            <input type="hidden" name="data_evento" value="<?= esc_attr($d->data_evento) ?>">
                            <button type="submit" name="acao_gerar_qr" class="button button-primary">
                                Gerar Chaves de Acesso
                            </button>
                        </form>
                    <?php else: ?>
                    
                        <?php
                        $base_qr_url = admin_url(
                            'admin-post.php?action=ze_imprimir_qr&data=' . urlencode($d->data_evento)
                        );
                        ?>
                    
                        <?php if ($d->tem_manha > 0): ?>
                            <a href="<?= $base_qr_url . '&turno=AM' ?>" target="_blank" class="button">
                                🖨️ Cartaz Manhã
                            </a>
                        <?php endif; ?>
                    
                        <?php if ($d->tem_tarde > 0): ?>
                            <a href="<?= $base_qr_url . '&turno=PM' ?>" target="_blank" class="button">
                                🖨️ Cartaz Tarde
                            </a>
                        <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>