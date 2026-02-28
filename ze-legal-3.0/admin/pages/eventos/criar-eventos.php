<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: ajuste a capability se necessário
if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

// Tabelas
$table_vagas        = "{$prefix}tb_vagas_pleitos";
$table_locais       = "{$prefix}tb_locais";
$table_tipos_locais = "{$prefix}tb_tipos_locais";
$table_funcoes      = "{$prefix}tb_funcoes";
$table_eventos      = "{$prefix}tb_eventos_vagas";
$table_tipos_evento = "{$prefix}tb_tipos_eventos";

$mensagem = '';
$erro = '';

/* =============================
 * PROCESSAMENTO E PERSISTÊNCIA
 * ============================= */
$data_evento        = sanitize_text_field( $_POST['data_evento'] ?? '' );
$id_tipo_evento     = intval( $_POST['id_tipo_evento'] ?? 0 );
$id_tipo_local_sel  = intval( $_POST['id_tipo_local'] ?? 0 );
$id_local_sel       = intval( $_POST['id_local'] ?? 0 );
$id_funcao_sel      = intval( $_POST['id_funcao'] ?? 0 );
$hora_inicio        = sanitize_text_field( $_POST['hora_inicio'] ?? '' );
$hora_fim           = sanitize_text_field( $_POST['hora_fim'] ?? '' );
$vale_alimentacao   = isset( $_POST['vale_alimentacao'] ) ? 1 : 0;
$ds_local_evento    = sanitize_text_field( $_POST['ds_local_evento'] ?? '' );

if ( isset( $_POST['ze_gerar_eventos_nonce'] ) && wp_verify_nonce( $_POST['ze_gerar_eventos_nonce'], 'ze_gerar_eventos' ) ) {
    if ( ! $data_evento || ! $id_tipo_evento ) {
        $erro = 'A data e o tipo de evento são campos obrigatórios.';
    } else {
        $sql = "SELECT v.id_vaga_pleito FROM {$table_vagas} v 
                INNER JOIN {$table_locais} l ON v.id_local = l.id_local 
                WHERE 1=1";
        $params = [];
        if ( $id_local_sel ) { $sql .= " AND v.id_local = %d"; $params[] = $id_local_sel; }
        if ( $id_tipo_local_sel ) { $sql .= " AND l.id_tipo_local = %d"; $params[] = $id_tipo_local_sel; }
        if ( $id_funcao_sel ) { $sql .= " AND v.id_funcao = %d"; $params[] = $id_funcao_sel; }

        if ( ! empty( $params ) ) { $sql = $wpdb->prepare( $sql, $params ); }
        $vagas = $wpdb->get_results( $sql );

        if ( ! $vagas ) {
            $erro = 'Nenhuma vaga encontrada para os filtros aplicados.';
        } else {
            $criados = 0; $ignorados = 0;
            foreach ( $vagas as $v ) {
                $existe = $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table_eventos} WHERE id_vaga_pleito = %d AND data_evento = %s AND id_tipo_evento = %d", $v->id_vaga_pleito, $data_evento, $id_tipo_evento ) );
                if ( $existe ) { $ignorados++; continue; }

                $wpdb->insert($table_eventos, [
                    'id_vaga_pleito'   => $v->id_vaga_pleito,
                    'id_tipo_evento'  => $id_tipo_evento,
                    'data_evento'     => $data_evento,
                    'hora_inicio'     => $hora_inicio ?: null,
                    'hora_fim'        => $hora_fim ?: null,
                    'vale_alimentacao'=> $vale_alimentacao,
                    'ds_local_evento' => $ds_local_evento,
                    'created_at'      => current_time( 'mysql' ),
                ]);
                $criados++;
            }

            /**
             * SINCRONIZAÇÃO
             * Chama a página para vincular os colaboradores aos eventos criados
             */
            if ( $criados > 0 ) {
                require_once ZE_LEGAL_PATH . 'domain/vagas/sincroniza-eventos.php';
            }

            $mensagem = "Sucesso! {$criados} eventos gerados.";
            if ( $ignorados ) $mensagem .= " ({$ignorados} já existiam).";
        }
    }
}

/* =============================
 * LISTAS FILTRADAS
 * ============================= */
