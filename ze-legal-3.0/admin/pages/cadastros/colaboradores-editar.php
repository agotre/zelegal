<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

$id_colaborador = isset($_GET['id_colaborador']) ? intval($_GET['id_colaborador']) : 0;
if ( ! $id_colaborador ) {
    wp_die('Colaborador não informado.');
}

$colaborador = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM {$prefix}tb_colaboradores WHERE id_colaborador = %d", $id_colaborador)
);

if ( ! $colaborador ) {
    wp_die('Colaborador não encontrado.');
}

/* =========================================================
 * PROCESSAMENTO DO FORMULÁRIO (UPDATE)
 * ========================================================= */
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    check_admin_referer( 'ze_cadastro_colaborador_editar' );

    $data = [
        'nom_eleitor'            => sanitize_text_field($_POST['nom_eleitor'] ?? ''),
        'num_inscricao'          => sanitize_text_field($_POST['num_inscricao'] ?? ''),
        'num_zona_votacao'       => sanitize_text_field($_POST['num_zona_votacao'] ?? ''),
        'num_secao_votacao'      => str_pad(preg_replace('/\D/', '', $_POST['num_secao_votacao'] ?? ''), 4, '0', STR_PAD_LEFT),
        'num_local_votacao'      => sanitize_text_field($_POST['num_local_votacao'] ?? ''),
        'nom_municipio_votacao'  => sanitize_text_field($_POST['nom_municipio_votacao'] ?? ''),
        'num_telefone_eleitor'   => sanitize_text_field($_POST['num_telefone_eleitor'] ?? ''),
        'num_telefone_eleitor_2' => sanitize_text_field($_POST['num_telefone_eleitor_2'] ?? ''),
        'email_colaborador'      => sanitize_email($_POST['email'] ?? ''),
        'endereco_atualizado'    => sanitize_text_field($_POST['endereco_atualizado'] ?? ''),
        'ds_camiseta'            => sanitize_text_field($_POST['camiseta'] ?? ''),
        'ds_experiencia'         => sanitize_textarea_field($_POST['ds_experiencia'] ?? ''),
        'updated_at'             => current_time('mysql')
    ];

    // Lógica de Upload da Foto
    if ( ! empty( $_FILES['foto_colaborador']['name'] ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        $attachment_id = media_handle_upload( 'foto_colaborador', 0 );

        if ( ! is_wp_error( $attachment_id ) ) {
            $data['id_upload_foto'] = $attachment_id; // Grava o ID da mídia no banco
        }
    }

    $wpdb->update( "{$prefix}tb_colaboradores", $data, [ 'id_colaborador' => $id_colaborador ] );

    wp_safe_redirect(admin_url('admin.php?page=ze-legal-colaboradores&msg=atualizado'));
    exit;
}

$zonas      = $wpdb->get_results("SELECT num_zona, descricao FROM {$prefix}tb_zonas ORDER BY num_zona");
$locais     = $wpdb->get_results("SELECT l.num_local, l.nom_local, m.nom_municipio FROM {$prefix}tb_locais l JOIN  {$prefix}tb_municipios m ON l.id_municipio = m.id_municipio WHERE status_local = 1 ORDER BY nom_local");
$municipios = $wpdb->get_results("SELECT nom_municipio_elo FROM {$prefix}tb_municipios ORDER BY nom_municipio_elo ASC");
?>

