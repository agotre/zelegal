<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function ze_legal_admin_menu() {

    if ( ! current_user_can( 'ze_view_own' ) ) {
        return;
    }

    /**
     * MENU PRINCIPAL – DASHBOARD
     */
    add_menu_page(
        'ZE-LEGAL',
        'ZE-LEGAL',
        'ze_view_own',
        'ze-legal-dashboard',
        'ze_legal_dashboard_page',
        'dashicons-legal',
        2
    );

    /**
     * SUBMENU – CAD ENUMES
     * Apenas administradores (ze_cadastro_adm)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Enumes',
        'Cad Enumes',
        'ze_cadastro_adm',
        'ze-legal-enumes',
        'ze_legal_enumes_page'
    );
    
    /**
     * SUBMENU – CAD PLEITOS
     * Apenas administradores (ze_cadastro_adm)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Pleitos',
        'Cad Pleitos',
        'ze_cadastro_adm',
        'ze-legal-pleitos',
        'ze_legal_pleitos_page'
    );
    
    /**
     * SUBMENU – CAD ZONAS
     * Apenas administradores (ze_cadastro_adm)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Zonas',
        'Cad Zonas',
        'ze_cadastro_adm',
        'ze-legal-zonas',
        'ze_legal_zonas_page'
    );
    
    /**
     * SUBMENU – CAD MUNICIPIOS
     * Apenas administradores (ze_cadastro_adm)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Municipios',
        'Cad Municipios',
        'ze_cadastro_adm',
        'ze-legal-municipios',
        'ze_legal_municipios_page'
    );
    
    /**
     * SUBMENU – CAD LOCAIS
     * Apenas administradores zona (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Locais',
        'Cad Locais',
        'ze_cadastro_adm_zona',
        'ze-legal-locais',
        'ze_legal_locais_page'
    );
    
    /**
     * SUBMENU – CAD TIPOS LOCAIS
     * Apenas administradores zona (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Tipos Locais',
        'Cad Tipos Locais',
        'ze_cadastro_adm_zona',
        'ze-legal-tipos-locais',
        'ze_legal_tipos_locais_page'
    );
    
    /**
     * SUBMENU – CAD SECOES
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Secoes',
        'Cad Secoes',
        'ze_cadastro_adm_zona',
        'ze-legal-secoes',
        'ze_legal_secoes_page'
    );
    
    /**
     * SUBMENU – CAD VEICULOS
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Veiculos',
        'Cad Veiculos',
        'ze_cadastro_adm_cartorio',
        'ze-legal-veiculos',
        'ze_legal_veiculos_page'
    );
    
    /**
     * SUBMENU – CAD FUNCOES
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Cad Funções',
        'Cad Funções',
        'ze_cadastro_adm_zona',
        'ze-legal-funcoes',
        'ze_legal_funcoes_page'
    );
    
    /**
     * SUBMENU – CAD ENUMES
     * Apenas administradores (ze_cadastro_adm)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Colaboradores Importar',
        'Colaboradores Importar',
        'ze_cadastro_adm',
        'ze-legal-importar-colaboradores',
        'ze_legal_importar_colaboradores_page'
    );
    
     /**
     * SUBMENU – COLABORADORES
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Colaboradores',
        'Colaboradores',
        'ze_cadastro_adm_cartorio',
        'ze-legal-colaboradores',
        'ze_legal_colaboradores_page'
    );
    
     /**
     * SUBMENU – COLABORADORES RESTAURAR
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Colaboradores Restaurar',
        'Colaboradores Restaurar',
        'ze_cadastro_adm_zona',
        'ze-legal-colaboradores-restaurar',
        'ze_legal_colaboradores_restaurar_page'
    );
    
     /**
     * SUBMENU – CRIAR EVENTOS
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Criar Eventos',
        'Criar Eventos',
        'ze_cadastro_adm_zona',
        'ze-legal-criar-eventos',
        'ze_legal_criar_eventos_page'
    );
    
     /**
     * SUBMENU – CRIAR VAGAS
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Criar Vagas',
        'Criar Vagas',
        'ze_cadastro_adm_zona',
        'ze-legal-criar-vagas',
        'ze_legal_criar_vagas_page'
    );
    
     /**
     * SUBMENU – AGREGAR VAGAS
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Agregar Vagas Seções',
        'Agregar Vagas Seções',
        'ze_cadastro_adm_zona',
        'ze-legal-agregar-vagas-secoes',
        'ze_legal_agregar_vagas_secoes_page'
    );
    
    /**
     * SUBMENU – CRIAR ROTAS
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Criar Rotas',
        'Criar Rotas',
        'ze_cadastro_adm_zona',
        'ze-legal-criar-rotas',
        'ze_legal_criar_rotas_page'
    );
    
    /**
     * SUBMENU – MAPA ROTAS
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Mapa Rotas',
        'Mapa Rotas',
        'ze_cadastro_adm_cartorio',
        'ze-legal-mapa-rotas',
        'ze_legal_mapa_rotas_page'
    );
    
     /**
     * SUBMENU – PREENCHER VAGAS
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Preencher vagas',
        'Preencher vagas',
        'ze_cadastro_adm_cartorio',
        'ze-legal-preencher-vagas-consulta',
        'ze_legal_preencher_vagas_consulta_page'
    );
    
     /**
     * SUBMENU – GERENCIAR CONVOCACAO
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Gestão Convocacao',
        'Gestão Convocacao',
        'ze_cadastro_adm_cartorio',
        'ze-legal-convocacao-cartorio-consulta',
        'ze_legal_convocacao_cartorio_consulta_page'
    );
    
      /**
     * SUBMENU – GESTAO EVENTOS
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Gestão Eventos',
        'Gestão Eventos',
        'ze_cadastro_adm_cartorio',
        'ze-legal-gestao-eventos',
        'ze_legal_gestao_eventos_page'
    );
    
     /**
     * SUBMENU – GESTAO EVENTOS QR
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Gestão Eventos QR',
        'Gestão Eventos QR',
        'ze_cadastro_adm_cartorio',
        'ze-legal-gestao-eventos-qr',
        'ze_legal_gestao_eventos_qr_page'
    );
    
    
     /**
     * SUBMENU – GESTAO EVENTOS QR
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Registro Presença',
        'Registro Presença',
        'ze_cadastro_adm_cartorio',
        'ze-legal-registro-presenca-manual',
        'ze_legal_registro_presenca_manual'
    );
    
     /**
     * SUBMENU – RELATORIOS
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Relatorios',
        'Relatorios',
        'ze_cadastro_adm_cartorio',
        'ze-legal-relatorios',
        'ze_legal_relatorios'
    );
    
     /**
     * SUBMENU – MEU PERFIL
     * Apenas administradores (subscriber)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Meu Perfil',
        'Meu Perfil',
        'ze_profile_edit',
        'ze-legal-meu-perfil',
        'ze_legal_meu_perfil_page'
    );
    
    
     /**
     * SUBMENU – MEU CHEKIN
     * Apenas administradores (subscriber)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Meu Chekin',
        'Meu Chekin',
        'ze_profile_edit',
        'ze-legal-meu-chekin',
        'ze_legal_meu_chekin_page'
    );
    
     /**
     * SUBMENU – MINHA CONVOCACAO
     * Apenas administradores (subscriber)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        'Minha Convocação',
        'Minha Convocação',
        'ze_profile_edit',
        'ze-legal-minha-convocacao',
        'ze_legal_minha_convocacao_page'
    );
    
     /**
     * SUBMENU – GERENCIAR CONVOCACAO
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        '',
        '',
        'ze_cadastro_adm_cartorio',
        'ze-legal-convocacao-cartorio-gerenciar',
        'ze_legal_convocacao_cartorio_gerenciar_page'
    );
    

     /**
     * SUBMENU – PREENCHER VAGAS - local
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        '',
        '',
        'ze_cadastro_adm_cartorio',
        'ze-legal-preencher-vagas-local',
        'ze_legal_preencher_vagas_local_page'
    );
    
     /**
     * SUBMENU – PREENCHER VAGAS - Selecionar
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        '',
        '',
        'ze_cadastro_adm_cartorio',
        'ze-legal-preencher-vagas-selecionar',
        'ze_legal_preencher_vagas_selecionar_page'
    );
    
     /**
     * SUBMENU – CRIAR VAGAS - LOCAL
     * Apenas administradores (ze_cadastro_adm_zona)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        '',
        '',
        'ze_cadastro_adm_zona',
        'ze-legal-criar-vagas-local',
        'ze_legal_criar_vagas_local_page'
    );
    
     /**
     * SUBMENU – COLABORADORES
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        '',
        '',
        'ze_cadastro_adm_cartorio',
        'ze-legal-colaboradores-editar',
        'ze_legal_colaboradores_editar_page'
    );
    
     /**
     * SUBMENU – COLABORADORES
     * Apenas administradores (ze_cadastro_adm_cartorio)
     */
    add_submenu_page(
        'ze-legal-dashboard',
        '',
        '',
        'ze_cadastro_adm_cartorio',
        'ze-legal-colaboradores-incluir',
        'ze_legal_colaboradores_incluir_page'
    );
    
}
add_action( 'admin_menu', 'ze_legal_admin_menu' );

