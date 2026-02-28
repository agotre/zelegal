<?php
/**
 * Arquivo: meu-perfil.php
 * Descrição: Gestão de perfil do colaborador com upload de foto e dados de contato.
 */

if ( ! defined('ABSPATH') ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_profile_edit' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

// Necessário para processar uploads de mídia via PHP
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';
$tabela_colaboradores = "{$prefix}tb_colaboradores";

/**
 * 1. IDENTIFICAÇÃO DO USUÁRIO
 */
$current_wp_user_id = get_current_user_id();
if ( ! $current_wp_user_id ) {
    wp_die('Acesso não autorizado. Por favor, faça login.');
}

// Busca colaborador pelo vínculo id_user
$colaborador = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$tabela_colaboradores} WHERE id_user = %d LIMIT 1",
        $current_wp_user_id
    )
);

if ( ! $colaborador ) {
    wp_die('Seu usuário não possui um cadastro de colaborador vinculado no sistema.');
}

/**
 * 2. PROCESSAMENTO DO FORMULÁRIO (SALVAR)
 */
if ( isset($_POST['salvar_perfil']) && check_admin_referer('salvar_meu_perfil') ) {

    $compartilhar = isset($_POST['compartilhar']) ? 1 : 0;
    
    $dados = [
        'num_telefone_eleitor_2' => sanitize_text_field($_POST['telefone2']),
        'email_colaborador'      => sanitize_email($_POST['email']),
        'ds_camiseta'            => sanitize_text_field($_POST['camiseta']),
        'endereco_atualizado'    => sanitize_text_field($_POST['endereco']),
        'compartilhar_contato'   => $compartilhar,
        'updated_at'             => current_time('mysql')
    ];

    // Processamento da Foto (se enviada)
    if ( ! empty($_FILES['foto_perfil']['name']) ) {
        $file = $_FILES['foto_perfil'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];

        if ( ! in_array($file['type'], $allowed_types) ) {
            echo '<div class="notice notice-error"><p>Erro: Apenas imagens JPG ou PNG são permitidas.</p></div>';
        } else {
            // Realiza o upload e cria o anexo no WordPress
            $attachment_id = media_handle_upload('foto_perfil', 0);

            if ( ! is_wp_error($attachment_id) ) {
                $dados['id_upload_foto'] = $attachment_id;
            } else {
                echo '<div class="notice notice-error"><p>Erro ao processar imagem: ' . $attachment_id->get_error_message() . '</p></div>';
            }
        }
    }

    // Atualiza o banco de dados
    $wpdb->update(
        $tabela_colaboradores,
        $dados,
        ['id_colaborador' => $colaborador->id_colaborador]
    );

    echo '<div class="notice notice-success is-dismissible"><p>✅ Perfil atualizado com sucesso!</p></div>';

    // Recarrega os dados do colaborador para refletir na tela
    $colaborador = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$tabela_colaboradores} WHERE id_colaborador = %d", $colaborador->id_colaborador)
    );
}

// Define a URL da foto ou placeholder
$foto_url = $colaborador->id_upload_foto
    ? wp_get_attachment_image_url($colaborador->id_upload_foto, 'medium')
    : 'https://via.placeholder.com/150?text=Sem+Foto';
?>

