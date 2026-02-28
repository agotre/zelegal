<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

/* =========================================================
 * FUNÇÕES AUXILIARES VISUAIS
 * ========================================================= */

if (!function_exists('ze_get_wp_role_from_meta')) {
    function ze_get_wp_role_from_meta($meta_value) {

        if (empty($meta_value)) {
            return null;
        }

        $caps = maybe_unserialize($meta_value);

        if (!is_array($caps)) {
            return null;
        }

        $roles = array_keys($caps);

        return $roles[0] ?? null;
    }
}

/* =========================================================
 * FILTROS
 * ========================================================= */
$filtros = [
    'busca'            => $_GET['busca'] ?? '',
    'status_eleitoral' => $_GET['ds_status_eleitoral'] ?? '',
    'papel'            => $_GET['papel'] ?? '',
];

$where = [];
if ($filtros['busca']) {
    $like = '%' . $wpdb->esc_like($filtros['busca']) . '%';
    $where[] = $wpdb->prepare(
        '(c.nom_eleitor LIKE %s OR c.num_cpf LIKE %s OR c.num_inscricao LIKE %s)',
        $like, $like, $like
    );
}

if ($filtros['status_eleitoral']) {
    $where[] = $wpdb->prepare('c.ds_status_eleitoral = %s', $filtros['status_eleitoral']);
}

if ($filtros['papel']) {
    $where[] = $wpdb->prepare(
        'um.meta_value LIKE %s',
        '%"' . $wpdb->esc_like($filtros['papel']) . '"%'
    );
}

$where_sql =$where ? 'WHERE ' . implode(' AND ', $where) : '';

$status_em_uso = $wpdb->get_col("SELECT DISTINCT ds_status_eleitoral FROM {$prefix}tb_colaboradores WHERE ds_status_eleitoral IS NOT NULL ORDER BY ds_status_eleitoral");

