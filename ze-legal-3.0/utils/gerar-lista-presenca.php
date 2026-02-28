<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

function ze_legal_gerar_lista_assinatura_pdf() {

    if ( ! current_user_can('read') ) {
        wp_die('Acesso não autorizado.');
    }

    global $wpdb;
    $prefix = $wpdb->prefix;
    $vw_eventos = $prefix . 'ze_vw_eventos_vagas_locais';

    // =========================
    // Filtros com validação
    // =========================
    $data_sel = isset($_GET['data']) ? sanitize_text_field($_GET['data']) : '';
    $turno    = isset($_GET['turno']) ? sanitize_text_field($_GET['turno']) : '';
    $id_local = isset($_GET['local']) ? intval($_GET['local']) : 0;

    if ( empty($data_sel) ) {
        wp_die('Data inválida.');
    }

    // =========================
    // Montagem segura do WHERE
    // =========================
    $where   = [
                "data_evento = %s",
                "nom_eleitor IS NOT NULL",
                "nom_eleitor != ''",
                "num_inscricao IS NOT NULL",
                "num_inscricao != ''"
            ];
    $params  = [$data_sel];

    if ($turno === 'AM') {
        $where[]  = "hora_inicio < %s";
        $params[] = '12:00:00';
    }

    if ($turno === 'PM') {
        $where[]  = "hora_inicio >= %s";
        $params[] = '12:00:00';
    }

    if ($id_local > 0) {
        $where[]  = "id_local = %d";
        $params[] = $id_local;
    }

    $sql = "SELECT * FROM {$vw_eventos} 
            WHERE " . implode(' AND ', $where) . "
            ORDER BY nom_local ASC, nom_eleitor ASC";

    $sql = $wpdb->prepare($sql, $params);

    $resultados = $wpdb->get_results($sql);

    if ( empty($resultados) ) {
        wp_die('Nenhum resultado encontrado.');
    }

    // =========================
    // Agrupar por Local
    // =========================
    $agrupados = [];
    foreach ($resultados as $r) {
        $agrupados[$r->nom_local][] = $r;
    }

    ob_start();
    ?>
    <style>
        @page { margin: 1cm; }
        .page-break { page-break-after: always; }
        body { font-family: Helvetica, sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background: #eee; font-size: 8pt; }
        .header { text-align: center; font-weight: bold; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
    </style>

    <?php foreach ($agrupados as $nome_local => $colaboradores): ?>
        <div class="header">
            LISTA DE PRESENÇA - JUSTIÇA ELEITORAL<br>
            LOCAL: <?php echo esc_html($nome_local); ?> |
            DATA: <?php echo esc_html( date('d/m/Y', strtotime($data_sel)) ); ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="120">Inscrição (Cód. Barras)</th>
                    <th>Nome do Colaborador</th>
                    <th width="100">Função</th>
                    <th width="150">Assinatura</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($colaboradores as $c):

                    $barcode_url = "https://bwipjs-api.metafloor.com/?bcid=code128&text="
                        . urlencode($c->num_inscricao)
                        . "&scale=1&includetext=true";
                ?>
                    <tr>
                        <td style="text-align:center;">
                            <img src="<?php echo esc_url($barcode_url); ?>" style="height:30px; width:100px;">
                        </td>
                        <td><?php echo esc_html($c->nom_eleitor); ?></td>
                        <td style="font-size:8pt;"><?php echo esc_html($c->nom_funcao); ?></td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="page-break"></div>

    <?php endforeach; ?>

    <?php
    $html = ob_get_clean();

    // =========================
    // DOMPDF
    // =========================
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('chroot', ABSPATH);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $arquivo_nome = 'Cartaz_Presenca_' . sanitize_file_name($data_sel . '_' . $turno) . '.pdf';

    $dompdf->stream(
        $arquivo_nome,
        ['Attachment' => false]
    );

    exit;
}

// =========================
// Hook correto fora da função
// =========================
add_action(
    'admin_post_ze_gerar_lista_assinatura_pdf',
    'ze_legal_gerar_lista_assinatura_pdf'
);