/**
 * =====================================================
 * CALLBACK DO DASHBOARD
 * =====================================================
 */
function ze_legal_dashboard_page() {

    $dashboard_file = ZE_LEGAL_PATH . 'admin/pages/dashboard.php';

    if ( file_exists( $dashboard_file ) ) {
        require $dashboard_file;
    } else {
        wp_die( 'Arquivo do dashboard não encontrado.' );
    }
}

/**
 * =====================================================
 * CALLBACK DA PÁGINA DE ENUMES
 * =====================================================
 */
function ze_legal_enumes_page() {

    if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $enumes_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/enumes.php';

    if ( file_exists( $enumes_file ) ) {
        require $enumes_file;
    } else {
        wp_die( 'Arquivo da página de cadastro de enumes não encontrado.' );
    }
}

function ze_legal_pleitos_page() {

    if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $pleitos_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/pleitos.php';

    if ( file_exists( $pleitos_file ) ) {
        require $pleitos_file;
    } else {
        wp_die( 'Arquivo da página de cadastro de pleitos não encontrado.' );
    }
}

function ze_legal_zonas_page() {

    if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $zonas_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/zonas.php';

    if ( file_exists( $zonas_file ) ) {
        require $zonas_file;
    } else {
        wp_die( 'Arquivo da página de cadastro de zonas não encontrado.' );
    }
}

