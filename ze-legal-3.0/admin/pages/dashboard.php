<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Proteção de segurança
if ( ! current_user_can( 'ze_view_own' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

$current_user = wp_get_current_user();
?>

<div class="ze-premium-wrapper">
    <aside class="ze-sidebar">
        <div class="ze-sidebar-brand">
            <div class="ze-logo-icon">ZE</div>
            <div class="ze-logo-text">LEGAL <span>3.0</span></div>
        </div>

        <nav class="ze-nav">
            <div class="ze-nav-label">Principal</div>
            <a href="#" class="ze-nav-item active"><span class="dashicons dashicons-dashboard"></span> Dashboard</a>
            
            <?php if ( current_user_can('ze_cadastro_adm_cartorio') ) : ?>
                <div class="ze-nav-label">Operacional</div>
                <a href="<?php echo admin_url('admin.php?page=ze-legal-colaboradores'); ?>" class="ze-nav-item"><span class="dashicons dashicons-groups"></span> Colaboradores</a>
                <a href="<?php echo admin_url('admin.php?page=ze-legal-preencher-vagas-consulta'); ?>" class="ze-nav-item"><span class="dashicons dashicons-clipboard"></span> Vagas & Escalas</a>
                <a href="<?php echo admin_url('admin.php?page=ze-legal-convocacao-cartorio-consulta'); ?>" class="ze-nav-item"><span class="dashicons dashicons-email-alt"></span> Convocações</a>
            <?php endif; ?>

            <?php if ( current_user_can('ze_cadastro_adm_zona') ) : ?>
                <div class="ze-nav-label">Logística</div>
                <a href="<?php echo admin_url('admin.php?page=ze-legal-locais'); ?>" class="ze-nav-item"><span class="dashicons dashicons-location"></span> Locais de Votação</a>
                <a href="<?php echo admin_url('admin.php?page=ze-legal-veiculos'); ?>" class="ze-nav-item"><span class="dashicons dashicons-car"></span> Frotas/Veículos</a>
                <a href="<?php echo admin_url('admin.php?page=ze-legal-criar-eventos'); ?>" class="ze-nav-item"><span class="dashicons dashicons-calendar-alt"></span> Eventos</a>
            <?php endif; ?>

            <div class="ze-nav-label">Acesso Rápido</div>
            <a href="<?php echo admin_url('admin.php?page=ze-legal-meu-perfil'); ?>" class="ze-nav-item"><span class="dashicons dashicons-admin-users"></span> Meu Perfil</a>
            <a href="<?php echo wp_logout_url(); ?>" class="ze-nav-item logout"><span class="dashicons dashicons-exit"></span> Sair</a>
        </nav>
    </aside>

    <main class="ze-main-content">
        
        <header class="ze-topbar">
            <div class="ze-welcome">
                <h1>Bem-vindo, <span><?php echo esc_html($current_user->display_name); ?></span></h1>
                <p>Status do Sistema: <span class="ze-status-online">Ativo</span></p>
            </div>
            <div class="ze-user-badge">
                <span class="ze-role-tag"><?php echo implode(', ', $current_user->roles); ?></span>
                <?php echo get_avatar($current_user->ID, 40); ?>
            </div>
        </header>

        <section class="ze-grid-featured">
            <a href="<?php echo admin_url('admin.php?page=ze-legal-meu-perfil'); ?>" class="ze-action-card">
                <div class="ze-card-icon blue"><span class="dashicons dashicons-admin-users"></span></div>
                <div class="ze-card-info">
                    <h3>Meu Perfil</h3>
                    <p>Dados Pessoais e Contatos</p>
                </div>
            </a>

            <a href="<?php echo admin_url('admin.php?page=ze-legal-minha-convocacao'); ?>" class="ze-action-card">
                <div class="ze-card-icon green"><span class="dashicons dashicons-media-document"></span></div>
                <div class="ze-card-info">
                    <h3>Minha Convocação</h3>
                    <p>Ver e Baixar Documento</p>
                </div>
            </a>

            <a href="<?php echo admin_url('admin.php?page=ze-legal-meu-chekin'); ?>" class="ze-action-card">
                <div class="ze-card-icon gold"><span class="dashicons dashicons-marker"></span></div>
                <div class="ze-card-info">
                    <h3>Meu Check-in</h3>
                    <p>Confirmar Presença no Evento</p>
                </div>
            </a>

            <a href="<?php echo admin_url('admin.php?page=ze-legal-meu-local'); ?>" class="ze-action-card">
                <div class="ze-card-icon purple"><span class="dashicons dashicons-location-alt"></span></div>
                <div class="ze-card-info">
                    <h3>Meu Local</h3>
                    <p>Onde devo me apresentar?</p>
                </div>
            </a>
        </section>

        <?php if ( current_user_can('ze_cadastro_adm') ) : ?>
        <section class="ze-admin-section">
            <h2 class="ze-section-title">Gerenciamento Administrativo</h2>
            <div class="ze-grid-tools">
                <div class="ze-tool-item">
                    <span class="dashicons dashicons-database"></span>
                    <h4>Zonas e Municípios</h4>
                    <p>Estrutura territorial do pleito.</p>
                    <a href="<?php echo admin_url('admin.php?page=ze-legal-zonas'); ?>">Gerenciar</a>
                </div>
                <div class="ze-tool-item">
                    <span class="dashicons dashicons-networking"></span>
                    <h4>Pleitos</h4>
                    <h4 style="font-size: 11px; opacity: 0.7;">Configurações de Eleição</h4>
                    <a href="<?php echo admin_url('admin.php?page=ze-legal-pleitos'); ?>">Configurar</a>
                </div>
                <div class="ze-tool-item">
                    <span class="dashicons dashicons-cloud-upload"></span>
                    <h4>Importação</h4>
                    <p>Importar colaboradores via CSV.</p>
                    <a href="<?php echo admin_url('admin.php?page=ze-legal-importar-colaboradores'); ?>">Subir Arquivo</a>
                </div>
                <div class="ze-tool-item">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <h4>Enumes</h4>
                    <p>Dicionário de dados do sistema.</p>
                    <a href="<?php echo admin_url('admin.php?page=ze-legal-enumes'); ?>">Editar</a>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </main>
</div>

<style>
/* VARIÁVEIS DE COR PREMIUM */
:root {
    --primary: #4f46e5;
    --primary-light: #818cf8;
    --dark: #0f172a;
    --sidebar-bg: #1e293b;
    --text-main: #1e293b;
    --text-muted: #64748b;
    --white: #ffffff;
    --bg-body: #f8fafc;
    --blue: #3b82f6; --green: #10b981; --gold: #f59e0b; --purple: #8b5cf6;
}

.ze-premium-wrapper {
    display: flex;
    margin-left: -20px; /* Ajuste para compensar margem do WP */
    font-family: 'Inter', sans-serif;
    background: var(--bg-body);
    min-height: 100vh;
}

/* SIDEBAR */
.ze-sidebar {
    width: 280px;
    background: var(--sidebar-bg);
    color: #fff;
    padding: 20px 0;
    position: sticky;
    top: 32px;
    height: calc(100vh - 32px);
}

.ze-sidebar-brand {
    padding: 0 25px 30px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.ze-logo-icon {
    background: var(--primary);
    padding: 8px;
    border-radius: 8px;
    font-weight: bold;
}

.ze-logo-text {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -1px;
}

.ze-logo-text span { color: var(--primary-light); }

.ze-nav-label {
    padding: 20px 25px 10px;
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-muted);
    font-weight: bold;
}

.ze-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 25px;
    color: #cbd5e1;
    text-decoration: none;
    transition: 0.3s;
}

.ze-nav-item:hover, .ze-nav-item.active {
    background: rgba(255,255,255,0.1);
    color: #fff;
    border-left: 4px solid var(--primary);
}

.ze-nav-item.logout:hover { color: #f87171; }

/* CONTEÚDO */
.ze-main-content { flex: 1; padding: 40px; }

.ze-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.ze-welcome h1 { font-size: 24px; color: var(--dark); margin: 0; }
.ze-welcome h1 span { color: var(--primary); }
.ze-status-online { color: var(--green); font-weight: bold; font-size: 12px; }

.ze-user-badge { display: flex; align-items: center; gap: 15px; }
.ze-role-tag { background: #e2e8f0; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
.ze-user-badge img { border-radius: 50%; border: 2px solid var(--primary); }

/* GRID DE CARDS */
.ze-grid-featured {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 50px;
}

.ze-action-card {
    background: var(--white);
    padding: 25px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 20px;
    text-decoration: none;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    transition: 0.3s;
    border: 1px solid transparent;
}

.ze-action-card:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }

.ze-card-icon {
    width: 60px; height: 60px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
}

.ze-card-icon span { font-size: 30px; width: 30px; height: 30px; }

.blue { background: #eff6ff; color: var(--blue); }
.green { background: #ecfdf5; color: var(--green); }
.gold { background: #fffbeb; color: var(--gold); }
.purple { background: #f5f3ff; color: var(--purple); }

.ze-card-info h3 { margin: 0; font-size: 16px; color: var(--dark); }
.ze-card-info p { margin: 5px 0 0; font-size: 13px; color: var(--text-muted); }

/* ADMIN TOOLS */
.ze-section-title { font-size: 18px; margin-bottom: 20px; color: var(--dark); }
.ze-grid-tools {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.ze-tool-item {
    background: var(--white);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #e2e8f0;
}

.ze-tool-item span { font-size: 32px; color: var(--text-muted); margin-bottom: 15px; display: block; height: 32px; }
.ze-tool-item h4 { margin: 10px 0 5px; font-size: 14px; }
.ze-tool-item p { font-size: 12px; color: var(--text-muted); }
.ze-tool-item a { margin-top: 15px; display: inline-block; color: var(--primary); font-weight: bold; text-decoration: none; font-size: 13px; }

</style>