<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Segurança: Verifica permissão
if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

$erro = '';
$sucesso = '';

/* =========================================================
 * PROCESSAMENTO DO FORMULÁRIO
 * ========================================================= */
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {

    check_admin_referer( 'ze_cadastro_colaborador_incluir' );

    $num_cpf               = preg_replace('/\D/', '', $_POST['num_cpf'] ?? '' );
    $nom_eleitor           = sanitize_text_field( $_POST['nom_eleitor'] ?? '' );
    $num_inscricao         = sanitize_text_field( $_POST['num_inscricao'] ?? '' );
    $num_zona_votacao      = sanitize_text_field( $_POST['num_zona_votacao'] ?? '' );
    $num_secao_votacao     = str_pad( preg_replace('/\D/', '', $_POST['num_secao_votacao'] ?? ''), 4, '0', STR_PAD_LEFT );
    $num_local_votacao     = sanitize_text_field( $_POST['num_local_votacao'] ?? '' );
    $nom_municipio         = sanitize_text_field( $_POST['nom_municipio'] ?? '' );
    $num_telefone_eleitor  = sanitize_text_field( $_POST['num_telefone_eleitor'] ?? '' );
    $num_telefone_eleitor2 = sanitize_text_field( $_POST['num_telefone_eleitor_2'] ?? '' );
    $email                 = sanitize_email( $_POST['email_colaborador'] ?? '' );
    $endereco_atualizado   = sanitize_text_field( $_POST['endereco_atualizado'] ?? '' );
    $camiseta              = sanitize_text_field( $_POST['camiseta'] ?? '' );
    $ds_experiencia        = sanitize_textarea_field( $_POST['ds_experiencia'] ?? '' );

    if ( strlen( $num_cpf ) !== 11 ) {
        $erro = 'CPF inválido.';
    } else {
        $cpf_existe = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$prefix}tb_colaboradores WHERE num_cpf = %s",
                $num_cpf
            )
        );

        if ( $cpf_existe ) {
            $erro = 'CPF já cadastrado no sistema.';
        }
    }

    if ( empty( $erro ) ) {

        $insert = $wpdb->insert(
            "{$prefix}tb_colaboradores",
            [
                'num_cpf'                => $num_cpf,
                'nom_eleitor'            => $nom_eleitor,
                'num_inscricao'          => $num_inscricao,
                'num_zona_votacao'       => $num_zona_votacao,
                'num_secao_votacao'      => $num_secao_votacao,
                'num_local_votacao'      => $num_local_votacao,
                'nom_municipio_votacao'  => $nom_municipio,
                'num_telefone_eleitor'   => $num_telefone_eleitor,
                'num_telefone_eleitor_2' => $num_telefone_eleitor2,
                'email_colaborador'      => $email,
                'endereco_atualizado'    => $endereco_atualizado,
                'ds_camiseta'            => $camiseta,
                'ds_experiencia'         => $ds_experiencia,
                'ds_tipo_colaborador'    => 'CONVENCIONAL',
                'ds_status_eleitoral'    => 'DISPONIVEL',
                'created_at'             => current_time( 'mysql' )
            ]
        );

        if ( $insert === false ) {
            $erro = 'Erro ao inserir colaborador: ' . $wpdb->last_error;
        } else {
            wp_safe_redirect(
                admin_url( 'admin.php?page=ze-legal-colaboradores&msg=sucesso' )
            );
            exit;
        }
    }
}

