<?php
/*
Plugin Name: ZE-LEGAL 3.0
Description: Sistema ZE-LEGAL – Versão 3.0 (Layout Premium + Correção de Máscara)
Version: 3.0
Author: ZE-LEGAL
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * =====================================================
 * CONSTANTES DO SISTEMA
 * =====================================================
 */
define( 'ZE_LEGAL_VERSION', '3.0' );
define( 'ZE_LEGAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZE_LEGAL_URL', plugin_dir_url( __FILE__ ) );

// Composer autoload (vendor/)
if ( file_exists( ZE_LEGAL_PATH . 'vendor/vendor/autoload.php' ) ) {
 require_once ZE_LEGAL_PATH . 'vendor/vendor/autoload.php';
}

// Registro do Serviço de PDF
if ( file_exists( ZE_LEGAL_PATH . 'utils/gerador-convocacao-pdf.php' ) ) {
    require_once ZE_LEGAL_PATH . 'utils/gerador-convocacao-pdf.php';
}
// Registro do Serviço de PDF
if ( file_exists( ZE_LEGAL_PATH . 'utils/gerador-qr-evento-pdf.php' ) ) {
    require_once ZE_LEGAL_PATH . 'utils/gerador-qr-evento-pdf.php';
}

if ( file_exists( ZE_LEGAL_PATH . 'registrar-presenca.php' ) ) {
    require_once ZE_LEGAL_PATH . 'registrar-presenca.php';
    add_shortcode('ze_legal_registrar_presenca','ze_legal_render_registrar_presenca');
}

// Registro do Serviço de PDF
if ( file_exists( ZE_LEGAL_PATH . 'utils/gerar-lista-presenca.php' ) ) {
    require_once ZE_LEGAL_PATH . 'utils/gerar-lista-presenca.php';
}


// Registro do Serviço de PDF
add_action('admin_post_ze_gerar_rotas_pdf', 'ze_gerar_rotas_pdf_handler');

function ze_gerar_rotas_pdf_handler() {
    if (!current_user_can('ze_cadastro_adm_cartorio')) {
        wp_die('Acesso não autorizado.');
    }
    check_admin_referer('ze_gerar_rotas_pdf_nonce');
    require ZE_LEGAL_PATH . 'utils/gerador-rotas-pdf.php';
    exit;
}

add_action('admin_post_ze_identificacao_secao_pdf_handler', 'ze_identificacao_secao_pdf_handler');

function ze_identificacao_secao_pdf_handler() {
    if (!current_user_can('ze_cadastro_adm_cartorio')) {
        wp_die('Acesso não autorizado.');
    }
    check_admin_referer('ze_identificacao_secao_pdf_nonce');
    require ZE_LEGAL_PATH . 'utils/identificacao-secao-pdf.php';
    exit;
}
/**
 * =====================================================
 * HANDLERS (admin_post / wp_ajax)
 * =====================================================
 */
if ( file_exists( ZE_LEGAL_PATH . 'handlers/convocacao-handler.php' ) ) {
    require_once ZE_LEGAL_PATH . 'handlers/convocacao-handler.php';
}

if ( file_exists( ZE_LEGAL_PATH . 'handlers/registro-presenca-evento.php' ) ) {
    require_once ZE_LEGAL_PATH . 'handlers/registro-presenca-evento.php';
}



/**
 * =====================================================
 * DOMÍNIO – REGRAS PURAS
 * =====================================================
 */
require_once ZE_LEGAL_PATH . 'domain/cpf-valida.php';
require_once ZE_LEGAL_PATH . 'domain/cpf-mascara.php';
require_once ZE_LEGAL_PATH . 'domain/cpf-mascara-limpa.php';
require_once ZE_LEGAL_PATH . 'domain/telefone-mascara.php';

/**
 * =====================================================
 * ATIVAÇÃO
 * =====================================================
 */
register_activation_hook(__FILE__, 'ze_legal_activate');

