<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$prefix = $wpdb->prefix;

// Tabelas
$table_rotas          = $prefix . 'ze_tb_rotas';
$table_rotas_destinos = $prefix . 'ze_tb_rotas_destinos';
$table_locais         = $prefix . 'ze_tb_locais';
$table_pleitos        = $prefix . 'ze_tb_pleitos';

/* ==========================================================
   1. BUSCA O PLEITO ATIVO
========================================================== */
$pleito = $wpdb->get_row("SELECT id_pleito, descricao FROM {$table_pleitos} WHERE status_pleito = 1 LIMIT 1");
$id_pleito = $pleito ? $pleito->id_pleito : 0;

/* ==========================================================
   2. BUSCA TODAS AS ROTAS E SEUS ITINERÁRIOS
========================================================== */
$sql = $wpdb->prepare("
    SELECT 
        r.id_rota, 
        r.ds_rota, 
        r.tipo_rota,
        l_origem.nom_local as nome_base,
        l_origem.num_latitude as lat_base,
        l_origem.num_longitude as lng_base,
        d.sq_rota,
        l_dest.nom_local as nome_destino,
        l_dest.num_latitude as lat_dest,
        l_dest.num_longitude as lng_dest
    FROM {$table_rotas} r
    INNER JOIN {$table_locais} l_origem ON r.id_local = l_origem.id_local
    LEFT JOIN {$table_rotas_destinos} d ON r.id_rota = d.id_rota
    LEFT JOIN {$table_locais} l_dest ON d.id_local_destino = l_dest.id_local
    WHERE r.id_pleito = %d
    ORDER BY r.ds_rota, d.sq_rota ASC
", $id_pleito);

$dados_brutos = $wpdb->get_results($sql);

$rotas_json = [];
foreach ($dados_brutos as $row) {
    if (!isset($rotas_json[$row->id_rota])) {
        $rotas_json[$row->id_rota] = [
            'nome' => $row->ds_rota,
            'pontos' => []
        ];
        if ($row->lat_base && $row->lng_base) {
            $rotas_json[$row->id_rota]['pontos'][] = [
                'nome' => "BASE: " . $row->nome_base,
                'lat'  => (float)$row->lat_base,
                'lng'  => (float)$row->lng_base,
                'label'=> 'B' // B de Base
            ];
        }
    }
    if ($row->lat_dest && $row->lng_dest) {
        $rotas_json[$row->id_rota]['pontos'][] = [
            'nome' => $row->nome_destino,
            'lat'  => (float)$row->lat_dest,
            'lng'  => (float)$row->lng_dest,
            'label'=> (int)$row->sq_rota
        ];
    }
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #mapa-container { height: 600px; width: 100%; border: 1px solid #ccc; border-radius: 8px; }
    .filtros-mapa { background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 5px; border: 1px solid #ddd; }
    
    /* Estilo do Ícone Numérico */
    .icon-numero {
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
        border: 2px solid white;
        border-radius: 50%;
        box-shadow: 0 0 5px rgba(0,0,0,0.5);
        width: 24px !important;
        height: 24px !important;
    }
</style>

<div class="wrap">
    <h1>Mapa de Rotas Logísticas</h1>
    <div class="filtros-mapa">
        <strong>Selecionar Rota:</strong>
        <select id="select-rota" onchange="filtrarRota(this.value)">
            <option value="todas">Exibir Todas as Rotas</option>
            <?php foreach ($rotas_json as $id => $r): ?>
                <option value="<?php echo $id; ?>"><?php echo esc_html($r['nome']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="mapa-container"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const dadosRotas = <?php echo json_encode($rotas_json); ?>;
    let mapa;
    let camadasRotas = {};

    function initMap() {
        mapa = L.map('mapa-container').setView([-15.7801, -47.9292], 4); 

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(mapa);

        const cores = ['#e6194b', '#3cb44b', '#ffe119', '#4363d8', '#f58231', '#911eb4', '#46f0f0', '#f032e6', '#bcf60c', '#fabebe'];
        let corIndex = 0;
        let todosOsPontos = [];

        Object.keys(dadosRotas).forEach(id => {
            const rota = dadosRotas[id];
            const cor = cores[corIndex % cores.length];
            corIndex++;

            let grupoRota = L.featureGroup();
            let coordenadasLinha = [];

            rota.pontos.forEach(ponto => {
                const latlng = [ponto.lat, ponto.lng];
                coordenadasLinha.push(latlng);
                todosOsPontos.push(latlng);

                // CRIAÇÃO DO ÍCONE NUMÉRICO
                const customIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div class="icon-numero" style="background-color: ${cor}">${ponto.label}</div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });

                L.marker(latlng, { icon: customIcon })
                    .bindPopup(`<strong>${rota.nome}</strong><br>${ponto.label === 'B' ? '' : ponto.label + 'º Destino: '}${ponto.nome}`)
                    .addTo(grupoRota);
            });

            if (coordenadasLinha.length > 1) {
                L.polyline(coordenadasLinha, {
                    color: cor,
                    weight: 3,
                    opacity: 0.8,
                    dashArray: '5, 10'
                }).addTo(grupoRota);
            }

            camadasRotas[id] = grupoRota;
            grupoRota.addTo(mapa);
        });

        if (todosOsPontos.length > 0) {
            mapa.fitBounds(L.latLngBounds(todosOsPontos), { padding: [50, 50] });
        }
    }

    function filtrarRota(id) {
        let bounds = L.latLngBounds();
        let pontosEncontrados = false;

        Object.keys(camadasRotas).forEach(rotaId => {
            if (id === 'todas' || rotaId === id) {
                mapa.addLayer(camadasRotas[rotaId]);
                bounds.extend(camadasRotas[rotaId].getBounds());
                pontosEncontrados = true;
            } else {
                mapa.removeLayer(camadasRotas[rotaId]);
            }
        });
        
        if (pontosEncontrados) {
            mapa.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    window.onload = initMap;
</script>