<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Interface de Importação de Colaboradores - ZE Legal
 * Layout Premium com Grid de Resultados
 */

if ( ! current_user_can('ze_cadastro_adm') ) {
    wp_die('Acesso não autorizado.');
}

global $wpdb;
$prefix       = $wpdb->prefix . 'ze_';
$tmp_table    = "{$prefix}tmp_import_colaboradores";
$final_table  = "{$prefix}tb_colaboradores";

@set_time_limit(300);
@ini_set('memory_limit', '512M');

$importados  = 0;
$atualizados = 0;

/* =========================================================
   AÇÕES POST (Lógica de Negócio)
========================================================= */

// CANCELAR TMP
if ( isset($_POST['ze_cancelar_tmp']) ) {
    check_admin_referer('ze_import_action', 'ze_import_nonce');
    $wpdb->query("TRUNCATE TABLE {$tmp_table}");
    wp_redirect( admin_url('admin.php?page=ze-importar-colaboradores') );
    exit;
}

// UPLOAD E ANÁLISE CSV
if ( isset($_POST['ze_upload_csv']) ) {
    check_admin_referer('ze_import_action', 'ze_import_nonce');

    if ( empty($_FILES['colaboradores_csv']) || $_FILES['colaboradores_csv']['error'] !== UPLOAD_ERR_OK ) {
        echo '<div class="notice notice-error is-dismissible"><p>Erro no upload do arquivo.</p></div>';
    } else {
        $wpdb->query("TRUNCATE TABLE {$tmp_table}");
        $file = fopen($_FILES['colaboradores_csv']['tmp_name'], 'r');
        $header = fgetcsv($file, 0, ';');
        $header = array_map(function($item){
            return trim(str_replace("\xEF\xBB\xBF", '', $item));
        }, $header);

        $line_number = 0;
        while ( ($row = fgetcsv($file, 0, ';')) !== false ) {
            $line_number++;
            if ( count($row) < count($header) ) {
                $row = array_pad($row, count($header), '');
            }
            $data = array_combine($header, $row);
            if ($data === false || empty($data['num_cpf_eleitor'])) continue;

            $cpf       = preg_replace('/\D/', '', $data['num_cpf_eleitor']);
            $inscricao = isset($data['num_inscricao']) ? str_pad(preg_replace('/\D/', '', $data['num_inscricao']), 12, '0', STR_PAD_LEFT) : null;
            $secao     = isset($data['num_secao_votacao']) ? str_pad(preg_replace('/\D/', '', $data['num_secao_votacao']), 4, '0', STR_PAD_LEFT) : null;
            $local     = isset($data['num_local_votacao']) ? str_pad(preg_replace('/\D/', '', $data['num_local_votacao']), 4, '0', STR_PAD_LEFT) : null;
            $tel       = isset($data['num_telefone_eleitor']) ? preg_replace('/\D/', '', $data['num_telefone_eleitor']) : null;

            $experiencia = $data['des_funcao_eleitoral'] ?? ($data['experiencia'] ?? null);

            $wpdb->insert($tmp_table, [
                'num_cpf'               => $cpf,
                'nom_eleitor'           => strtoupper($data['nom_eleitor'] ?? ''),
                'num_inscricao'         => $inscricao,
                'num_zona_votacao'      => $data['num_zona_votacao'] ?? null,
                'num_secao_votacao'     => $secao,
                'num_local_votacao'     => $local,
                'nom_municipio_votacao' => $data['municipio_votacao'] ?? null,
                'num_telefone_eleitor'  => $tel,
                'ds_experiencia'        => $experiencia,
                'linha_origem'          => $line_number,
                'data_importacao'       => current_time('mysql')
            ]);
        }
        fclose($file);
        echo '<div class="notice notice-success is-dismissible"><p>Arquivo processado e disponível para análise abaixo.</p></div>';
    }
}

