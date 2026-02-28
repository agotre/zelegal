<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão
if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

/* =========================================================
 * LÓGICA DE ATUALIZAÇÃO: TORNAR DISPONÍVEL
 * ========================================================= */
if (isset($_POST['action']) && $_POST['action'] === 'tornar_disponivel' && isset($_POST['id_colaborador'])) {
    check_admin_referer('ze_tornar_disponivel_' . $_POST['id_colaborador']);
    
    $id_col = intval($_POST['id_colaborador']);
    $wpdb->update(
        "{$prefix}tb_colaboradores",
        ['ds_status_eleitoral' => 'DISPONIVEL'],
        ['id_colaborador' => $id_col],
        ['%s'],
        ['%d']
    );
    echo '<div class="updated notice is-dismissible"><p>Colaborador restaurado para DISPONÍVEL com sucesso!</p></div>';
}

/* =========================================================
 * FUNÇÕES AUXILIARES VISUAIS
 * ========================================================= */
if (!function_exists('ze_get_wp_role_from_meta')) {
    function ze_get_wp_role_from_meta($meta_value) {
        if (empty($meta_value)) return null;
        $caps = maybe_unserialize($meta_value);
        if (!is_array($caps)) return null;
        $roles = array_keys($caps);
        return $roles[0] ?? null;
    }
}

/* =========================================================
 * FILTROS (Forçando DISPENSADO)
 * ========================================================= */
$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'papel' => $_GET['papel'] ?? '',
];

$where = [];
// REGRA 1: Somente os que tiverem DISPENSADO
$where[] = "c.ds_status_eleitoral = 'DISPENSADO'";

if ($filtros['busca']) {
    $like = '%' . $wpdb->esc_like($filtros['busca']) . '%';
    $where[] = $wpdb->prepare(
        '(c.nom_eleitor LIKE %s OR c.num_cpf LIKE %s OR c.num_inscricao LIKE %s)',
        $like, $like, $like
    );
}

if ($filtros['papel']) {
    $where[] = $wpdb->prepare('um.meta_value LIKE %s', '%"' . $wpdb->esc_like($filtros['papel']) . '"%');
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Pega os papéis do WP para o filtro
$meta_key = $wpdb->prefix . 'capabilities';
$papeis_em_uso_raw = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->usermeta} WHERE meta_key = '{$meta_key}'");
$papeis = [];
foreach ($papeis_em_uso_raw as $mv) {
    $caps = maybe_unserialize($mv);
    if (is_array($caps)) {
        foreach (array_keys($caps) as $role) $papeis[$role] = $role;
    }
}
$papeis_em_uso = array_values($papeis);

$total_colaboradores = $wpdb->get_var("SELECT COUNT(c.id_colaborador) FROM {$prefix}tb_colaboradores c LEFT JOIN {$wpdb->usermeta} um ON um.user_id = c.id_user AND um.meta_key = '{$meta_key}' {$where_sql}");

/* =========================================================
 * CONSULTA
 * ========================================================= */
$sql = "
SELECT
    c.id_colaborador, c.id_user, c.nom_eleitor, c.num_cpf, c.num_inscricao,
    c.ds_status_eleitoral, c.num_telefone_eleitor, c.num_telefone_eleitor_2,
    c.email_colaborador, c.id_upload_foto, c.num_local_votacao,
    l.nom_local AS nom_local_votacao, l.nom_municipio AS nom_municipio_votacao,
    um.meta_value AS papel
FROM {$prefix}tb_colaboradores c
LEFT JOIN {$prefix}vw_local_votacao_colaborador l ON l.id_colaborador = c.id_colaborador
LEFT JOIN {$wpdb->usermeta} um ON um.user_id = c.id_user AND um.meta_key = '{$meta_key}'
{$where_sql}
ORDER BY c.nom_eleitor ASC
LIMIT 100
";

$usuarios = $wpdb->get_results($sql);
?>