<style>
    :root {
        --ze-primary: #2271b1;
        --ze-bg: #f0f2f5;
        --ze-card-shadow: 0 10px 25px rgba(0,0,0,0.05);
        --ze-text: #334155;
    }

    .ze-perfil-wrap { max-width: 900px; margin: 20px auto; font-family: 'Segoe UI', system-ui, sans-serif; }
    
    .ze-btn-back { display: inline-flex; align-items: center; text-decoration: none; color: #64748b; font-weight: 600; margin-bottom: 20px; transition: color 0.2s; }
    .ze-btn-back:hover { color: var(--ze-primary); }
    .ze-btn-back span { margin-right: 5px; }

    .ze-card { background: #fff; border-radius: 16px; padding: 30px; margin-bottom: 24px; box-shadow: var(--ze-card-shadow); border: 1px solid rgba(0,0,0,0.03); }
    
    .ze-header-flex { display: flex; align-items: center; gap: 35px; flex-wrap: wrap; }
    
    .ze-foto-container { position: relative; text-align: center; flex-shrink: 0; }
    .ze-foto { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.3s ease; }
    .ze-foto:hover { transform: scale(1.02); }
    
    .ze-nome { font-size: 26px; font-weight: 800; color: #1d2327; margin-bottom: 8px; letter-spacing: -0.5px; }
    .ze-badge-info { display: inline-flex; flex-direction: column; gap: 4px; border-left: 3px solid #d1d5db; padding-left: 15px; }
    .ze-badge-info span { font-size: 14px; color: #64748b; }
    .ze-badge-info strong { color: #334155; }

    .ze-grid-premium { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .ze-field { display: flex; flex-direction: column; }
    .ze-field.full { grid-column: span 2; }
    .ze-field label { font-weight: 600; font-size: 14px; margin-bottom: 8px; color: #475569; }
    .ze-field input, .ze-field select { padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 15px; background: #fcfcfd; }
    .ze-field input:focus { border-color: var(--ze-primary); outline: none; box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1); background: #fff; }

    .ze-share-card { background: linear-gradient(135deg, #f8fbff 0%, #ebf4ff 100%); border: 1px solid #c2dbff; }
    .ze-toggle-label { display: inline-flex; align-items: center; cursor: pointer; font-weight: 700; color: #1e40af; background: #fff; padding: 12px 20px; border-radius: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .ze-toggle-label input { margin-right: 12px; transform: scale(1.3); }

    .ze-actions { text-align: right; margin-top: 30px; }
    .btn-save-premium { background: var(--ze-primary); color: white; border: none; padding: 15px 45px; border-radius: 8px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3); }
    .btn-save-premium:hover { background: #1a5a8e; transform: translateY(-2px); }

    @media (max-width: 600px) {
        .ze-grid-premium { grid-template-columns: 1fr; }
        .ze-field.full { grid-column: span 1; }
        .ze-header-flex { flex-direction: column; text-align: center; }
        .ze-badge-info { border-left: none; padding-left: 0; border-top: 1px solid #eee; padding-top: 15px; margin-top: 10px; }
    }
</style>

<div class="wrap ze-perfil-wrap">
    
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('salvar_meu_perfil'); ?>

        <div class="ze-card">
            <div class="ze-header-flex">
                <div class="ze-foto-container">
                    <img src="<?php echo esc_url($foto_url); ?>" class="ze-foto" id="preview-foto">
                    <div style="margin-top:15px; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <label for="foto_perfil" style="font-size: 11px; font-weight: 800; color: var(--ze-primary); text-transform: uppercase; cursor: pointer; background: #f0f6fb; padding: 5px 12px; border-radius: 20px;">
                            📷 Alterar Foto
                        </label>
                        <input type="file" name="foto_perfil" id="foto_perfil" accept="image/jpeg,image/png" style="display: none;">
                        <span id="file-name" style="font-size: 10px; color: #94a3b8;">Nenhum arquivo selecionado</span>
                    </div>
                </div>
                
                <div class="ze-dados-topo">
                    <div class="ze-nome"><?php echo esc_html($colaborador->nom_eleitor); ?></div>
                    <div class="ze-badge-info">
                        <span>CPF: <strong><?php echo esc_html($colaborador->num_cpf); ?></strong></span>
                        <span>Inscrição: <strong><?php echo esc_html($colaborador->num_inscricao); ?></strong></span>
                        <span>Telefone Fixo: <strong><?php echo esc_html($colaborador->num_telefone_eleitor); ?></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="ze-card">
            <h2 style="margin-bottom: 25px; font-size: 20px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">Dados de Contato</h2>

            <div class="ze-grid-premium">
                <div class="ze-field">
                    <label>WhatsApp / Celular de Contato</label>
                    <input 
                        type="text" 
                        name="telefone2" 
                        id="telefone2"
                        value="<?php echo esc_attr($colaborador->num_telefone_eleitor_2); ?>" 
                        placeholder="+55 (00) 00000-0000">
                </div>

                <div class="ze-field">
                    <label>Tamanho da Camiseta</label>
                    <select name="camiseta">
                        <option value="">Selecione...</option>
                        <?php foreach(['PP','P','M','G','GG','XG','XGG'] as $t): ?>
                            <option value="<?php echo $t; ?>" <?php selected($colaborador->ds_camiseta, $t); ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ze-field full">
                    <label>E-mail Pessoal</label>
                    <input type="email" name="email" value="<?php echo esc_attr($colaborador->email_colaborador); ?>" placeholder="seu-email@provedor.com">
                </div>

                <div class="ze-field full">
                    <label>Endereço Residencial Completo</label>
                    <input type="text" name="endereco" value="<?php echo esc_attr($colaborador->endereco_atualizado); ?>" placeholder="Rua, Número, Complemento, Bairro e Cidade">
                </div>
            </div>
        </div>

        <div class="ze-card ze-share-card">
            <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 20px;">
                <div style="font-size: 24px;">🤝</div>
                <div>
                    <h3 style="margin: 0 0 5px 0; color: #1e40af; font-size: 18px;">Rede de Colaboradores</h3>
                    <p style="margin: 0; color: #3b82f6; font-size: 14px; line-height: 1.5;">Ao ativar, seus colegas da mesma seção eleitoral poderão visualizar seu contato para facilitar a coordenação da equipe.</p>
                </div>
            </div>
            
            <label class="ze-toggle-label">
                <input type="checkbox" name="compartilhar" <?php checked($colaborador->compartilhar_contato, 1); ?>>
                Sim, desejo compartilhar meu contato com a equipe
            </label>
        </div>

        <div class="ze-actions">
            <button type="submit" class="btn-save-premium" name="salvar_perfil">
                💾 Gravar Alterações
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. Preview da Foto e Nome do Arquivo
    const inputFoto = document.getElementById('foto_perfil');
    const preview = document.getElementById('preview-foto');
    const fileName = document.getElementById('file-name');
    
    if(inputFoto) {
        inputFoto.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Atualiza nome do arquivo
                fileName.textContent = file.name;
                
                // Atualiza preview visual
                const reader = new FileReader();
                reader.onload = function(e) { 
                    preview.src = e.target.result; 
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // 2. Máscara de Telefone (WhatsApp)
    const inputTelefone = document.getElementById('telefone2');
    if (inputTelefone) {
        inputTelefone.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            
            // Remove 55 se o usuário digitar
            if (value.startsWith('55') && value.length > 10) value = value.substring(2);
            
            // Limita a 11 dígitos
            if (value.length > 11) value = value.substring(0, 11);
            
            let formatted = '+55 ';
            if (value.length > 0) formatted += '(' + value.substring(0, 2);
            if (value.length >= 3) formatted += ') ' + value.substring(2, 7);
            if (value.length >= 8) formatted += '-' + value.substring(7, 11);
            
            e.target.value = formatted;
        });

        // Dispara a máscara se já houver valor
        if (inputTelefone.value) {
            inputTelefone.dispatchEvent(new Event('input'));
        }
    }
});
</script>