// IMPORTAR AGORA
if ( isset($_POST['ze_importar_agora']) ) {
    check_admin_referer('ze_import_action', 'ze_import_nonce');
    $registros = $wpdb->get_results("SELECT * FROM {$tmp_table}");
    foreach ($registros as $r) {
        $existe = $wpdb->get_var($wpdb->prepare("SELECT id_colaborador FROM {$final_table} WHERE num_cpf = %s", $r->num_cpf));
        if ( ! $existe ) {
            $telefone_formatado = ze_telefone_mascara( $r->num_telefone_eleitor );
            
            $wpdb->insert($final_table, [
                'num_cpf'               => $r->num_cpf,
                'nom_eleitor'           => $r->nom_eleitor,
                'num_inscricao'         => $r->num_inscricao,
                'num_zona_votacao'      => $r->num_zona_votacao,
                'num_secao_votacao'     => $r->num_secao_votacao,
                'num_local_votacao'     => $r->num_local_votacao,
                'nom_municipio_votacao' => $r->nom_municipio_votacao,
                'num_telefone_eleitor'  => $telefone_formatado,
                'ds_experiencia'        => $r->ds_experiencia
            ]);
            $importados++;
        }
    }
}

// ATUALIZAR AGORA
if ( isset($_POST['ze_atualizar_agora']) ) {
    check_admin_referer('ze_import_action', 'ze_import_nonce');
    $registros = $wpdb->get_results("SELECT * FROM {$tmp_table}");
    foreach ($registros as $r) {
        $existe = $wpdb->get_var($wpdb->prepare("SELECT id_colaborador FROM {$final_table} WHERE num_cpf = %s", $r->num_cpf));
        if ( $existe ) {
            $wpdb->update($final_table, [
                'num_zona_votacao'      => $r->num_zona_votacao,
                'num_local_votacao'     => $r->num_local_votacao,
                'num_secao_votacao'     => $r->num_secao_votacao,
                'nom_municipio_votacao' => $r->nom_municipio_votacao
            ], [ 'num_cpf' => $r->num_cpf ]);
            $atualizados++;
        }
    }
}

/* =========================================================
   ESTATÍSTICAS PARA OS CARDS
========================================================= */
$stats = ['total'=>0,'novos'=>0,'existentes'=>0];
$rows = $wpdb->get_results("SELECT num_cpf FROM {$tmp_table}");
foreach ($rows as $r) {
    $stats['total']++;
    $existe = $wpdb->get_var($wpdb->prepare("SELECT id_colaborador FROM {$final_table} WHERE num_cpf = %s", $r->num_cpf));
    if ($existe) $stats['existentes']++; else $stats['novos']++;
}
$preview = $wpdb->get_results("SELECT * FROM {$tmp_table} LIMIT 5");
?>