$tipos_local_lista = $wpdb->get_results("SELECT DISTINCT tl.id_tipo_local, tl.ds_tipo_local FROM {$table_tipos_locais} tl INNER JOIN {$table_locais} l ON tl.id_tipo_local = l.id_tipo_local INNER JOIN {$table_vagas} v ON l.id_local = v.id_local ORDER BY tl.ds_tipo_local ASC");
$locais_lista = $wpdb->get_results("SELECT DISTINCT l.id_local, l.nom_local FROM {$table_locais} l INNER JOIN {$table_vagas} v ON l.id_local = v.id_local ORDER BY l.nom_local ASC");
$funcoes_lista = $wpdb->get_results("SELECT DISTINCT f.id_funcao, f.nom_funcao FROM {$table_funcoes} f INNER JOIN {$table_vagas} v ON f.id_funcao = v.id_funcao WHERE f.status_funcao = 1 ORDER BY f.nom_funcao ASC");
$tipos_evento_lista = $wpdb->get_results("SELECT id_tipo_evento, ds_tipo_evento FROM {$table_tipos_evento} WHERE status = 1 ORDER BY ds_tipo_evento ASC");
$resumo_eventos = $wpdb->get_results("SELECT data_evento, COUNT(*) as total FROM {$table_eventos} GROUP BY data_evento ORDER BY data_evento DESC LIMIT 6");
?>