function ze_legal_municipios_page() {

    if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $municipios_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/municipios.php';

    if ( file_exists( $municipios_file ) ) {
        require $municipios_file;
    } else {
        wp_die( 'Arquivo da página de cadastro de municipios não encontrado.' );
    }
}

function ze_legal_importar_colaboradores_page() {

    if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $importar_file = ZE_LEGAL_PATH . 'admin/pages/importar/importar-colaboradores.php';

    if ( file_exists( $importar_file ) ) {
        require $importar_file;
    } else {
        wp_die( 'Arquivo da página de importar colaboradores não encontrado.' );
    }
}

function ze_legal_locais_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $locais_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/locais.php';

    if ( file_exists( $locais_file ) ) {
        require $locais_file;
    } else {
        wp_die( 'Arquivo da página de cadastrar locais não encontrado.' );
    }
}

function ze_legal_tipos_locais_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $locaistipos_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/tipos-locais.php';

    if ( file_exists( $locaistipos_file ) ) {
        require $locaistipos_file;
    } else {
        wp_die( 'Arquivo da página de cadastrar tipo de locais não encontrado.' );
    }
}

function ze_legal_secoes_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $secoes_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/secoes.php';

    if ( file_exists( $secoes_file ) ) {
        require $secoes_file;
    } else {
        wp_die( 'Arquivo da página de cadastro de secoes não encontrado.' );
    }
}

function ze_legal_veiculos_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $veiculos_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/veiculos.php';

    if ( file_exists( $veiculos_file ) ) {
        require $veiculos_file;
    } else {
        wp_die( 'Arquivo da página de cadastro de veiculos não encontrado.' );
    }
}

function ze_legal_funcoes_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $funcoes_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/funcoes.php';

    if ( file_exists( $funcoes_file ) ) {
        require $funcoes_file;
    } else {
        wp_die( 'Arquivo da página de cadastro de funções eleitorais não encontrado.' );
    }
}