<div class="wrap ze-admin-container">
    
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
            <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
        </a>
    
    <header>
        <h1 class="ze-page-title">Importação de Colaboradores</h1>
    </header>
    
    <div class="ze-card ze-card-main">
        
        <div class="ze-card-body">
            <form method="post" enctype="multipart/form-data" class="ze-import-form-layout">
                <?php wp_nonce_field('ze_import_action', 'ze_import_nonce'); ?>
                <div class="ze-input-group">
                    <label>Selecione o CSV</label>
                    <input type="file" name="colaboradores_csv" accept=".csv" required>
                </div>
                <button type="submit" name="ze_upload_csv" class="button button-primary button-hero">
                    <span class="dashicons dashicons-search"></span> Carregar e Analisar Base
                </button>
            </form>
        </div>
    </div>

    <?php if ($stats['total'] > 0) : ?>

        <div class="ze-result-grid">
            
            <div class="ze-card ze-card-stat border-blue">
                <div class="ze-card-header">
                    <h4><span class="dashicons dashicons-analytics"></span> Análise</h4>
                </div>
                <div class="ze-card-body">
                    <div class="ze-stat-row"><span>Total Lido:</span> <strong><?php echo $stats['total']; ?></strong></div>
                    <div class="ze-stat-row"><span>Novos:</span> <strong class="color-green"><?php echo $stats['novos']; ?></strong></div>
                    <div class="ze-stat-row"><span>Existentes:</span> <strong class="color-orange"><?php echo $stats['existentes']; ?></strong></div>
                </div>
            </div>

            <div class="ze-card ze-card-stat <?php echo ($importados > 0) ? 'border-green active' : ''; ?>">
                <div class="ze-card-header">
                    <h4><span class="dashicons dashicons-database-add"></span> Importação</h4>
                </div>
                <div class="ze-card-body">
                    <div class="ze-stat-row"><span>Total Inserido:</span> <strong><?php echo $importados; ?></strong></div>
                    <p class="description">Registros adicionados à base oficial.</p>
                </div>
            </div>

            <div class="ze-card ze-card-stat <?php echo ($atualizados > 0) ? 'border-purple active' : ''; ?>">
                <div class="ze-card-header">
                    <h4><span class="dashicons dashicons-update"></span> Atualização</h4>
                </div>
                <div class="ze-card-body">
                    <div class="ze-stat-row"><span>Total Atualizado:</span> <strong><?php echo $atualizados; ?></strong></div>
                    <p class="description">Dados de local/zona sincronizados.</p>
                </div>
            </div>

        </div>

        <div class="ze-card">
            <div class="ze-card-header">
                <h3><span class="dashicons dashicons-visibility"></span> Prévia Completa (5 registros)</h3>
            </div>
            <div class="ze-table-responsive">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:110px">CPF</th>
                            <th>Nome</th>
                            <th style="width:110px">Inscrição</th>
                            <th style="width:60px">Zona</th>
                            <th style="width:60px">Seção</th>
                            <th style="width:60px">Local</th>
                            <th>Município</th>
                            <th style="width:130px">Telefone</th>
                            <th>Experiência</th>
                            <th style="width:60px">Linha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $p) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($p->num_cpf); ?></strong></td>
                            <td><?php echo esc_html($p->nom_eleitor); ?></td>
                            <td><?php echo esc_html($p->num_inscricao); ?></td>
                            <td><?php echo esc_html($p->num_zona_votacao); ?></td>
                            <td><?php echo esc_html($p->num_secao_votacao); ?></td>
                            <td><?php echo esc_html($p->num_local_votacao); ?></td>
                            <td><?php echo esc_html($p->nom_municipio_votacao); ?></td>
                            <td><?php echo esc_html($p->num_telefone_eleitor); ?></td>
                            <td><small><?php echo esc_html($p->ds_experiencia); ?></small></td>
                            <td><span class="badge"><?php echo esc_html($p->linha_origem); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <div class="ze-actions-footer">
            <form method="post">
                <?php wp_nonce_field('ze_import_action', 'ze_import_nonce'); ?>
                
                <button type="submit" name="ze_importar_agora" class="button button-primary">
                    <span class="dashicons dashicons-cloud-upload"></span> Confirmar Importação
                </button>

                <button type="submit" name="ze_atualizar_agora" class="button">
                    <span class="dashicons dashicons-update"></span> Atualizar Existentes
                </button>

                <button type="submit" name="ze_cancelar_tmp" class="button button-link-delete">
                    Cancelar e Limpar
                </button>
            </form>
        </div>

    <?php endif; ?>
</div>



<style>
/* Layout Principal */
.ze-container { margin: 20px auto; max-width: 1200px; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; }
.ze-header-area { margin-bottom: 25px; }
.ze-btn-back { text-decoration: none; color: #666; font-size: 13px; display: inline-flex; align-items: center; margin-bottom: 10px; transition: color 0.2s; }
.ze-btn-back:hover { color: #2271b1; }
.ze-btn-back .dashicons { font-size: 16px; margin-right: 5px; }

/* Cards Premium */
.ze-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 20px; overflow: hidden; }
.ze-card-header { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; background: #f9f9f9; display: flex; align-items: center; }
.ze-card-header h3, .ze-card-header h4 { margin: 0; font-size: 15px; color: #1d2327; }
.ze-card-header .dashicons { margin-right: 10px; color: #646970; }
.ze-card-body { padding: 20px; }

/* Grid de Status */
.ze-result-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
.ze-card-stat { border-top: 4px solid #ccd0d4; }
.ze-card-stat.border-blue { border-top-color: #2271b1; }
.ze-card-stat.border-green.active { border-top-color: #46b450; background: #f0fcf1; }
.ze-card-stat.border-purple.active { border-top-color: #722ed1; background: #f9f0ff; }

.ze-stat-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f1; }
.ze-stat-row:last-child { border-bottom: none; }
.ze-stat-row strong { font-size: 16px; }

/* Cores e Auxiliares */
.color-green { color: #2e7d32; }
.color-orange { color: #ed6c02; }
.ze-table-wrapper { padding: 10px; }
.ze-actions-footer { background: #fff; padding: 25px; border: 1px solid #ccd0d4; border-radius: 6px; text-align: center; display: flex; justify-content: center; gap: 15px; align-items: center; }

/* Responsividade */
@media (max-width: 850px) {
    .ze-result-grid { grid-template-columns: 1fr; }
}
</style>
