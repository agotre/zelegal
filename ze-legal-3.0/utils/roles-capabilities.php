<?php
if ( ! defined( 'ABSPATH' ) ) exit;




function ze_legal_register_roles_and_capabilities() {
    if ( ! function_exists( 'add_role' ) ) return;

    // 1. ADICIONAR CAPS AO ADMINISTRADOR
    $capabilities = array(
        'ze_cadastro_adm', 'ze_cadastro_adm_zona', 'ze_cadastro_adm_cartorio',
        'ze_gestao', 'ze_gestao_checklist', 'ze_gestao_checkin', 'ze_gestao_rotas',
        'ze_gestao_tarefas', 'ze_gestao_eventos', 'ze_view_all', 'ze_view_local',
        'ze_view_own', 'ze_profile_view', 'ze_profile_edit', 'ze_convocacao_view', 'ze_checkin_publico',
        'ze_convocacao_accept', 'read' // Admin sempre tem read, mas incluímos por segurança
    );

    $admin = get_role( 'administrator' );
    if ( $admin ) {
        foreach ( $capabilities as $cap ) {
            $admin->add_cap( $cap );
        }
    }

    // 2. CRIAR / ATUALIZAR ROLES CUSTOMIZADOS
    // Nota: Usamos add_role. Se o role já existe, ele não sobrescreve. 
    // Para atualizar, você precisaria de remove_role primeiro, ou usar o get_role()->add_cap.

    // ROLE: adm_zona
    if ( ! get_role( 'adm_zona' ) ) {
        add_role( 'adm_zona', 'Administrador de Zona', array(
            'read' => true, // OBRIGATÓRIO
            'ze_cadastro_adm_zona' => true,
            'ze_gestao' => true,
            'ze_gestao_checklist' => true,
            'ze_view_local' => true,
            'ze_profile_edit' => true,
            'ze_convocacao_view' => true,
        ));
    }

    // ROLE: adm_cartorio
    if ( ! get_role( 'adm_cartorio' ) ) {
        add_role( 'adm_cartorio', 'Administrador de Cartório', array(
            'read' => true, // OBRIGATÓRIO
            'ze_cadastro_adm_cartorio' => true,
            'ze_gestao' => true,
            'ze_view_local' => true,
            'ze_profile_edit' => true,
            'ze_convocacao_view' => true,
        ));
    }

    // ROLE: gestor
    if ( ! get_role( 'gestor' ) ) {
        add_role( 'gestor', 'Gestor', array(
            'read' => true, // OBRIGATÓRIO
            'ze_view_all' => true,
            'ze_profile_edit' => true,
        ));
    }

    // ROLE: monitor
    if ( ! get_role( 'monitor' ) ) {
        add_role( 'monitor', 'Monitor', array(
            'read' => true, // OBRIGATÓRIO
            'ze_gestao_checkin' => true,
            'ze_view_local' => true,
            'ze_convocacao_view' => true,
        ));
    }

    // 3. ATUALIZAR SUBSCRIBER
    $subscriber = get_role( 'subscriber' );
    if ( $subscriber ) {
        $subscriber->add_cap( 'read' ); // Garante que ele possa ver o perfil
        $subscriber->add_cap( 'ze_profile_view' );
        $subscriber->add_cap( 'ze_profile_edit' );
        $subscriber->add_cap( 'ze_convocacao_view' );
        $subscriber->add_cap( 'ze_convocacao_accept' );
        $subscriber->add_cap( 'ze_view_own' );
    }
    
    // 4. ATUALIZAR ROLE NATIVO: COLABORADOR (contributor)
    $contributor = get_role( 'contributor' );
    if ( $contributor ) {
        $contributor->add_cap( 'read' ); // obrigatório
        $contributor->add_cap( 'ze_cadastro_adm_cartorio' );
        $contributor->add_cap( 'ze_gestao' );
        $contributor->add_cap( 'ze_view_local' );
        $contributor->add_cap( 'ze_profile_edit' );
        $contributor->add_cap( 'ze_convocacao_view' );
    }
}

/**
 * ESTA É A PARTE QUE VOCÊ DEVE ADICIONAR:
 * Função para limpar os cargos antigos e forçar o registro dos novos
 */
function ze_legal_force_reset_roles() {
    $roles_para_limpar = array('adm_zona', 'adm_cartorio', 'gestor', 'monitor');
    
    foreach ( $roles_para_limpar as $role_id ) {
        remove_role( $role_id );
    }
    
    // Agora chama a função de registro para gravar do zero no banco
    ze_legal_register_roles_and_capabilities();
}

// Hook temporário: Isso vai rodar assim que você carregar o painel do WP
add_action( 'admin_init', 'ze_legal_force_reset_roles' );