function ze_legal_colaboradores_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $colab_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/colaboradores.php';

    if ( file_exists( $colab_file ) ) {
        require $colab_file;
    } else {
        wp_die( 'Arquivo da página de Colaboradores não encontrado.' );
    }
}

function ze_legal_colaboradores_restaurar_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $colabrestaurar_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/colaboradores-restaurar.php';

    if ( file_exists( $colabrestaurar_file ) ) {
        require $colabrestaurar_file;
    } else {
        wp_die( 'Arquivo da página de Colaboradores Restaurar não encontrado.' );
    }
}

function ze_legal_colaboradores_editar_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $colabeditar_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/colaboradores-editar.php';

    if ( file_exists( $colabeditar_file ) ) {
        require $colabeditar_file;
    } else {
        wp_die( 'Arquivo da página de Colaboradores Editar não encontrado.' );
    }
}

function ze_legal_colaboradores_incluir_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $colabincluir_file = ZE_LEGAL_PATH . 'admin/pages/cadastros/colaboradores-incluir.php';

    if ( file_exists( $colabincluir_file ) ) {
        require $colabincluir_file;
    } else {
        wp_die( 'Arquivo da página de Colaboradores incluir não encontrado.' );
    }
}

function ze_legal_criar_vagas_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $criarvagas_file = ZE_LEGAL_PATH . 'admin/pages/vagas/criar-vagas.php';

    if ( file_exists( $criarvagas_file ) ) {
        require $criarvagas_file;
    } else {
        wp_die( 'Arquivo da página de Criara Vagas não encontrado.' );
    }
}

function ze_legal_agregar_vagas_secoes_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $criarvagassecoes_file = ZE_LEGAL_PATH . 'admin/pages/vagas/agregar-vagas-secoes.php';

    if ( file_exists( $criarvagassecoes_file ) ) {
        require $criarvagassecoes_file;
    } else {
        wp_die( 'Arquivo da página de Agregar Vagas Seções não encontrado.' );
    }
}

function ze_legal_criar_vagas_local_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $criarvagaslocal_file = ZE_LEGAL_PATH . 'admin/pages/vagas/criar-vagas-local.php';

    if ( file_exists( $criarvagaslocal_file ) ) {
        require $criarvagaslocal_file;
    } else {
        wp_die( 'Arquivo da página de Criar Vagas Local não encontrado.' );
    }
}

function ze_legal_preencher_vagas_consulta_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $preevagas_file = ZE_LEGAL_PATH . 'admin/pages/vagas/preencher-vagas-consulta.php';

    if ( file_exists( $preevagas_file ) ) {
        require $preevagas_file;
    } else {
        wp_die( 'Arquivo da página de Preencher Vagas não encontrado.' );
    }
}

function ze_legal_preencher_vagas_local_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $preevagaslocal_file = ZE_LEGAL_PATH . 'admin/pages/vagas/preencher-vagas-local.php';

    if ( file_exists( $preevagaslocal_file ) ) {
        require $preevagaslocal_file;
    } else {
        wp_die( 'Arquivo da página de Preencher Vagas Local não encontrado.' );
    }
}

function ze_legal_preencher_vagas_selecionar_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $preevagassele_file = ZE_LEGAL_PATH . 'admin/pages/vagas/preencher-vagas-selecionar.php';

    if ( file_exists( $preevagassele_file ) ) {
        require $preevagassele_file;
    } else {
        wp_die( 'Arquivo da página de Preencher Vagas Selecionar não encontrado.' );
    }
}

function ze_legal_convocacao_cartorio_consulta_page() {

    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $convocacao_file = ZE_LEGAL_PATH . 'admin/pages/convocacao/convocacao-cartorio-consulta.php';

    if ( file_exists( $convocacao_file ) ) {
        require $convocacao_file;
    } else {
        wp_die( 'Arquivo da página de Convocacao Consulta não encontrado.' );
    }
}

function ze_legal_convocacao_cartorio_gerenciar_page() {
    
    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $convocacaogere_file = ZE_LEGAL_PATH . 'admin/pages/convocacao/convocacao-cartorio-gerenciar.php';

    if ( file_exists( $convocacaogere_file ) ) {
        require $convocacaogere_file;
    } else {
        wp_die( 'Arquivo da página de Convocacao Gerenciar não encontrado.' );
    }
}

