<?php
if ( ! defined('ABSPATH') ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$tabela = $wpdb->prefix . 'ze_tb_municipios';
$tabela_locais = $wpdb->prefix . 'ze_tb_locais';

/* =========================
 * EXCLUSÃO
 * ========================= */
if ( isset($_POST['zelegal_excluir_municipio']) ) {
    check_admin_referer('zelegal_excluir_municipio');
    $id_municipio = intval($_POST['zelegal_excluir_municipio']);
    $total_locais = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tabela_locais} WHERE id_municipio = %d", $id_municipio));

    if ( $total_locais == 0 ) {
        $wpdb->delete($tabela, ['id_municipio' => $id_municipio], ['%d']);
    }
}

/* =========================
 * INSERT / UPDATE
 * ========================= */
if ( isset($_POST['zelegal_salvar_municipio']) ) {
    check_admin_referer('zelegal_cadastro_municipio');
    $dados = [
        'codigo_ibge'        => sanitize_text_field($_POST['codigo_ibge']),
        'nom_municipio'     => sanitize_text_field($_POST['nom_municipio']),
        'nom_municipio_elo'  => sanitize_text_field($_POST['nom_municipio_elo']), // Novo Campo
        'updated_at'         => current_time('mysql'),
    ];

    if ( ! empty($_POST['id_municipio']) ) {
        $wpdb->update($tabela, $dados, ['id_municipio' => intval($_POST['id_municipio'])]);
    } else {
        $dados['created_at'] = current_time('mysql');
        $wpdb->insert($tabela, $dados);
    }
}

/* =========================
 * FILTRO DE BUSCA
 * ========================= */
$filtro = '';
$param  = [];
if ( ! empty($_GET['q']) ) {
    $filtro = "WHERE m.nom_municipio LIKE %s OR m.nom_municipio_elo LIKE %s";
    $termo = '%' . $wpdb->esc_like( sanitize_text_field($_GET['q']) ) . '%';
    $param[] = $termo;
    $param[] = $termo;
}

$sql = "
    SELECT m.*, (SELECT COUNT(*) FROM {$tabela_locais} l WHERE l.id_municipio = m.id_municipio) AS total_locais
    FROM {$tabela} m {$filtro} ORDER BY m.nom_municipio ASC
";

$municipios = $param ? $wpdb->get_results( $wpdb->prepare($sql, ...$param) ) : $wpdb->get_results( $sql );
?>

<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <header>
        <h1 class="ze-page-title">Cadastro de Municípios</h1>
    </header>

    <div class="ze-card">
        <h2 class="ze-section-title">
            <span class="dashicons dashicons-admin-site"></span> Dados do Município
        </h2>

        <form method="post" id="zelegal-form-municipio">
            <?php wp_nonce_field('zelegal_cadastro_municipio'); ?>
            <input type="hidden" name="id_municipio">

            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Código IBGE</label>
                    <input type="text" name="codigo_ibge" maxlength="16" required placeholder="Cód. IBGE">
                </div>

                <div class="ze-form-group">
                    <label>Nome do Município</label>
                    <input type="text" name="nom_municipio" required placeholder="Ex: Porto Velho">
                </div>

                <div class="ze-form-group">
                    <label>Município (Sistema ELO)</label>
                    <input type="text" name="nom_municipio_elo" maxlength="60" placeholder="Ex: PORTO VELHO">
                </div>
            </div>

            <div class="ze-form-footer">
                <button type="submit" name="zelegal_salvar_municipio" class="ze-btn-submit">
                    Salvar Município
                </button>
            </div>
        </form>
    </div>

    <h2 class="ze-section-title">
        <span class="dashicons dashicons-list-view"></span> Municípios Registrados
    </h2>

    <div class="ze-card no-padding">

        <form method="get" class="ze-search-box" style="padding:20px;">
            <input type="hidden" name="page" value="ze-legal-municipios">
            <input type="text" name="q" value="<?php echo esc_attr($_GET['q'] ?? ''); ?>" placeholder="Buscar por nome ou ELO...">
            <button type="submit" class="ze-btn-secondary">Buscar</button>

            <?php if(!empty($_GET['q'])): ?>
                <a href="?page=ze-legal-municipios" class="ze-btn-secondary">Limpar</a>
            <?php endif; ?>
        </form>

        <table class="ze-table">
            <thead>
                <tr>
                    <th style="width: 120px;">IBGE</th>
                    <th>Nome Município</th>
                    <th>Município ELO</th>
                    <th style="width: 80px; text-align: center;">Locais</th>
                    <th style="text-align: right; width: 180px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($municipios): foreach ($municipios as $m): ?>
                <tr>
                    <td><strong><?= esc_html($m->codigo_ibge); ?></strong></td>
                    <td><strong><?= esc_html($m->nom_municipio); ?></strong></td>
                    <td><?= esc_html($m->nom_municipio_elo); ?></td>
                    <td style="text-align: center;">
                        <span class="ze-badge ze-badge-neutral">
                            <?= (int)$m->total_locais; ?>
                        </span>
                    </td>
                    <td style="text-align: right; display:flex; justify-content:flex-end; gap:15px; align-items:center;">
                        <button class="ze-edit-link zelegal-editar-municipio" data-municipio='<?= esc_attr(json_encode($m)); ?>'>
                            <span class="dashicons dashicons-edit"></span> Editar
                        </button>

                        <?php if ((int)$m->total_locais === 0): ?>
                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('zelegal_excluir_municipio'); ?>
                                <input type="hidden" name="zelegal_excluir_municipio" value="<?= intval($m->id_municipio); ?>">
                                <button type="submit" class="ze-edit-link" style="color:#991b1b; background:none; border:none; cursor:pointer;" onclick="return confirm('Excluir município?')">
                                    <span class="dashicons dashicons-trash"></span> Excluir
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                    <td colspan="5" style="padding:40px; text-align:center; color:#999;">
                        Nenhum município encontrado.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.zelegal-editar-municipio').forEach(btn=>{
    btn.addEventListener('click',()=>{
        const m = JSON.parse(btn.dataset.municipio);
        const f = document.getElementById('zelegal-form-municipio');
        f.id_municipio.value        = m.id_municipio;
        f.codigo_ibge.value         = m.codigo_ibge || '';
        f.nom_municipio.value       = m.nom_municipio || '';
        f.nom_municipio_elo.value   = m.nom_municipio_elo || ''; // Preenchimento do novo campo
        window.scrollTo({top:0,behavior:'smooth'});
    });
});
</script>