$zonas      = $wpdb->get_results( "SELECT num_zona, descricao FROM {$prefix}tb_zonas ORDER BY num_zona" );
$locais     = $wpdb->get_results( "SELECT num_local, nom_local FROM {$prefix}tb_locais WHERE status_local = 1 ORDER BY nom_local" );
$municipios = $wpdb->get_results( "SELECT nom_municipio_elo FROM {$prefix}tb_municipios ORDER BY nom_municipio_elo ASC" );
?>
<style>
    :root { --primary: #2271b1; --bg: #f0f2f5; --text: #1e293b; --text-sub: #64748b; --border: #e2e8f0; }
    .ze-premium-wrap { max-width: 1000px; margin: 20px auto; padding: 0 20px; font-family: 'Segoe UI', system-ui, sans-serif; }
    .ze-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border); }
    .ze-card h2 { font-size: 20px; color: var(--text); margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; display: flex; align-items: center; gap: 10px; }
    .ze-form-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
    .ze-field { display: flex; flex-direction: column; gap: 6px; }
    .col-3 { grid-column: span 3; } .col-4 { grid-column: span 4; } .col-6 { grid-column: span 6; } .col-8 { grid-column: span 8; } .col-9 { grid-column: span 9; } .col-12 { grid-column: span 12; }
    .ze-field label { font-size: 11px; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; }
    .ze-field input, .ze-field select, .ze-field textarea { height: 40px; border: 1.5px solid var(--border); border-radius: 8px; padding: 0 12px; font-size: 14px; background: #fcfcfd; transition: all 0.2s ease; }
    .ze-field textarea { height: 100px; padding: 12px; resize: vertical; }
    .ze-field input:focus, .ze-field select:focus { border-color: var(--primary); outline: none; background: #fff; box-shadow: 0 0 0 3px rgba(34,113,177,0.1); }
    .ze-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0 25px; height: 42px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; text-decoration: none; }
    .ze-btn-submit { background: var(--primary); color: #fff; border: none; }
    .ze-btn-submit:hover { background: #1a5a8e; transform: translateY(-1px); }
    .ze-btn-cancel { background: transparent; color: var(--text-sub); border: 1.5px solid var(--border); }
    .ze-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
</style>

<div class="ze-premium-wrap">
    <div class="ze-header-title">
        <h1 style="font-weight: 800; color: #0f172a;"><?php echo esc_html(get_admin_page_title()); ?></h1>
    </div>

    <?php if ( $erro ) : ?>
        <div class="notice notice-error is-dismissible" style="border-radius: 10px; margin-bottom: 20px;">
            <p><strong>Erro:</strong> <?php echo esc_html( $erro ); ?></p>
        </div>
    <?php endif; ?>

    <div class="ze-card">
        <h2><span class="dashicons dashicons-businessman"></span> Dados do Colaborador</h2>
        
        <form method="post">
            <?php wp_nonce_field( 'ze_cadastro_colaborador_incluir' ); ?>

            <div class="ze-form-grid">
                <div class="ze-field col-4">
                    <label>CPF</label>
                    <input type="text" name="num_cpf" id="num_cpf" placeholder="000.000.000-00" required>
                    <small id="cpf-feedback" style="color:#dc2626; display:none; font-size: 10px; margin-top: 4px; font-weight: 700;"></small>
                </div>

                <div class="ze-field col-8">
                    <label>Nome Completo do Eleitor</label>
                    <input type="text" name="nom_eleitor" placeholder="Nome completo sem abreviações" required>
                </div>

                <div class="ze-field col-4">
                    <label>Inscrição Eleitoral</label>
                    <input type="text" name="num_inscricao" placeholder="Número do Título">
                </div>

                <div class="ze-field col-8">
                    <label>Zona Eleitoral</label>
                    <select name="num_zona_votacao" required>
                        <option value="">Selecione a Zona...</option>
                        <?php foreach ( $zonas as $z ) : ?>
                            <option value="<?php echo esc_attr( $z->num_zona ); ?>">
                                <?php echo esc_html( $z->num_zona . ' - ' . $z->descricao ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ze-field col-3">
                    <label>Seção</label>
                    <input type="text" name="num_secao_votacao" maxlength="4" placeholder="0000" required>
                </div>

                <div class="ze-field col-6">
                    <label>Local de Votação</label>
                    <select name="num_local_votacao" required>
                        <option value="">Selecione o Local...</option>
                        <?php foreach ( $locais as $l ) : ?>
                            <option value="<?php echo esc_attr( $l->num_local ); ?>">
                                <?php echo esc_html( $l->num_local . ' - ' . $l->nom_local ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ze-field col-3">
                    <label>Município Votação</label>
                    <select name="nom_municipio" required>
                        <option value="">Selecione...</option>
                        <?php foreach ( $municipios as $m ) : ?>
                            <option value="<?php echo esc_attr( $m->nom_municipio_elo ); ?>">
                                <?php echo esc_html( $m->nom_municipio_elo ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ze-field col-4">
                    <label>Telefone Principal</label>
                    <input type="text" name="num_telefone_eleitor" id="telefone1" placeholder="+55 (00) 00000-0000">
                </div>

                <div class="ze-field col-4">
                    <label>Telefone Secundário</label>
                    <input type="text" name="num_telefone_eleitor_2" id="telefone2" placeholder="+55 (00) 00000-0000">
                </div>

                <div class="ze-field col-4">
                    <label>E-mail Pessoal</label>
                    <input type="email" name="email_colaborador" placeholder="exemplo@email.com">
                </div>

                <div class="ze-field col-9">
                    <label>Endereço Atualizado</label>
                    <input type="text" name="endereco_atualizado" placeholder="Rua, Número, Bairro, Cidade">
                </div>

                <div class="ze-field col-3">
                    <label>Camiseta</label>
                    <select name="camiseta">
                        <option value="">Selecione...</option>
                        <option value="P">P</option>
                        <option value="M">M</option>
                        <option value="G">G</option>
                        <option value="GG">GG</option>
                        <option value="XGG">GG</option>
                    </select>
                </div>

                <div class="ze-field col-12">
                    <label>Experiência e Observações</label>
                    <textarea name="ds_experiencia" rows="4" placeholder="Informe experiências anteriores em eleições..."></textarea>
                </div>
            </div>

            <div class="ze-actions">
                <a href="<?php echo admin_url( 'admin.php?page=ze-legal-colaboradores' ); ?>" class="ze-btn ze-btn-cancel">
                    <span class="dashicons dashicons-undo"></span> Cancelar / Voltar
                </a>
                <button type="submit" class="ze-btn ze-btn-submit">
                    <span class="dashicons dashicons-saved"></span> Incluir Colaborador
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const maskCPF = v => v.replace(/\D/g, "").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    
    const maskTelInternacional = v => {
        v = v.replace(/\D/g, "");
        if (v.indexOf("55") === 0 && v.length > 2) v = v.substring(2);
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length > 0) v = "+55 (" + v;
        if (v.length > 7) v = v.replace(/^(\+55\s\(\d{2})(\d)/g, "$1) $2");
        if (v.length > 12) v = v.replace(/(\d)(\d{4})$/, "$1-$2");
        return v;
    };

    const cpfInput    = document.getElementById('num_cpf');
    const cpfFeedback = document.getElementById('cpf-feedback');
    const submitBtn   = document.querySelector('.ze-btn-submit');

    cpfInput.addEventListener('input', function (e) {
        e.target.value = maskCPF(e.target.value);
        cpfFeedback.style.display = 'none';
        submitBtn.disabled = false;
    });

    cpfInput.addEventListener('blur', function () {
        const cpf = cpfInput.value.replace(/\D/g, '');
        if (cpf.length !== 11) return;

        const data = new FormData();
        data.append('action', 'ze_verificar_cpf_colaborador');
        data.append('cpf', cpf);

        fetch(ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.success && resp.data.existe) {
                cpfFeedback.textContent = 'CPF já cadastrado no sistema.';
                cpfFeedback.style.display = 'block';
                submitBtn.disabled = true;
            }
        });
    });

    ['telefone1','telefone2'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function (e) {
                e.target.value = maskTelInternacional(e.target.value);
            });
        }
    });
});
</script>