<style>
    :root { --primary: #2271b1; --bg: #f0f2f5; --text: #1e293b; --text-sub: #64748b; --border: #e2e8f0; }
    .ze-premium-wrap { max-width: 1000px; margin: 20px auto; padding: 0 20px; font-family: 'Segoe UI', system-ui, sans-serif; }
    .ze-card { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid var(--border); }
    .ze-card h2 { font-size: 20px; color: var(--text); margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; display: flex; align-items: center; gap: 10px; }
    .ze-form-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
    .ze-field { display: flex; flex-direction: column; gap: 6px; }
    .col-2 { grid-column: span 2; } .col-3 { grid-column: span 3; } .col-4 { grid-column: span 4; } .col-6 { grid-column: span 6; } .col-8 { grid-column: span 8; } .col-10 { grid-column: span 10; } .col-12 { grid-column: span 12; }
    .ze-field label { font-size: 11px; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: 0.5px; }
    .ze-field input, .ze-field select, .ze-field textarea { height: 40px; border: 1.5px solid var(--border); border-radius: 8px; padding: 0 12px; font-size: 14px; background: #fcfcfd; transition: all 0.2s ease; }
    .ze-field input[type="file"] { padding: 5px; height: auto; }
    .ze-field textarea { height: 100px; padding: 12px; resize: vertical; }
    .ze-field input:focus, .ze-field select:focus { border-color: var(--primary); outline: none; background: #fff; box-shadow: 0 0 0 3px rgba(34,113,177,0.1); }
    .ze-field input[readonly] { background: #f8fafc; color: var(--text-sub); cursor: not-allowed; border-style: dashed; }
    
    /* Foto Preview - Ajustado para aparecer foto inteira e quadrada */
    .ze-photo-preview { 
        width: 120px;           /* Largura fixa */
        height: 120px;          /* Altura igual para ficar quadrado */
        border-radius: 8px; 
        border: 2px dashed var(--border); 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        overflow: hidden; 
        background: #f8fafc; 
        margin-bottom: 5px; 
    }
    
    .ze-photo-preview img { 
        width: 100%; 
        height: 100%; 
        object-fit: contain;    /* Ajusta a imagem para caber inteira sem cortar */
        background: #eee;       /* Fundo leve caso a foto seja proporcionalmente diferente */
    }
    
    .ze-photo-preview i { 
        font-size: 30px; 
        color: #cbd5e1; 
    }

    .ze-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0 25px; height: 42px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; text-decoration: none; border: none; }
    .ze-btn-submit { background: var(--primary); color: #fff; }
    .ze-btn-submit:hover { background: #1a5a8e; transform: translateY(-1px); }
    .ze-btn-cancel { background: transparent; color: var(--text-sub); border: 1.5px solid var(--border); }
    .ze-btn-cancel:hover { background: #f8fafc; color: var(--text); border-color: #cbd5e1; }
    .ze-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
</style>

<div class="ze-premium-wrap">
    <div class="ze-header-title">
        <h1 style="font-weight: 800; color: #0f172a;">Editar Colaborador</h1>
    </div>
    
    <div class="ze-card">
        <h2><span class="dashicons dashicons-businessman"></span> Ficha do Colaborador: <?php echo esc_html($colaborador->nom_eleitor); ?></h2>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'ze_cadastro_colaborador_editar' ); ?>
            
            <div class="ze-form-grid">
                <div class="ze-field col-2">
                    <label>Foto Atual</label>
                    <div class="ze-photo-preview">
                        <?php 
                        $foto_id = $colaborador->id_upload_foto;
                        if ( $foto_id ) {
                            echo wp_get_attachment_image( $foto_id, 'thumbnail' );
                        } else {
                            echo '<span class="dashicons dashicons-camera"></span>';
                        }
                        ?>
                    </div>
                </div>
                <div class="ze-field col-10">
                    <label>Alterar Foto do Colaborador (Formatos: JPG, PNG)</label>
                    <input type="file" name="foto_colaborador" id="id_upload_foto" accept="image/*">
                    <small style="color: var(--text-sub);">O ID da foto no sistema é: <strong><?php echo $foto_id ? $foto_id : 'Nenhuma foto carregada'; ?></strong></small>
                </div>

                <div class="ze-field col-4">
                    <label>CPF (Inalterável)</label>
                    <input type="text" id="num_cpf" value="<?php echo esc_attr($colaborador->num_cpf); ?>" readonly>
                </div>
                <div class="ze-field col-8">
                    <label>Nome Completo do Eleitor</label>
                    <input type="text" name="nom_eleitor" value="<?php echo esc_attr($colaborador->nom_eleitor); ?>" required>
                </div>

                <div class="ze-field col-4">
                    <label>Inscrição Eleitoral</label>
                    <input type="text" name="num_inscricao" value="<?php echo esc_attr($colaborador->num_inscricao); ?>">
                </div>
                <div class="ze-field col-8">
                    <label>Zona Eleitoral</label>
                    <select name="num_zona_votacao" required>
                        <?php foreach ($zonas as $z): ?>
                            <option value="<?php echo esc_attr($z->num_zona); ?>" <?php selected($z->num_zona, $colaborador->num_zona_votacao); ?>>
                                <?php echo esc_html($z->descricao); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ze-field col-3">
                    <label>Seção</label>
                    <input type="text" name="num_secao_votacao" value="<?php echo esc_attr($colaborador->num_secao_votacao); ?>" required>
                </div>
                <div class="ze-field col-6">
                    <label>Local de Votação</label>
                    <select name="num_local_votacao" required>
                        <?php foreach ($locais as $l): ?>
                            <option value="<?php echo esc_attr($l->num_local); ?>" <?php selected($l->num_local, $colaborador->num_local_votacao); ?>>
                                <?php echo esc_html( $l->nom_local . ' - ' . $l->num_local . ' - ' . $l->nom_municipio); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="ze-field col-3">
                    <label>Município Votação</label>
                    <select name="nom_municipio_votacao" required>
                        <option value="">Selecione...</option>
                        <?php foreach ( $municipios as $m ) : ?>
                            <option value="<?php echo esc_attr( $m->nom_municipio_elo ); ?>"
                                <?php selected( $colaborador->nom_municipio_votacao, $m->nom_municipio_elo ); ?>>
                                <?php echo esc_html( $m->nom_municipio_elo ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ze-field col-4">
                    <label>Telefone Principal</label>
                    <input type="text" name="num_telefone_eleitor" id="telefone1" value="<?php echo esc_attr($colaborador->num_telefone_eleitor); ?>">
                </div>
                <div class="ze-field col-4">
                    <label>Telefone Secundário</label>
                    <input type="text" name="num_telefone_eleitor_2" id="telefone2" value="<?php echo esc_attr($colaborador->num_telefone_eleitor_2); ?>">
                </div>
                <div class="ze-field col-4">
                    <label>E-mail Pessoal</label>
                    <input type="email" name="email" value="<?php echo esc_attr($colaborador->email_colaborador); ?>">
                </div>
                <div class="ze-field col-9">
                    <label>Endereço Atualizado</label>
                    <input type="text" name="endereco_atualizado" value="<?php echo esc_attr($colaborador->endereco_atualizado); ?>">
                </div>
                <div class="ze-field col-3">
                    <label>Camiseta</label>
                    <select name="camiseta">
                        <option value="">Selecione...</option>
                        <?php foreach (['P','M','G','GG','XGG'] as $t): ?>
                            <option value="<?php echo $t; ?>" <?php selected($t, $colaborador->ds_camiseta); ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ze-field col-12">
                    <label>Experiência e Observações</label>
                    <textarea name="ds_experiencia" rows="4"><?php echo esc_textarea($colaborador->ds_experiencia); ?></textarea>
                </div>
            </div>

            <div class="ze-actions">
                <a href="<?php echo admin_url('admin.php?page=ze-legal-colaboradores'); ?>" class="ze-btn ze-btn-cancel">Cancelar / Voltar</a>
                <button type="submit" class="ze-btn ze-btn-submit"><span class="dashicons dashicons-saved" style="margin-right:8px;"></span> Salvar Alterações</button>
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

    const cpfInput = document.getElementById('num_cpf');
    if (cpfInput) cpfInput.value = maskCPF(cpfInput.value);

    ['telefone1', 'telefone2'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            if (el.value) el.value = maskTelInternacional(el.value);
            el.addEventListener('input', e => e.target.value = maskTelInternacional(e.target.value));
        }
    });
});
</script>