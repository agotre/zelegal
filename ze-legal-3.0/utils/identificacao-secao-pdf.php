<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('ze_cadastro_adm_cartorio')) {
    wp_die('Acesso não autorizado.');
}

if (!isset($_GET['id_zona']) || empty($_GET['id_zona'])) {
    wp_die('Zona não informada.');
}

global $wpdb;

$id_zona = intval($_GET['id_zona']);
$view = $wpdb->prefix . 'ze_vw_secao_vagas_locais_colaboradores';

$secoes = $wpdb->get_results($wpdb->prepare("
    SELECT DISTINCT 
        id_local,
        num_secao,
        nom_local,
        nom_municipio,
        ds_zona,
        descricao, -- Usado para o ano/pleito se disponível
        num_secao_agregada1,
        num_secao_agregada2,
        num_secao_agregada3,
        num_secao_agregada4
    FROM {$view}
    WHERE id_zona = %d AND num_secao > 0000
    ORDER BY nom_local ASC, num_secao ASC
", $id_zona));

if (!$secoes) {
    wp_die('Nenhuma seção encontrada.');
}

require_once ZE_LEGAL_PATH . 'vendor/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);

// CSS de Layout Premium
$css = "
<style>
    @page { margin: 0; }
    body { margin: 0; padding: 0; font-family: 'Helvetica', sans-serif; background-color: #fff; }
    
    .page-container {
        position: relative;
        width: 100%;
        height: 100%;
        page-break-after: always;
        display: block;
    }

    /* Moldura Arredondada */
    .border-frame {
        position: absolute;
        top: 20px;
        left: 20px;
        right: 20px;
        bottom: 20px;
        border: 4px solid #1a2a6c; /* Azul Institucional */
        border-radius: 30px;
        padding: 40px;
        text-align: center;
    }

    .header-info {
        border-bottom: 2px solid #eee;
        padding-bottom: 20px;
        margin-bottom: 30px;
    }

    .zona-pleito {
        font-size: 26pt;
        color: #555;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .local-nome {
        font-size: 22pt;
        font-weight: bold;
        color: #000;
        line-height: 1.2;
    }

    .municipio-label {
        font-size: 16pt;
        color: #666;
        font-weight: normal;
    }

    .main-section {
        margin-top: 50px;
    }

    .section-label {
        font-size: 20pt;
        color: #1a2a6c;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: -20px;
    }

    .section-number {
        font-size: 240pt;
        font-weight: 900;
        color: #000;
        margin: 0;
        letter-spacing: -5px;
    }

    .agregadas-box {
        position: absolute;
        bottom: 60px;
        left: 0;
        right: 0;
        text-align: center;
    }

    .agregadas-title {
        font-size: 18pt;
        color: #d32f2f; /* Vermelho suave para destaque */
        font-weight: bold;
        margin-bottom: 10px;
    }

    .agregadas-list {
        font-size: 45pt;
        font-weight: bold;
        color: #333;
    }
</style>
";

$html = $css;

foreach ($secoes as $secao) {
    $agregadas = [];
    if (!empty($secao->num_secao_agregada1)) $agregadas[] = $secao->num_secao_agregada1;
    if (!empty($secao->num_secao_agregada2)) $agregadas[] = $secao->num_secao_agregada2;
    if (!empty($secao->num_secao_agregada3)) $agregadas[] = $secao->num_secao_agregada3;
    if (!empty($secao->num_secao_agregada4)) $agregadas[] = $secao->num_secao_agregada4;

    $agregadas_html = '';
    if (!empty($agregadas)) {
        $agregadas_html = '
        <div class="agregadas-box">
            <div class="agregadas-title">SEÇÕES AGREGADAS</div>
            <div class="agregadas-list">' . implode(' &bull; ', $agregadas) . '</div>
        </div>';
    }

    $html .= '
    <div class="page-container">
        <div class="border-frame">
            <div class="header-info">
                <div class="zona-pleito">' . esc_html($secao->ds_zona) . '</div>
                <div class="local-nome">
                    ' . esc_html($secao->nom_local) . '<br>
                    <span class="municipio-label">' . esc_html($secao->nom_municipio) . '</span>
                </div>
            </div>

            <div class="main-section">
                <div class="section-label">SEÇÃO</div>
                <h1 class="section-number">' . esc_html($secao->num_secao) . '</h1>
            </div>

            ' . $agregadas_html . '
        </div>
    </div>';
}

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$dompdf->stream('identificacao-secoes-' . $id_zona . '.pdf', ['Attachment' => false]);
exit;