<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Gera o PDF do cartaz de presença com QR Code em uma única página A4
 */
function ze_legal_gerar_qr_cartaz_pdf() {
    global $wpdb;

    if ( ! current_user_can('ze_cadastro_adm_cartorio') ) {
        wp_die('Acesso não autorizado.');
    }
    
    $data_sel = isset($_GET['data']) ? sanitize_text_field($_GET['data']) : '';
    $turno    = isset($_GET['turno']) ? sanitize_text_field($_GET['turno']) : '';

    if ( empty($data_sel) || ! in_array($turno, ['AM', 'PM'], true) ) {
        wp_die('Parâmetros inválidos.');
    }

    $prefix = $wpdb->prefix;
    $sql_filtro = ($turno === 'AM') ? "AND hora_inicio < '12:00:00'" : "AND hora_inicio >= '12:00:00'";

    $codigo = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT codigo_acesso
             FROM {$prefix}ze_tb_eventos_vagas
             WHERE data_evento = %s
               {$sql_filtro}
               AND codigo_acesso IS NOT NULL
             LIMIT 1",
            $data_sel
        )
    );

    if ( empty($codigo) ) {
        wp_die('Chave de acesso não encontrada para este turno.');
    }

    $url_registro = home_url('registrar-presenca/?token=' . urlencode($codigo));
    $qr_api_url   = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($url_registro);


    ob_start();
    
    
    ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            text-align: center;
            padding: 20px; /* Reduzido de 40px */
            color: #1a237e;
            height: 100%;
        }
        .header { font-size: 28pt; font-weight: bold; margin-top: 10px; }
        .subheader { font-size: 18pt; color: #555; margin-bottom: 20px; }
        
        .main-instruction {
            font-size: 22pt; /* Reduzido de 26pt */
            font-weight: bold;
            margin: 20px 0;
            line-height: 1.1;
        }

        .qr-box {
            margin: 0 auto;
            width: 360px; /* Reduzido de 480px */
            padding: 20px;
            border: 15px solid #1a237e; /* Reduzido de 20px */
            border-radius: 30px;
        }

        .details {
            font-size: 13pt;
            color: #444;
            margin-top: 30px; /* Reduzido de 60px */
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        small { font-size: 10pt; color: #888; }
    </style>
</head>
<body>

    <div class="header">JUSTIÇA ELEITORAL</div>
    <div class="subheader">SISTEMA DE PRESENÇA DIGITAL</div>

    <div class="main-instruction">
        APONTE A CÂMERA DO CELULAR<br>
        PARA O CÓDIGO ABAIXO:
    </div>

    <div class="qr-box">
        
        <img src="<?= esc_url($qr_api_url) ?>" style="width:100%;" alt="QR Code">
    </div>

    <div class="main-instruction">
        E REGISTRE SUA PRESENÇA
    </div>

    <div class="details">
        <strong>DATA DO EVENTO:</strong>
        <?= esc_html( date('d/m/Y', strtotime($data_sel)) ) ?>
        &nbsp;|&nbsp;
        <strong>TURNO:</strong>
        <?= esc_html( $turno === 'AM' ? 'MANHÃ' : 'TARDE' ) ?>
        <br>
        <small>Código de Segurança: <?= esc_html($codigo) ?></small>
    </div>

</body>
</html>
    <?php
    $html = ob_get_clean();

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('chroot', ABSPATH);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream(
        "Cartaz_Presenca_{$data_sel}_{$turno}.pdf",
        ['Attachment' => false]
    );
    exit;
}
add_action('admin_post_ze_imprimir_qr', 'ze_legal_gerar_qr_cartaz_pdf');