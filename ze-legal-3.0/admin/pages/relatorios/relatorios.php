<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('ze_cadastro_adm_cartorio')) {
    wp_die('Acesso não autorizado.');
}

global $wpdb;

$view = $wpdb->prefix . 'ze_vw_secao_vagas_locais_colaboradores';

// Buscar zonas distintas da view (pleito já ativo)
$zonas = $wpdb->get_results("
    SELECT DISTINCT id_zona, ds_zona
    FROM {$view}
    ORDER BY ds_zona ASC
");
?>

<div class="ze-admin-container">

    <a href="<?php echo admin_url('admin.php?page=ze-legal-dashboard'); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <h1>Emissão de Relatórios</h1>

    <form method="GET" action="<?php echo admin_url('admin-post.php'); ?>" target="_blank">

        <input type="hidden" name="action" value="ze_identificacao_secao_pdf_handler">

        <?php wp_nonce_field('ze_identificacao_secao_pdf_nonce'); ?>

        <table class="form-table">
            <tr>
                <th>Zona Eleitoral</th>
                <td>
                    <select name="id_zona" required>
                        <option value="">Selecione a Zona</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?php echo esc_attr($zona->id_zona); ?>">
                                <?php echo esc_html($zona->ds_zona); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <p>
            <button type="submit" class="button button-primary">
                Emitir Identificação de Seções
            </button>
        </p>

    </form>

</div>