<style>
    /* Estilos base mantidos e ajustes nos botões */
    :root { --primary: #2271b1; --bg-body: #f0f2f5; --white: #ffffff; --border: #e2e8f0; --text-dark: #1e293b; --text-muted: #64748b; --whatsapp: #25D366; }
    .zelegal-admin { max-width: 1400px; margin: 20px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .ze-header-main h1 { font-size: 28px; font-weight: 800; color: var(--text-dark); }
    .ze-filter-card { background: var(--white); border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid var(--border); }
    .ze-filter-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
    .ze-filter-group { display: flex; flex-direction: column; gap: 6px; }
    .ze-filter-row input, .ze-filter-row select { height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; min-width: 220px; }
    .ze-btn-filter { background: var(--primary); color: #fff; border: none; padding: 0 25px; height: 42px; border-radius: 8px; cursor: pointer; }
    .ze-table-card { background: var(--white); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; }
    .ze-table { width: 100%; border-collapse: collapse; }
    .ze-table th { background: #f8fafc; padding: 15px 20px; font-size: 12px; font-weight: 700; color: var(--text-muted); border-bottom: 2px solid var(--border); }
    .ze-table td { padding: 16px 20px; border-bottom: 1px solid var(--border); font-size: 14px; }
    .ze-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
    .ze-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .status-eleitoral-DISPENSADO { background:#fee2e2; color:#991b1b; }
    .ze-btn-restore {
        background: #10b981; color: #fff; border: none; padding: 8px 15px; border-radius: 8px; 
        font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-size: 13px;
    }
    .ze-btn-restore:hover { background: #059669; }
    .ze-counter-card { background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 10px 20px; text-align: center; }
</style>

<div class="zelegal-admin">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" style="text-decoration:none;">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <h1 class="ze-page-title">Restaurar Colaboradores Dispensados</h1>

    <div class="ze-header-main">
        <p>Lista de colaboradores com status <strong>DISPENSADO</strong>. Clique em "Tornar Disponível" para restaurá-los.</p>
    </div>
    
    <form method="get">
        <input type="hidden" name="page" value="ze-legal-colaboradores">
        <div class="ze-filter-card">
            <div class="ze-filter-row">
                <div class="ze-filter-group">
                    <label>Busca Rápida</label>
                    <input type="text" name="busca" placeholder="Nome, CPF ou Inscrição..." value="<?php echo esc_attr($filtros['busca']); ?>">
                </div>

                <div class="ze-filter-group">
                    <label>Nível de Acesso</label>
                    <select name="papel">
                        <option value="">Todos os Papéis</option>
                        <?php foreach ($papeis_em_uso as $p): ?>
                            <option value="<?php echo esc_attr($p); ?>" <?php selected($filtros['papel'], $p); ?>><?php echo esc_html($p); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="ze-btn-filter">Aplicar Filtros</button>
                
                <div class="ze-counter-card">
                    <span style="font-size:10px; font-weight:700; color:var(--text-muted);">DISPENSADOS</span><br>
                    <span style="font-size:24px; font-weight:800; color:#ef4444;"><?php echo $total_colaboradores; ?></span>
                </div>
            </div>
        </div>
    </form>

    <div class="ze-table-card">
        <table class="ze-table">
            <thead>
                <tr>
                    <th>Perfil</th>
                    <th>Dados do Colaborador</th>
                    <th>Contatos</th>
                    <th>Situação</th>
                    <th>Local Votação</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <?php 
                            if ($u->id_upload_foto) {
                                echo wp_get_attachment_image($u->id_upload_foto, [50, 50], false, ['class' => 'ze-avatar']);
                            } else {
                                echo '<div class="ze-avatar" style="background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8;">👤</div>';
                            }
                            ?>
                        </td>

                        <td>
                            <div style="font-weight: 700;"><?php echo esc_html($u->nom_eleitor); ?></div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                CPF: <?php echo esc_html($u->num_cpf); ?>
                            </div>
                        </td>

                        <td>
                            <div style="font-size: 13px;">
                                <strong><?php echo esc_html($u->num_telefone_eleitor); ?></strong><br>
                                <small><?php echo esc_html($u->email_colaborador); ?></small>
                            </div>
                        </td>

                        <td>
                            <span class="ze-badge status-eleitoral-DISPENSADO">DISPENSADO</span>
                        </td>

                        <td style="font-size: 12px; color: var(--text-muted);">
                            <?php echo esc_html($u->nom_local_votacao ?: '—'); ?>
                        </td>

                        <td style="text-align: right;">
                            <form method="post" onsubmit="return confirm('Deseja realmente tornar este colaborador DISPONÍVEL?');">
                                <?php wp_nonce_field('ze_tornar_disponivel_' . $u->id_colaborador); ?>
                                <input type="hidden" name="action" value="tornar_disponivel">
                                <input type="hidden" name="id_colaborador" value="<?php echo $u->id_colaborador; ?>">
                                <button type="submit" class="ze-btn-restore">
                                    <span class="dashicons dashicons-undo"></span> Tornar Disponível
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px;">Nenhum colaborador dispensado encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>