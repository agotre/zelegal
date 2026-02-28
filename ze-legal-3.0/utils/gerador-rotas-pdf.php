<?php
if (!defined('ABSPATH')) exit;

use Dompdf\Dompdf;
use Dompdf\Options;

global $wpdb;
$prefix = $wpdb->prefix;

// Definição das tabelas
$table_rotas          = $prefix . 'ze_tb_rotas';
$table_rotas_destinos = $prefix . 'ze_tb_rotas_destinos';
$table_locais         = $prefix . 'ze_tb_locais';
$table_vagas          = $prefix . 'ze_tb_vagas_pleitos'; 
$table_municipios     = $prefix . 'ze_tb_municipios'; 
$table_funcoes        = $prefix . 'ze_tb_funcoes';
$table_colaboradores  = $prefix . 'ze_tb_colaboradores'; 
$vw_secoes            = $prefix . 'ze_vw_numero_secoes';
$table_tipos_locais   = $prefix . 'ze_tb_tipos_locais';

// 1. Busca Segura do Pleito Ativo
$id_pleito_ativo = $wpdb->get_var($wpdb->prepare(
    "SELECT id_pleito FROM {$prefix}ze_tb_pleitos WHERE status_pleito = %d LIMIT 1", 
    1
));

if (!$id_pleito_ativo) wp_die("Nenhum pleito ativo encontrado.");