// pega os papeis do wp
global $wpdb;
$meta_key = $wpdb->prefix . 'capabilities';
$papeis_em_uso = $wpdb->get_col("
    SELECT DISTINCT meta_value
    FROM {$wpdb->usermeta}
    WHERE meta_key = '{$meta_key}'
");
$papeis = [];
foreach ($papeis_em_uso as $meta_value) {
    $caps = maybe_unserialize($meta_value);

    if (is_array($caps)) {
        $roles = array_keys($caps);
        foreach ($roles as $role) {
            $papeis[$role] = $role; // evita duplicados
        }
    }
}
$papeis_em_uso = array_values($papeis);
/* =========================================================
 * CONTAGEM TOTAL (Para o Card)
 * ========================================================= */
$sql_count = "SELECT COUNT(c.id_colaborador) 
              FROM {$prefix}tb_colaboradores c 
              LEFT JOIN {$wpdb->usermeta} um ON um.user_id = c.id_user AND um.meta_key = '{$wpdb->prefix}capabilities'
              {$where_sql}";
$total_colaboradores = $wpdb->get_var($sql_count);

/* =========================================================
 * CONSULTA
 * ========================================================= */
$sql = "
SELECT
    c.id_colaborador,
    c.id_user,
    c.nom_eleitor,
    c.num_cpf,
    c.num_inscricao,
    c.ds_status_eleitoral,
    c.num_telefone_eleitor,
    c.num_telefone_eleitor_2,
    c.email_colaborador,
    c.id_upload_foto,
    c.num_local_votacao,
    l.nom_local AS nom_local_votacao,
    l.nom_municipio AS nom_municipio_votacao,
    t.id_vaga_pleito,
    t.nom_funcao,
    t.num_secao AS num_secao_trabalho,
    t.nom_local AS nom_local_trabalho,
    t.nom_municipio AS nom_municipio_trabalho,
    um.meta_value AS papel

FROM {$prefix}tb_colaboradores c

LEFT JOIN {$prefix}vw_local_votacao_colaborador l 
    ON l.id_colaborador = c.id_colaborador

LEFT JOIN {$prefix}vw_local_trabalho_colaborador t
    ON t.id_colaborador = c.id_colaborador
-- vínculo com usuário WP
LEFT JOIN {$wpdb->usermeta} um
    ON um.user_id = c.id_user
   AND um.meta_key = '{$wpdb->prefix}capabilities'

{$where_sql}

ORDER BY c.nom_eleitor ASC
LIMIT 15
";

$usuarios = $wpdb->get_results($sql);
?>

<style>
    :root {
        --primary: #2271b1;
        --bg-body: #f0f2f5;
        --white: #ffffff;
        --border: #e2e8f0;
        --text-dark: #1e293b;
        --text-muted: #64748b;
        --whatsapp: #25D366;
    }

    .zelegal-admin { max-width: 1400px; margin: 20px auto; padding: 0 20px; font-family: 'Inter', -apple-system, sans-serif; }
    
    .ze-header-main h1 { font-size: 28px; font-weight: 800; color: var(--text-dark); margin-bottom: 5px; }
    .ze-header-main p { color: var(--text-muted); margin-bottom: 25px; }

    .ze-filter-card {
        background: var(--white); border-radius: 12px; padding: 24px; margin-bottom: 24px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid var(--border);
    }
    .ze-filter-card h3 { margin-top: 0; font-size: 16px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; margin-bottom: 15px; }
    .ze-filter-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
    .ze-filter-group { display: flex; flex-direction: column; gap: 6px; }
    .ze-filter-group label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
    
    .ze-filter-row input, .ze-filter-row select {
        height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; min-width: 220px; font-size: 14px;
    }
    
    .ze-btn-filter { 
        background: var(--primary); color: #fff; border: none; padding: 0 25px; height: 42px; 
        border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;
    }
    .ze-btn-filter:hover { background: #1a5a8e; }

    /* Estilo do Botão Novo Colaborador */
    .ze-btn-new {
        background: #28a745; color: #fff; border: none; padding: 0 25px; height: 42px; 
        border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    }
    .ze-btn-new:hover { background: #218838; color: #fff; }

    .ze-table-card { background: var(--white); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .ze-table { width: 100%; border-collapse: collapse; text-align: left; }
    .ze-table th { background: #f8fafc; padding: 15px 20px; font-size: 12px; font-weight: 700; color: var(--text-muted); border-bottom: 2px solid var(--border); text-transform: uppercase; }
    .ze-table td { padding: 16px 20px; border-bottom: 1px solid var(--border); vertical-align: middle; font-size: 14px; color: var(--text-dark); }
    .ze-table tr:hover { background: #f1f5f9; }

    .ze-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .ze-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
    
    .status-eleitoral-DISPONIVEL { background:#dcfce7; color:#166534; }
    .status-eleitoral-RESERVADA { background:#fef3c7; color:#92400e; }
    .status-eleitoral-PRE_SELECIONADA { background:#dbeafe; color:#1e40af; }
    .status-eleitoral-DISPENSADO { background:#fee2e2; color:#991b1b; }

    .papel-badge { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .ze-contact-info div { line-height: 1.4; }
    .ze-contact-info strong { display: block; color: var(--text-dark); }
    .ze-contact-info small { color: var(--text-muted); font-size: 12px; }

    .ze-btn-edit {
        text-decoration: none; padding: 7px 15px; border-radius: 8px; font-size: 13px; font-weight: 600;
        color: var(--primary); border: 1px solid var(--primary); transition: 0.2s; display: inline-flex; align-items: center; gap: 5px;
    }
    .ze-btn-edit:hover { background: var(--primary); color: #fff; }

    .ze-btn-whatsapp {
        text-decoration: none; padding: 7px 15px; border-radius: 8px; font-size: 13px; font-weight: 600;
        color: var(--whatsapp); border: 1px solid var(--whatsapp); transition: 0.2s; display: inline-flex; align-items: center; gap: 5px; margin-right: 5px;
    }
    .ze-btn-whatsapp:hover { background: var(--whatsapp); color: #fff; }

    .ze-filter-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .ze-counter-card {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 20px;
        text-align: center;
        min-width: 120px;
    }

    .ze-counter-label {
        font-size: 10px;
        text-transform: uppercase;
        color: var(--text-muted);
        font-weight: 700;
        display: block;
        margin-bottom: 2px;
    }

    .ze-counter-value {
        font-size: 24px;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
    }
    @media (max-width: 1024px) { .ze-table { display: block; overflow-x: auto; } }    
</style>

<div class="zelegal-admin">
        <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <h1 class="ze-page-title">Gestão de Colaboradores</h1>

    <div class="ze-header-main">
        <p>Visualize e gerencie as permissões e status de todos os usuários do sistema.</p>
    </div>
    
    <div class="ze-counter-card">
                    <span class="ze-counter-label">Cadastrados</span>
                    <span class="ze-counter-value"><?php echo number_format($total_colaboradores, 0, '', '.'); ?></span>
                </div>

    <form method="get">
        <input type="hidden" name="page" value="ze-legal-colaboradores">
        <div class="ze-filter-card">
            <h3>🔍 Filtros de Pesquisa</h3>
            <div class="ze-filter-row">
                <div class="ze-filter-group">
                    <label>Busca Rápida</label>
                    <input type="text" name="busca" placeholder="Nome, CPF ou Inscrição..." value="<?php echo esc_attr($filtros['busca']); ?>">
                </div>

                <div class="ze-filter-group">
                    <label>Situação Eleitoral</label>
                    <select name="status_eleitoral">
                        <option value="">Todos os Status</option>
                        <?php foreach ($status_em_uso as $s): ?>
                            <option value="<?php echo esc_attr($s); ?>" <?php selected($filtros['status_eleitoral'], $s); ?>><?php echo esc_html($s); ?></option>
                        <?php endforeach; ?>
                    </select>
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

                <a href="<?php echo esc_url(admin_url('admin.php?page=ze-legal-colaboradores-incluir')); ?>" class="ze-btn-new">
                    <span class="dashicons dashicons-plus-alt"></span> Novo Colaborador
                </a>
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
                    <th>Acesso</th>
                    <th>Local Votação</th>
                    <th>Alocação (Pleito)</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td style="width: 70px;">
                            <?php 
                            if ($u->id_upload_foto) {
                                echo wp_get_attachment_image($u->id_upload_foto, [50, 50], false, ['class' => 'ze-avatar']);
                            } else {
                                echo '<div class="ze-avatar" style="background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:18px;">👤</div>';
                            }
                            ?>
                        </td>

                        <td>
                            <div style="font-weight: 700; color: var(--text-dark);"><?php echo esc_html($u->nom_eleitor); ?></div>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                CPF: <?php echo esc_html(cpf_mascara($u->num_cpf)); ?> <br>
                                Título: <?php echo esc_html($u->num_inscricao); ?>
                            </div>
                        </td>

                        <td>
                            <div class="ze-contact-info">
                                <strong><?php echo esc_html(ze_telefone_mascara($u->num_telefone_eleitor)); ?></strong>
                                <?php if($u->num_telefone_eleitor_2): ?>
                                    <small><?php echo esc_html(ze_telefone_mascara($u->num_telefone_eleitor_2)); ?></small>
                                <?php endif; ?>
                                <small><?php echo esc_html($u->email_colaborador ?: 'Sem e-mail'); ?></small>
                            </div>
                        </td>

                        <td>
                            <span class="ze-badge status-eleitoral-<?php echo esc_attr($u->ds_status_eleitoral); ?>">
                                <?php echo esc_html($u->ds_status_eleitoral); ?>
                            </span>
                        </td>

                        <td>
                            <span class="ze-badge papel-badge">
                                <?php echo esc_html(ze_get_wp_role_from_meta($u->papel)); ?> 
                            </span>
                        </td>

                        <td style="font-size: 12px; color: var(--text-muted); max-width: 180px;">
                            <?php
                                echo esc_html(
                                    ($u->nom_local_votacao || $u->nom_municipio_votacao)
                                        ? trim(($u->nom_local_votacao ?? '') . ' — ' . ($u->nom_municipio_votacao ?? ''), ' —')
                                        : '—'
                                );
                            ?>
                        </td>

                        <td>
                            <?php if ($u->id_vaga_pleito): ?>
                        
                                <div style="font-weight: 600; font-size: 13px;">
                                    <?php echo esc_html($u->nom_funcao); ?>
                                </div>
                        
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    Seção: <?php echo esc_html($u->num_secao_trabalho ?: '—'); ?>
                                </div>
                        
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    <?php echo esc_html($u->nom_local_trabalho ?: '—'); ?>
                                </div>
                        
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    <?php echo esc_html($u->nom_municipio_trabalho ?: '—'); ?>
                                </div>
                        
                            <?php else: ?>
                        
                                <span style="color: #cbd5e1; font-style: italic; font-size: 13px;">
                                    Não alocado
                                </span>
                        
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <?php
                                if ( ! empty( $u->id_usuario ) ) {
                                
                                    if ( ! empty( $u->id_colaborador ) && function_exists('ze_legal_gerar_acesso_completo_colaborador') ) {
                                        ?>
                                        <form method="post"
                                              action="<?php echo admin_url('admin-post.php'); ?>"
                                              style="display:inline;"
                                              target="_blank">
                                        
                                            <input type="hidden" name="action" value="ze_enviar_token">
                                        
                                            <input type="hidden" name="id_colaborador"
                                                   value="<?php echo esc_attr( $u->id_colaborador ); ?>">
                                        
                                            <?php wp_nonce_field( 'ze_token_action_' . $u->id_colaborador ); ?>
                                        
                                            <button type="submit" class="ze-btn-whatsapp">
                                                <span class="dashicons dashicons-whatsapp"></span> Enviar token
                                            </button>
                                        </form>
                                        <?php
                                    }
                                }
                                ?>
                                <a class="ze-btn-edit" href="<?php echo esc_url(admin_url('admin.php?page=ze-legal-colaboradores-editar&id_colaborador=' . $u->id_colaborador)); ?>">
                                    <span class="dashicons dashicons-edit"></span> Editar
                                </a>
                            </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">Nenhum colaborador encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>