function ze_legal_criar_eventos_page() {
    
    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $criareventos_file = ZE_LEGAL_PATH . 'admin/pages/eventos/criar-eventos.php';

    if ( file_exists( $criareventos_file ) ) {
        require $criareventos_file;
    } else {
        wp_die( 'Arquivo da página de Criar Eventos não encontrado.' );
    }
}

function ze_legal_meu_perfil_page() {
    
    if ( ! current_user_can( 'ze_profile_edit' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $meuperfil_file = ZE_LEGAL_PATH . 'admin/pages/usuarios/meu-perfil.php';

    if ( file_exists( $meuperfil_file) ) {
        require $meuperfil_file;
    } else {
        wp_die( 'Arquivo da página do Meu Perfil não encontrado.' );
    }
}

function ze_legal_meu_chekin_page() {
    
    if ( ! current_user_can( 'ze_profile_edit' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $meuchekin_file = ZE_LEGAL_PATH . 'admin/pages/usuarios/meu-chekin.php';

    if ( file_exists( $meuchekin_file) ) {
        require $meuchekin_file;
    } else {
        wp_die( 'Arquivo da página do Meu Chekin não encontrado.' );
    }
}

function ze_legal_minha_convocacao_page() {
    
    if ( ! current_user_can( 'ze_profile_edit' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $minhaconvocacao_file = ZE_LEGAL_PATH . 'admin/pages/usuarios/minha-convocacao.php';

    if ( file_exists( $minhaconvocacao_file) ) {
        require $minhaconvocacao_file;
    } else {
        wp_die( 'Arquivo da página do Minha Convocacao não encontrado.' );
    }
}

function ze_legal_criar_rotas_page() {
    
    if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $criarrotas_file = ZE_LEGAL_PATH . 'admin/pages/rotas/criar-rotas.php';

    if ( file_exists( $criarrotas_file) ) {
        require $criarrotas_file;
    } else {
        wp_die( 'Arquivo da página do Criar Rotas não encontrado.' );
    }
}

function ze_legal_mapa_rotas_page() {
    
    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $maparotas_file = ZE_LEGAL_PATH . 'admin/pages/rotas/mapa-rotas.php';

    if ( file_exists( $maparotas_file) ) {
        require $maparotas_file;
    } else {
        wp_die( 'Arquivo da página do Mapa Rotas não encontrado.' );
    }
}

function ze_legal_gestao_eventos_page() {
    
    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $gestaoeventos_file = ZE_LEGAL_PATH . 'admin/pages/eventos/gestao-eventos.php';

    if ( file_exists( $gestaoeventos_file ) ) {
        require $gestaoeventos_file;
    } else {
        wp_die( 'Arquivo da página de Gestão de Eventos não encontrado.' );
    }
}

function ze_legal_gestao_eventos_qr_page() {
    
    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $gestaoeventosqr_file = ZE_LEGAL_PATH . 'admin/pages/eventos/gestao-eventos-qr.php';

    if ( file_exists( $gestaoeventosqr_file ) ) {
        require $gestaoeventosqr_file;
    } else {
        wp_die( 'Arquivo da página de Gestão de Eventos QR não encontrado.' );
    }
}

function ze_legal_registro_presenca_manual() {
    
    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $registrarpresenca_file = ZE_LEGAL_PATH . 'admin/pages/eventos/registro-presenca-manual.php';

    if ( file_exists( $registrarpresenca_file ) ) {
        require $registrarpresenca_file;
    } else {
        wp_die( 'Arquivo da página de Registro de Presença não encontrado.' );
    }
}

function ze_legal_relatorios() {
    
    if ( ! current_user_can( 'ze_cadastro_adm_cartorio' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }

    $relatorios_file = ZE_LEGAL_PATH . 'admin/pages/relatorios/relatorios.php';

    if ( file_exists( $relatorios_file ) ) {
        require $relatorios_file;
    } else {
        wp_die( 'Arquivo da página de Relatorio não encontrado.' );
    }
}