// 2. Busca de Rotas
$rotas = $wpdb->get_results($wpdb->prepare("
    SELECT r.*, l.nom_local as local_origem, l.id_local as id_origem
    FROM {$table_rotas} r
    LEFT JOIN {$table_locais} l ON r.id_local = l.id_local
    WHERE r.id_pleito = %d ORDER BY r.ds_rota", $id_pleito_ativo));

ob_start();
?>
<style>
    @page { margin: 1cm; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; line-height: 1.2; }
    .rota-page { page-break-after: always; }
    
    /* Previne que um destino seja cortado entre duas páginas */
    .destino-container { page-break-inside: avoid; border: 1px solid #888; margin-bottom: 15px; width: 100%; background: #fff; }
    
    .header-table { width: 100%; border-bottom: 3px solid #000; margin-bottom: 15px; }
    .header-table td { padding: 5px; vertical-align: bottom; }
    .rota-nome { font-size: 20px; font-weight: bold; color: #000; }
    
    .summary-box { background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin-bottom: 15px; }
    .total-urnas { font-size: 18px; font-weight: bold; color: #d32f2f; }

    .section-title { background: #333; color: #fff; padding: 7px; font-size: 11px; font-weight: bold; margin-bottom: 8px; border-radius: 3px; text-transform: uppercase; }

    .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .data-table th { background: #eee; border: 1px solid #bbb; padding: 5px; text-align: left; font-size: 9px; }
    .data-table td { border: 1px solid #bbb; padding: 5px; vertical-align: top; }

    .destino-table { width: 100%; border-collapse: collapse; }
    .destino-table td { padding: 8px; border: none; vertical-align: top; }
    
    .num-parada { background: #333; color: #fff; width: 35px; text-align: center; font-size: 18px; font-weight: bold; vertical-align: middle !important; }
    
    .dest-info { font-size: 10px; }
    .dest-nome { font-size: 13px; font-weight: bold; text-decoration: underline; margin-bottom: 3px; }
    .dest-municipio { font-size: 11px; color: #555; font-weight: normal; text-decoration: none; }
    
    .secoes-destaque { 
        background-color: #fff0f0; 
        border: 1px dashed #d32f2f; 
        padding: 6px; 
        margin-top: 5px; 
        color: #b71c1c; 
        font-weight: bold; 
    }

    .qr-box { text-align: center; width: 100px; border-left: 1px solid #eee; background: #fafafa; }
    .label-mini { font-size: 8px; color: #777; font-weight: bold; text-transform: uppercase; display: block; }
</style>

<?php foreach ($rotas as $r): 
    // 3. Busca de Colaboradores por Rota
    $colaboradores = $wpdb->get_results($wpdb->prepare("
        SELECT e.nom_eleitor, e.num_telefone_eleitor, f.nom_funcao
        FROM {$table_vagas} v
        JOIN {$table_colaboradores} e ON v.id_colaborador = e.id_colaborador
        JOIN {$table_funcoes} f ON v.id_funcao = f.id_funcao
        WHERE v.id_local = %d AND v.id_pleito = %d", $r->id_origem, $id_pleito_ativo));

    // 4. Busca de Destinos com Subquery para Seções (Otimizado: remove loop SQL interno)
    $destinos = $wpdb->get_results($wpdb->prepare("
        SELECT d.*, l.nom_local, l.endereco, l.num_latitude, l.num_longitude, m.nom_municipio, 
               tl.ds_tipo_local, l.contato_1_local, s.total_secoes,
               (SELECT GROUP_CONCAT(DISTINCT num_secao ORDER BY num_secao ASC SEPARATOR ' - ') 
                FROM {$table_vagas} 
                WHERE tp_secao_mrv = 1 AND id_local = d.id_local_destino) as lista_secoes
        FROM {$table_rotas_destinos} d
        JOIN {$table_locais} l ON d.id_local_destino = l.id_local
        LEFT JOIN {$table_municipios} m ON l.id_municipio = m.id_municipio
        JOIN {$table_tipos_locais} tl ON l.id_tipo_local = tl.id_tipo_local
        LEFT JOIN {$vw_secoes} s ON l.id_local = s.id_local
        WHERE d.id_rota = %d ORDER BY d.sq_rota ASC", $r->id_rota));

    $total_secoes = array_sum(array_column($destinos, 'total_secoes'));
    $total_cont   = array_sum(array_column($destinos, 'qt_urnas_contingencia'));
?>

<div class="rota-page">
    <table class="header-table">
        <tr>
            <td>
                <span class="label-mini">Itinerário Oficial de Transporte</span>
                <div class="rota-nome">ROTA: <?php echo esc_html($r->ds_rota); ?></div>
            </td>
            <td align="right">
                <span class="label-mini">Total Geral de Urnas</span>
                <div class="total-urnas"><?php echo ($total_secoes + $total_cont); ?></div>
            </td>
        </tr>
    </table>

    <div class="summary-box">
        <strong>ORIGEM (BASE):</strong> <?php echo esc_html($r->local_origem); ?><br>
        <strong>COMPOSIÇÃO:</strong> <?php echo $total_secoes; ?> Urnas de Seção + <?php echo $total_cont; ?> Contingência | 
        <strong>STATUS:</strong> <?php echo esc_html($r->status_rota); ?>
    </div>

    <div class="section-title">EQUIPE VINCULADA À ORIGEM</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="50%">NOME DO COLABORADOR</th>
                <th width="25%">FUNÇÃO</th>
                <th width="25%">CONTATO</th>
            </tr>
        </thead>
        <tbody>
            <?php if($colaboradores): foreach($colaboradores as $c): ?>
                <tr>
                    <td><?php echo esc_html($c->nom_eleitor); ?></td>
                    <td><?php echo esc_html($c->nom_funcao); ?></td>
                    <td><?php echo esc_html($c->num_telefone_eleitor); ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="3">Nenhuma equipe alocada na base de origem.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="section-title">ITINERÁRIO E PONTOS DE ENTREGA</div>

    <?php foreach ($destinos as $det): 
        $lat = $det->num_latitude;
        $lng = $det->num_longitude;
        $qr_base64 = '';
        $coords = (!empty($lat) && !empty($lng)) ? "$lat,$lng" : '';

        // 5. Lógica de QR Code com Cache (Transient) para Performance
        if ($coords) {
            $transient_key = 'qr_map_' . md5($coords);
            $qr_base64 = get_transient($transient_key);

            if (false === $qr_base64) {
                $map_url = "https://www.google.com/maps/search/?api=1&query=" . $coords;
                $qr_api = "https://quickchart.io/qr?size=120&text=" . urlencode($map_url);
                
                $response = wp_remote_get($qr_api, ['timeout' => 5]);
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $qr_base64 = 'data:image/png;base64,' . base64_encode(wp_remote_retrieve_body($response));
                    set_transient($transient_key, $qr_base64, DAY_IN_SECONDS * 7); // Cache por 7 dias
                }
            }
        }
    ?>
    <div class="destino-container">
        <table class="destino-table">
            <tr>
                <td class="num-parada"><?php echo (int)$det->sq_rota; ?></td>
                <td class="dest-info">
                    <div class="dest-nome">
                        <?php echo esc_html($det->nom_local); ?> 
                        <span class="dest-municipio">(<?php echo esc_html($det->nom_municipio ?? 'N/I'); ?>)</span>
                    </div>
                    <strong>Endereço:</strong> <?php echo esc_html($det->endereco); ?><br>
                    <strong>Tipo:</strong> <?php echo esc_html($det->ds_tipo_local); ?> | 
                    <strong>Contato:</strong> <?php echo esc_html($det->contato_1_local); ?><br>
                    <strong>Carga:</strong> <?php echo (int)$det->total_secoes; ?> Urnas (S) Seções + <?php echo (int)$det->qt_urnas_contingencia; ?> (C) Contingência
                    
                    <div class="secoes-destaque">
                        <span class="label-mini" style="color:#d32f2f">Seções neste local:</span>
                        <?php echo $det->lista_secoes ? esc_html($det->lista_secoes) : 'Nenhuma seção vinculada'; ?>
                    </div>
                </td>
                <td class="qr-box">
                    <span class="label-mini">GPS</span>
                    <?php if ($qr_base64): ?>
                        <img src="<?php echo $qr_base64; ?>" width="85" height="85" style="margin-top:5px;">
                        <div style="font-size: 7px; margin-top: 3px; color: #666;"><?php echo esc_html($coords); ?></div>
                    <?php else: ?>
                        <div style="font-size:8px;color:#999;margin-top:20px;">QR Code<br>Indisponível</div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true); 
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

// Configuração de contexto SSL para evitar erros em servidores locais
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);
$dompdf->setHttpContext($context);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("Itinerario_Rotas_Eleitorais.pdf", ["Attachment" => false]);
exit;