function ze_legal_activate() {

    if (file_exists(ZE_LEGAL_PATH . 'install/install.php')) {
        require_once ZE_LEGAL_PATH . 'install/install.php';
    }

    if (file_exists(ZE_LEGAL_PATH . 'install/install-enums.php')) {
        require_once ZE_LEGAL_PATH . 'install/install-enums.php';

        if (function_exists('ze_install_enums_iniciais')) {
            ze_install_enums_iniciais();
        }
    }

    // 1. Apenas inclua o arquivo (não execute a função solta)
    if (file_exists(ZE_LEGAL_PATH . 'utils/roles-capabilities.php')) {
        require_once ZE_LEGAL_PATH . 'utils/roles-capabilities.php';
    }
    
    // 2. Opção A: Executar apenas na ativação do plugin (Recomendado para Produção)
    register_activation_hook(__FILE__, 'ze_legal_register_roles_and_capabilities');
    
    // 3. Opção B: Executar no 'init' (Útil para Desenvolvimento/Testes)
    // Isso garante que o WP já carregou todas as funções de usuários antes de rodar a sua
    add_action('init', function() {
        if (function_exists('ze_legal_register_roles_and_capabilities')) {
            ze_legal_register_roles_and_capabilities();
        }
    });
}


/**
 * 2. CARREGAMENTO DOS ESTILOS PREMIUM (CSS)
 */
 
 add_action('admin_enqueue_scripts', function() {
    // Ajuste o caminho 'css/admin-style.css' para onde seu arquivo realmente está
    wp_enqueue_style('ze-premium-style', plugins_url('admin/assets/css/admin-style.css', __FILE__));
});
 
 

/**
 * =====================================================
 * REGISTRO – CPF COMO LOGIN
 * =====================================================
 */
add_filter( 'registration_errors', 'ze_legal_validate_registration_cpf', 10, 3 );
function ze_legal_validate_registration_cpf( $errors, $user_login, $user_email ) {

    if ( empty( $user_login ) ) {
        $errors->add( 'cpf_empty', 'Informe seu CPF.' );
        return $errors;
    }

    $cpf_puro = cpf_limpa( $user_login );

    if ( ! cpf_valida( $cpf_puro ) ) {
        $errors->add( 'cpf_invalid', 'CPF inválido.' );
        return $errors;
    }

    if ( username_exists( $cpf_puro ) ) {
        $errors->add( 'cpf_exists', 'CPF já cadastrado.' );
        return $errors;
    }

    return $errors;
}

/**
 * =====================================================
 * AUTENTICAÇÃO – LOGIN VIA CPF
 * =====================================================
 */
add_filter( 'login_display_language_dropdown', '__return_false' );

add_action( 'login_form_top', function() {
    echo '<p style="text-align:center;margin-bottom:20px;color:#4b5563;font-weight:500;">Bem-vindo ao ZE-LEGAL!</p>';
});

add_filter( 'authenticate', 'ze_legal_authenticate_by_cpf', 20, 3 );
function ze_legal_authenticate_by_cpf( $user, $username, $password ) {

    if ( is_a( $user, 'WP_User' ) ) return $user;
    if ( empty( $username ) ) return $user;

    if ( is_email( $username ) ) {
        return new WP_Error( 'email_login_blocked', 'Utilize seu CPF para acessar.' );
    }

    $username_limpo = cpf_limpa( $username );

    if ( ! cpf_valida( $username_limpo ) ) {
        return new WP_Error( 'cpf_login_invalid', 'CPF inválido.' );
    }

    return wp_authenticate_username_password( null, $username_limpo, $password );
}

/**
 * =====================================================
 * LOGIN – VISUAL, LOGO E SCRIPTS
 * =====================================================
 */