<style>
    .ze-wrapper { max-width: 760px; margin: 20px 0; font-family: 'Segoe UI', system-ui, sans-serif; }
    .ze-premium-card { background: #fff; border-radius: 12px; border: 1px solid #e0e4e8; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 25px; }
    .ze-header { margin-bottom: 25px; border-left: 5px solid #2271b1; padding-left: 15px; }
    .ze-header h1 { font-size: 22px; margin: 0; color: #1d2327; }
    .ze-row { display: flex; gap: 20px; margin-bottom: 20px; align-items: flex-end; }
    .ze-form-group { margin-bottom: 20px; }
    .ze-label { display: block; font-weight: 600; font-size: 13px; color: #50575e; margin-bottom: 6px; }
    .ze-input { border: 1.5px solid #d1d5db; border-radius: 6px; padding: 8px 12px; font-size: 14px; transition: border-color 0.2s; background: #f9fafb; max-width: 100%; }
    .ze-input:focus { border-color: #2271b1; outline: none; background: #fff; box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1); }
    .ze-vale-highlight { background: #f0f7ff; border: 1px solid #c2d9ff; border-radius: 8px; padding: 15px; margin: 25px 0; display: flex; align-items: center; }
    .ze-vale-highlight label { cursor: pointer; display: flex; align-items: center; font-weight: 600; color: #1e40af; margin: 0; }
    .ze-vale-highlight input { margin-right: 12px; transform: scale(1.2); }
    .ze-filter-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 25px 20px 5px 20px; position: relative; margin-top: 10px; }
    .ze-filter-box::before { content: "FILTROS DE SELEÇÃO (DESTINO)"; position: absolute; top: -10px; left: 20px; background: #fff; padding: 0 10px; font-size: 11px; font-weight: 800; color: #9ca3af; letter-spacing: 0.5px; }
    .ze-btn-submit { background: #2271b1; color: white; border: none; padding: 14px 28px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(34, 113, 177, 0.2); transition: all 0.2s; }
    .ze-btn-submit:hover { background: #135e96; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(34, 113, 177, 0.3); }
    .ze-stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; }
    .ze-stat-card { background: #fff; border: 1px solid #e0e4e8; border-radius: 8px; padding: 12px; text-align: center; transition: transform 0.2s; }
    .ze-stat-card:hover { transform: translateY(-2px); border-color: #2271b1; }
    .ze-stat-date { font-size: 11px; font-weight: 700; color: #646970; margin-bottom: 4px; display: block; }
    .ze-stat-num { font-size: 20px; font-weight: 800; color: #2271b1; }
</style>

<div class="ze-admin-container">
    <div class="ze-header">
        <h1>Gerador de Eventos em Massa</h1>
        <p class="description">Gere eventos de forma automatizada com base nos filtros operacionais.</p>
    </div>

    <?php if ( $erro ) : ?><div class="notice notice-error" style="border-radius:6px;"><p><?php echo esc_html( $erro ); ?></p></div><?php endif; ?>
    <?php if ( $mensagem ) : ?><div class="notice notice-success is-dismissible" style="border-radius:6px;"><p><?php echo esc_html( $mensagem ); ?></p></div><?php endif; ?>

    <div class="ze-premium-card">
        <form method="post">
            <?php wp_nonce_field( 'ze_gerar_eventos', 'ze_gerar_eventos_nonce' ); ?>

            <div class="ze-form-group">
                <label class="ze-label">Data do Evento *</label>
                <input type="date" name="data_evento" class="ze-input" style="width: 200px;" value="<?php echo esc_attr($data_evento); ?>" required>
            </div>

            <div class="ze-form-group">
                <label class="ze-label">Tipo de Evento *</label>
                <select name="id_tipo_evento" class="ze-input" style="width: 100%; max-width: 450px;" required>
                    <option value="">Selecione o tipo de evento...</option>
                    <?php foreach ( $tipos_evento_lista as $t ): ?>
                        <option value="<?php echo intval($t->id_tipo_evento); ?>" <?php selected($id_tipo_evento, $t->id_tipo_evento); ?>>
                            <?php echo esc_html($t->ds_tipo_evento); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="ze-row">
                <div class="ze-form-group" style="margin-bottom:0;">
                    <label class="ze-label">Hora Início</label>
                    <input type="time" name="hora_inicio" class="ze-input" style="width: 130px;" value="<?php echo esc_attr($hora_inicio); ?>">
                </div>
                <div class="ze-form-group" style="margin-bottom:0;">
                    <label class="ze-label">Hora Fim</label>
                    <input type="time" name="hora_fim" class="ze-input" style="width: 130px;" value="<?php echo esc_attr($hora_fim); ?>">
                </div>
            </div>

            <div class="ze-form-group" style="margin-top: 20px;">
                <label class="ze-label">Local de Realização (Descrição)</label>
                <input type="text" name="ds_local_evento" class="ze-input" style="width: 100%;" value="<?php echo esc_attr($ds_local_evento); ?>" placeholder="Ex: Auditório Central, 2º Andar">
            </div>

            <div class="ze-vale-highlight">
                <label>
                    <input type="checkbox" name="vale_alimentacao" value="1" <?php checked($vale_alimentacao, 1); ?>>
                    🍴 Este evento dará direito a Vale Alimentação
                </label>
            </div>

            <div class="ze-filter-box">
                <div class="ze-form-group">
                    <label class="ze-label">Tipo de Local</label>
                    <select name="id_tipo_local" class="ze-input" style="width: 100%;">
                        <option value="">Todos os tipos usados</option>
                        <?php foreach ( $tipos_local_lista as $tl ): ?>
                            <option value="<?php echo intval($tl->id_tipo_local); ?>" <?php selected($id_tipo_local_sel, $tl->id_tipo_local); ?>><?php echo esc_html($tl->ds_tipo_local); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ze-form-group">
                    <label class="ze-label">Local de Votação</label>
                    <select name="id_local" class="ze-input" style="width: 100%;">
                        <option value="">Todos os locais com vagas</option>
                        <?php foreach ( $locais_lista as $l ): ?>
                            <option value="<?php echo intval($l->id_local); ?>" <?php selected($id_local_sel, $l->id_local); ?>><?php echo esc_html($l->nom_local); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ze-form-group">
                    <label class="ze-label">Função Eleitoral</label>
                    <select name="id_funcao" class="ze-input" style="width: 100%;">
                        <option value="">Todas as funções com vagas</option>
                        <?php foreach ( $funcoes_lista as $f ): ?>
                            <option value="<?php echo intval($f->id_funcao); ?>" <?php selected($id_funcao_sel, $f->id_funcao); ?>><?php echo esc_html($f->nom_funcao); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="text-align: right; margin-top: 30px;">
                <button type="submit" class="ze-btn-submit">Gerar Eventos em Massa</button>
            </div>
        </form>
    </div>

    <div style="padding: 0 5px;">
        <h3 style="font-size: 12px; color: #8c8f94; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px;">Últimos Eventos Registrados</h3>
        <div class="ze-stats-grid">
            <?php if($resumo_eventos): foreach($resumo_eventos as $r): ?>
                <div class="ze-stat-card">
                    <span class="ze-stat-date"><?php echo date('d/m/Y', strtotime($r->data_evento)); ?></span>
                    <span class="ze-stat-num"><?php echo $r->total; ?></span>
                </div>
            <?php endforeach; else: ?>
                <p style="color: #9ca3af; font-size: 13px; font-style: italic;">Nenhum evento registrado ainda.</p>
            <?php endif; ?>
        </div>
    </div>
</div>