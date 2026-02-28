<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_profile_edit' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_tb_';
$current_user_id = get_current_user_id();

$id_colaborador = $wpdb->get_var($wpdb->prepare(
    "SELECT id_colaborador 
     FROM {$prefix}colaboradores 
     WHERE id_user = %d 
     LIMIT 1",
    $current_user_id
));

if ( ! $id_colaborador ) {
    wp_die( 'Colaborador não identificado.' );
}

$id_pleito = $wpdb->get_var("SELECT id_pleito FROM {$prefix}pleitos WHERE status_pleito = 1 LIMIT 1");
if ( ! $id_pleito ) wp_die( 'Pleito ativo não encontrado.' );

$local_origem = $wpdb->get_row($wpdb->prepare(
    "SELECT l.id_local, l.nom_local FROM {$prefix}vagas_pleitos vp 
     INNER JOIN {$prefix}locais l ON l.id_local = vp.id_local 
     WHERE vp.id_colaborador = %d  AND l.flg_check_in = 1 AND vp.id_pleito = %d LIMIT 1", 
    $id_colaborador, $id_pleito
));
// AND l.flg_check_in = 1
if ( ! $local_origem ) wp_die( 'Voce não esta lotado em equipe volante, checkin não autorizado.' );

// Processamento
if ( isset( $_POST['registrar_checkin'] ) ) {
    check_admin_referer( 'registrar_checkin_action', 'registrar_checkin_nonce' );
    $id_local_destino = intval( $_POST['id_local_destino'] );
    if ( $id_local_destino > 0 ) {
        $wpdb->insert("{$prefix}checkin_local", [
            'id_pleito'         => $id_pleito,
            'id_local_origem'   => intval( $local_origem->id_local ),
            'id_local_destino'  => $id_local_destino,
            'id_colaborador'    => $id_colaborador,
            'data_hora_checkin' => current_time( 'mysql' ),
        ], [ '%d', '%d', '%d', '%d', '%s' ]);
        echo '<div class="notice notice-success is-dismissible"><p>✅ Check-in realizado!</p></div>';
    }
}

$locais_destino = $wpdb->get_results("SELECT l.id_local, l.nom_local, m.nom_municipio FROM {$prefix}locais l 
                      INNER JOIN {$prefix}municipios m ON m.id_municipio = l.id_municipio  
                      INNER JOIN {$prefix}tipos_locais tl ON tl.id_tipo_local = l.id_tipo_local 
                      WHERE tl.ds_tipo_local = 'local de votação' AND l.status_local = 1 ORDER BY l.nom_local");
$meus_checkins = $wpdb->get_results($wpdb->prepare("SELECT c.data_hora_checkin, lo.nom_local AS local_origem, ld.nom_local AS local_destino FROM {$prefix}checkin_local c INNER JOIN {$prefix}locais lo ON lo.id_local = c.id_local_origem INNER JOIN {$prefix}locais ld ON ld.id_local = c.id_local_destino WHERE c.id_local_origem = %d AND c.id_pleito = %d ORDER BY c.data_hora_checkin DESC", intval( $local_origem->id_local ), $id_pleito));
?>

<style>
    :root {
        --ze-primary: #2271b1;
        --ze-bg: #f0f2f5;
        --ze-card-bg: #ffffff;
        --ze-text: #1d2327;
    }

    .ze-premium-container {
        max-width: 600px;
        margin: 20px auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    /* Card de Ação Principal */
    .ze-card {
        background: var(--ze-card-bg);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 24px;
        margin-bottom: 24px;
        border: 1px solid #e2e8f0;
    }

    .ze-card-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--ze-text);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ze-badge-origem {
        display: block;
        background: #e7f0ff;
        color: var(--ze-primary);
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 600;
        margin-bottom: 20px;
        border-left: 4px solid var(--ze-primary);
    }

    /* Inputs e Selects Mobile-First */
    .ze-form-group select {
        width: 100% !important;
        height: 50px !important;
        font-size: 16px !important; /* Evita zoom automático no iOS */
        border-radius: 8px !important;
        border: 1px solid #ccd0d4 !important;
        margin-bottom: 15px !important;
    }

    .ze-btn-full {
        width: 100%;
        height: 54px;
        background: var(--ze-primary) !important;
        color: white !important;
        border: none !important;
        border-radius: 8px !important;
        font-size: 1.1rem !important;
        font-weight: 600 !important;
        cursor: pointer;
        transition: transform 0.1s;
    }

    .ze-btn-full:active {
        transform: scale(0.98);
    }

    /* Histórico em Lista (Cards menores) */
    .ze-history-item {
        background: #fff;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .ze-history-date {
        font-size: 0.8rem;
        color: #646970;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .ze-history-route {
        font-weight: 500;
        color: #2c3338;
    }

    .ze-history-route span {
        color: #94a3b8;
        padding: 0 5px;
    }

    @media (max-width: 480px) {
        .ze-premium-container { margin: 10px; }
        .ze-card { padding: 18px; }
    }
</style>

<div class="ze-admin-container">
     <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>
    
    <h1 class="wp-heading-inline">📍 Sistema de Check-in</h1>
    
    <div class="ze-card">
        <div class="ze-card-title">Equipe Volante</div>
        
        <div class="ze-badge-origem">
            <small style="display:block; font-weight:normal; opacity: 0.8;">Equipe:</small>
            <?php echo esc_html( $local_origem->nom_local ); ?>
        </div>

        <form method="post">
            <?php wp_nonce_field( 'registrar_checkin_action', 'registrar_checkin_nonce' ); ?>
            
            <div class="ze-form-group">
                <label style="display:block; margin-bottom: 8px; font-weight: 500;">Selecione o Destino:</label>
                <select name="id_local_destino" required>
                    <option value="">Clique para selecionar...</option>
                    <?php foreach ( $locais_destino as $local ) : ?>
                        <option value="<?php echo esc_attr( $local->id_local ); ?>">
                            <?php echo esc_html("{$local->nom_local} - {$local->nom_municipio}"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="registrar_checkin" class="ze-btn-full">
                Confirmar Check-in
            </button>
        </form>
    </div>

    <h2 style="font-size: 1.1rem; margin: 30px 0 15px 5px;">Meus Check-ins Recentes</h2>

    <?php if ( empty( $meus_checkins ) ) : ?>
        <p style="text-align: center; color: #666; padding: 20px;">Nenhum registro encontrado.</p>
    <?php else : ?>
        <div class="ze-history-list">
            <?php foreach ( $meus_checkins as $checkin ) : ?>
                <div class="ze-history-item">
                    <div class="ze-history-date">
                        🗓️ <?php echo esc_html( date( 'd/m/Y - H:i', strtotime( $checkin->data_hora_checkin ) ) ); ?>
                    </div>
                    <div class="ze-history-route">
                        <strong><?php echo esc_html( $checkin->local_origem ); ?></strong>
                        <span>➔</span>
                        <strong><?php echo esc_html( $checkin->local_destino ); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>