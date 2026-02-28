<?php
if ( ! defined( 'ABSPATH' ) ) exit;



global $wpdb;
$tabela_vagas  = $wpdb->prefix . 'ze_tb_vagas_pleitos';
$tabela_locais = $wpdb->prefix . 'ze_tb_locais';

/**
 * PROCESSA AGRUPAMENTO
 */
if ( isset($_POST['acao']) && $_POST['acao'] === 'agrupar' ) {

    $id_local        = intval($_POST['id_local']);
    $secao_principal = sanitize_text_field($_POST['secao_principal']);
    $secao_agrupar   = sanitize_text_field($_POST['secao_agrupar']);

    // Busca seção principal (qualquer vaga da seção)
    $principal = $wpdb->get_row( $wpdb->prepare("
        SELECT *
        FROM {$tabela_vagas}
        WHERE num_secao = %s
          AND id_local = %d
          AND tp_secao_mrv = 1
        LIMIT 1
    ", $secao_principal, $id_local) );

    if ( ! $principal ) {
        wp_die('Seção principal inválida.');
    }

    // Descobre campo disponível
    if ( empty($principal->num_secao_agregada1) ) {
        $campo = 'num_secao_agregada1';
    } elseif ( empty($principal->num_secao_agregada2) ) {
        $campo = 'num_secao_agregada2';
    } elseif ( empty($principal->num_secao_agregada3) ) {
        $campo = 'num_secao_agregada3';
    } elseif ( empty($principal->num_secao_agregada4) ) {
        $campo = 'num_secao_agregada4';    
    } else {
        wp_die('Limite máximo de 3 seções agregadas atingido.');
    }

    // Atualiza todas as vagas da seção principal
    $wpdb->update(
        $tabela_vagas,
        [ $campo => $secao_agrupar ],
        [
            'num_secao' => $secao_principal,
            'id_local'  => $id_local
        ]
    );

    // Remove todas as vagas da seção agregada
    $wpdb->delete(
        $tabela_vagas,
        [
            'num_secao' => $secao_agrupar,
            'id_local'  => $id_local
        ]
    );

    echo '<div class="notice notice-success"><p>Agrupamento realizado com sucesso.</p></div>';
}

/**
 * FILTRO POR LOCAL (somente locais com MRV)
 */
$id_local_filtro = isset($_GET['id_local']) ? intval($_GET['id_local']) : 0;

// Lista locais (id_local + nom_local) que possuem MRV
$locais = $wpdb->get_results("
    SELECT DISTINCT
        l.id_local,
        l.nom_local
    FROM {$tabela_vagas} v
    INNER JOIN {$tabela_locais} l ON l.id_local = v.id_local
    WHERE v.tp_secao_mrv = 1
    ORDER BY l.nom_local
");

// Lista seções MRV do local
$secoes = [];
if ( $id_local_filtro ) {
    $secoes = $wpdb->get_results( $wpdb->prepare("
        SELECT
            num_secao,
            MAX(num_secao_agregada1) AS num_secao_agregada1,
            MAX(num_secao_agregada2) AS num_secao_agregada2,
            MAX(num_secao_agregada3) AS num_secao_agregada3,
            MAX(num_secao_agregada4) AS num_secao_agregada4
        FROM {$tabela_vagas}
        WHERE tp_secao_mrv = 1
          AND id_local = %d
        GROUP BY num_secao
        ORDER BY num_secao
    ", $id_local_filtro) );
}
?>

<style>
    .ze-premium-container { margin: 20px 20px 0 0; font-family: 'Segoe UI', Roboto, sans-serif; color: #2c3338; }
    
    /* Header */
    .ze-header { margin-bottom: 25px; }
    .ze-header h1 { font-size: 24px; font-weight: 700; color: #1d2327; margin: 0; }
    .ze-header p { color: #646970; margin: 5px 0 0; }

    /* Filtros e Cards */
    .ze-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; }
    .ze-card-title { font-weight: 600; font-size: 14px; text-transform: uppercase; color: #50575e; margin-bottom: 15px; display: block; letter-spacing: 0.5px; }

    /* Formulários Flexíveis */
    .ze-filter-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
    .ze-field-group { display: flex; flex-direction: column; gap: 6px; }
    .ze-field-group label { font-weight: 600; font-size: 12px; color: #1d2327; }

    .ze-premium-container select, 
    .ze-premium-container input { 
        height: 38px !important; border-radius: 6px !important; border: 1px solid #8c8f94 !important; padding: 0 10px !important; min-width: 200px;
    }

    /* Botões */
    .btn-ze { border-radius: 6px !important; height: 38px !important; padding: 0 20px !important; font-weight: 600 !important; cursor: pointer; transition: 0.2s; border: none; }
    .btn-ze-primary { background: #2271b1 !important; color: #fff !important; }
    .btn-ze-primary:hover { background: #135e96 !important; }
    .btn-ze-success { background: #135e23 !important; color: #fff !important; }
    .btn-ze-success:hover { background: #0d4a1c !important; }

    /* Tabela Premium */
    .ze-table-card { padding: 0; overflow: hidden; }
    .ze-table { width: 100%; border-collapse: collapse; margin: 0; }
    .ze-table thead { background: #f6f7f7; }
    .ze-table th { padding: 15px 20px; text-align: left; font-weight: 600; color: #1d2327; border-bottom: 2px solid #dcdcde; font-size: 13px; }
    .ze-table td { padding: 12px 20px; border-bottom: 1px solid #f0f0f1; font-size: 13px; }
    .ze-table tr:hover { background-color: #f9fbff; }
    
    .badge-principal { background: #e5f5fa; color: #007cba; padding: 4px 8px; border-radius: 4px; font-weight: 700; font-family: monospace; }
    .badge-agregada { background: #f0f0f1; color: #50575e; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-family: monospace; }
</style>

<div class="ze-premium-container">
    
    <div class="ze-header">
        <h1>Agrupar Seções MRV</h1>
        <p>Gerencie a agregação de seções eleitorais por local de votação.</p>
    </div>

    <div class="ze-card">
        <span class="ze-card-title">1. Selecionar Localidade</span>
        <form method="get" class="ze-filter-row">
            <input type="hidden" name="page" value="ze-legal-agregar-vagas-secoes">
            
            <div class="ze-field-group">
                <label for="id_local">Local de Votação:</label>
                <select name="id_local" id="id_local" required style="min-width: 400px;">
                    <option value="">Selecione um local para listar as seções...</option>
                    <?php foreach ( $locais as $loc ): ?>
                        <option value="<?= esc_attr($loc->id_local) ?>" <?= selected($loc->id_local, $id_local_filtro) ?>>
                            <?= esc_html($loc->nom_local) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-ze btn-ze-primary">Filtrar Seções</button>
        </form>
    </div>

    <?php if ( $id_local_filtro && $secoes ): ?>

        <div class="ze-card" style="border-left: 5px solid #135e23;">
            <span class="ze-card-title">2. Executar Novo Agrupamento</span>
            <form method="post" class="ze-filter-row">
                <input type="hidden" name="acao" value="agrupar">
                <input type="hidden" name="id_local" value="<?= esc_attr($id_local_filtro) ?>">

                <div class="ze-field-group">
                    <label>Seção Principal (Destino):</label>
                    <select name="secao_principal" required>
                        <?php foreach ( $secoes as $s ): ?>
                            <option value="<?= esc_attr($s->num_secao) ?>">Seção <?= esc_html($s->num_secao) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="font-size: 20px; color: #c3c4c7; padding-bottom: 8px;">&plus;</div>

                <div class="ze-field-group">
                    <label>Seção a Agrupar (Origem):</label>
                    <select name="secao_agrupar" required>
                        <?php foreach ( $secoes as $s ): ?>
                            <option value="<?= esc_attr($s->num_secao) ?>">Seção <?= esc_html($s->num_secao) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-ze btn-ze-success">Agrupar Agora</button>
            </form>
        </div>

        <div class="ze-card ze-table-card">
            <table class="ze-table">
                <thead>
                    <tr>
                        <th>Seção Principal</th>
                        <th>Agregada 01</th>
                        <th>Agregada 02</th>
                        <th>Agregada 03</th>
                        <th>Agregada 04</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $secoes as $s ): ?>
                        <tr>
                            <td><span class="badge-principal"><?= esc_html($s->num_secao) ?></span></td>
                            <td><?= $s->num_secao_agregada1 ? '<span class="badge-agregada">'.esc_html($s->num_secao_agregada1).'</span>' : '<span style="color:#ccd0d4">—</span>' ?></td>
                            <td><?= $s->num_secao_agregada2 ? '<span class="badge-agregada">'.esc_html($s->num_secao_agregada2).'</span>' : '<span style="color:#ccd0d4">—</span>' ?></td>
                            <td><?= $s->num_secao_agregada3 ? '<span class="badge-agregada">'.esc_html($s->num_secao_agregada3).'</span>' : '<span style="color:#ccd0d4">—</span>' ?></td>
                            <td><?= $s->num_secao_agregada4 ? '<span class="badge-agregada">'.esc_html($s->num_secao_agregada4).'</span>' : '<span style="color:#ccd0d4">—</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ( $id_local_filtro ): ?>
        <div class="notice notice-info inline">
            <p>Nenhuma seção MRV encontrada para o local selecionado.</p>
        </div>
    <?php endif; ?>
</div>