add_action( 'login_enqueue_scripts', 'ze_legal_login_assets' );
function ze_legal_login_assets() {

    wp_enqueue_style(
        'ze-legal-login',
        ZE_LEGAL_URL . 'admin/assets/css/login.css',
        array(),
        ZE_LEGAL_VERSION
    );

    $logo_url = ZE_LEGAL_URL . 'admin/assets/images/ui/logo-login.png';

    wp_add_inline_style(
        'ze-legal-login',
        ".login h1 a{
            background-image:url('$logo_url')!important;
            background-size:contain!important;
            width:100%!important;
            height:100px!important;
            display:block!important;
        }"
    );

    wp_enqueue_script(
        'jquery-mask',
        'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js',
        array( 'jquery' ),
        null,
        true
    );

    wp_add_inline_script( 'jquery-mask', "
        jQuery(document).ready(function($){
            var cpf = $('#user_login');
            if (cpf.length) {
                cpf.mask('000.000.000-00');
            }
            $('form').on('submit', function(){
                if (cpf.length) {
                    cpf.unmask();
                }
            });
        });
    " );
}

add_filter( 'login_headerurl', fn() => home_url() );
add_filter( 'login_headertext', fn() => '' );

/**
 * =====================================================
 * PERSONALIZAÇÃO DO E-MAIL DE REDEFINIÇÃO DE SENHA
 * =====================================================
 */

// Assunto do e-mail
add_filter( 'retrieve_password_title', function() {
    return 'ZE-LEGAL | Redefinição de Senha';
});

// Corpo do e-mail
add_filter( 'retrieve_password_message', function( $message, $key, $user_login, $user_data ) {

    $cpf_formatado = cpf_mascara( $user_login );
    $reset_link = network_site_url(
        "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ),
        'login'
    );

    $mensagem = "Recebemos uma solicitação para redefinir a senha de acesso ao sistema ZE-LEGAL.\n\n";
    $mensagem .= "CPF cadastrado:\n$cpf_formatado\n\n";
    $mensagem .= "Se você reconhece esta solicitação, clique no link abaixo para criar uma nova senha:\n\n";
    $mensagem .= "$reset_link\n\n";
    $mensagem .= "Caso você não tenha solicitado a redefinição, ignore este e-mail com segurança.\n\n";
    $mensagem .= "Atenciosamente,\n";
    $mensagem .= "Equipe ZE-LEGAL";

    return $mensagem;

}, 10, 4 );

/**
 * =====================================================
 * TEXTOS – LOGIN / REGISTRO / ERROS / SENHA
 * =====================================================
 */
add_filter( 'gettext', 'ze_legal_custom_login_texts', 20, 3 );
function ze_legal_custom_login_texts( $translated_text, $text, $domain ) {

    if ( $domain !== 'default' ) return $translated_text;

    if (
        $text === 'Username or Email Address' ||
        $text === 'Nome de utilizador ou endereço de email' ||
        $text === 'Username' ||
        $text === 'Nome de utilizador'
    ) {
        return 'Insira seu CPF';
    }

    if (
        $text === 'Email' ||
        $text === 'E-mail'
    ) {
        return 'E-mail (usado para recuperação de senha)';
    }

    if ( $text === 'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.' ) {
        return 'Por favor insira seu CPF, e receberá um email com instruções sobre como redefinir a sua senha.';
    }

    if (
        $text === 'Register' ||
        $text === 'Register For This Site' ||
        $text === 'Criar Registo Neste Site'
    ) {
        return 'Quero me inscrever no ZE Legal';
    }

    return $translated_text;
}
add_filter( 'wp_new_user_notification_email', function( $wp_new_user_notification_email, $user, $blogname ) {

    $reset_link = wp_lostpassword_url();

    $wp_new_user_notification_email['subject'] =
        'ZE-LEGAL | Acesso ao Sistema';

    $wp_new_user_notification_email['message'] =
        "Seu acesso ao sistema ZE-LEGAL foi criado.\n\n" .
        "Para definir sua senha e concluir seu cadastro, utilize o link abaixo:\n\n" .
        "$reset_link\n\n" .
        "Este link é pessoal e temporário.\n\n" .
        "Se você não solicitou este acesso, ignore este e-mail.\n\n" .
        "Atenciosamente,\n" .
        "Equipe ZE-LEGAL";

    return $wp_new_user_notification_email;

}, 10, 3 );
/**
 * =====================================================
 * ADMIN
 * =====================================================
 */
if ( is_admin() && file_exists( ZE_LEGAL_PATH . 'admin/menu.php' ) ) {
    require_once ZE_LEGAL_PATH . 'admin/menu.php';
}


