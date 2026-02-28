<?php
if ( ! defined('ABSPATH') ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_profile_edit' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$prefix = $wpdb->prefix . 'ze_';
$user_id = get_current_user_id();

// 1. Localiza o colaborador pelo ID do WordPress
$colaborador = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$prefix}tb_colaboradores WHERE id_user = %d", 
    $user_id
));

if ( ! $colaborador ) { wp_die('Colaborador não identificado.'); }


/* ======================================================
   BUSCA DE CONVOCAÇÃO (LÓGICA DE PREVALÊNCIA)
====================================================== */

// Tenta primeiro encontrar a última que NÃO seja DISPENSADO
$convocacao = $wpdb->get_row($wpdb->prepare(
    "SELECT c.*, p.ano AS ano_pleito, f.nom_funcao, l.nom_local, l.endereco, z.num_zona
     FROM {$prefix}tb_convocacao c
     INNER JOIN {$prefix}tb_pleitos p ON p.id_pleito = c.id_pleito
     LEFT JOIN {$prefix}tb_funcoes f ON f.id_funcao = c.id_funcao
     LEFT JOIN {$prefix}tb_locais l ON l.id_local = c.id_local
     LEFT JOIN {$prefix}tb_zonas z ON z.id_zona = l.id_zona
     WHERE c.id_colaborador = %d 
     AND p.status_pleito = 1 
     AND c.status_convocacao != 'DISPENSADO'
     ORDER BY c.id_convocacao DESC LIMIT 1",
    $colaborador->id_colaborador
));

// Se não houver nenhuma ativa, busca a última DISPENSADA
if ( ! $convocacao ) {
    $convocacao = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, p.ano AS ano_pleito, f.nom_funcao, l.nom_local, l.endereco, z.num_zona
         FROM {$prefix}tb_convocacao c
         INNER JOIN {$prefix}tb_pleitos p ON p.id_pleito = c.id_pleito
         LEFT JOIN {$prefix}tb_funcoes f ON f.id_funcao = c.id_funcao
         LEFT JOIN {$prefix}tb_locais l ON l.id_local = c.id_local
         LEFT JOIN {$prefix}tb_zonas z ON z.id_zona = l.id_zona
         WHERE c.id_colaborador = %d 
         AND p.status_pleito = 1 
         AND c.status_convocacao = 'DISPENSADO'
         ORDER BY c.id_convocacao DESC LIMIT 1",
        $colaborador->id_colaborador
    ));
}

if ( ! $convocacao ) {
    echo '<div class="notice notice-warning" style="margin:20px; padding:15px;"><p>Nenhuma convocação encontrada para este pleito.</p></div>';
    return;
}

/* ======================================================
   BUSCA DE EVENTOS (PELA VIEW)
====================================================== */
$eventos_lista = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ze_vw_eventos_vagas_locais 
     WHERE id_colaborador = %d 
     AND id_vaga_pleito = %d
     ORDER BY data_evento ASC, hora_inicio ASC",
    $colaborador->id_colaborador,
    $convocacao->id_vaga_pleito
));
/* ======================================================
   VERIFICAÇÃO DE INTEGRIDADE (Vaga ocupada)
====================================================== */
$status = $convocacao->status_convocacao;
$cor_badge = ($status === 'DISPENSADO') ? 'background: #fee2e2; color: #991b1b;' : '';

// Se NÃO estiver dispensado no registro, checamos se ele ainda é o dono da vaga na tb_vagas_pleitos
if ( $status !== 'DISPENSADO' ) {
    $vaga_atual = $wpdb->get_row($wpdb->prepare(
        "SELECT id_colaborador FROM {$prefix}tb_vagas_pleitos WHERE id_vaga_pleito = %d",
        $convocacao->id_vaga_pleito
    ));

    // Se o colaborador na vaga mudou, consideramos esta convocação como invalidada/dispensada visualmente
    if ( ! $vaga_atual || intval($vaga_atual->id_colaborador) !== intval($colaborador->id_colaborador) ) {
        $status = 'DISPENSADO';
        $cor_badge = 'background: #fee2e2; color: #991b1b;';
    }
}

// Configurações visuais baseadas no status final decidido acima
$aceita = ( $status === 'CONVOCACAO_ACEITA' );
$dispensado = ( $status === 'DISPENSADO' );

// Decodificação segura: se for null, passa uma string vazia '' para o json_decode
$eventos_json = json_decode($convocacao->eventos ?? '');

if (json_last_error() !== JSON_ERROR_NONE || !is_array($eventos_json)) {
    $eventos_json = [];
}

$eh_mrv = ( intval($convocacao->tp_secao_mrv) === 1 );


/* ======================================================
   SINCRONIZA STATUS ELEITORAL E STATUS DA VAGA
   Somente se a convocação estiver aceita
====================================================== */
if ( $aceita ) {

    // Atualiza status do colaborador
    $wpdb->update(
        "{$prefix}tb_colaboradores",
        [
            'ds_status_eleitoral' => 'CONVOCACAO_ACEITA',
            'updated_at'       => current_time('mysql')
        ],
        [
            'id_colaborador' => $colaborador->id_colaborador
        ],
        [ '%s', '%s' ],
        [ '%d' ]
    );

    // Atualiza status da vaga
    $wpdb->update(
        "{$prefix}tb_vagas_pleitos",
        [
            'status_vaga' => 'CONVOCACAO_ACEITA',
            'updated_at'  => current_time('mysql')
        ],
        [
            'id_vaga_pleito' => $convocacao->id_vaga_pleito
        ],
        [ '%s', '%s' ],
        [ '%d' ]
    );
}
$numero_convocacao = sprintf('%03dZE-%d-%06d', $convocacao->num_zona, $convocacao->ano_pleito, $convocacao->id_convocacao);

/* ======================================================
    VALIDAÇÃO DE PERFIL COM CAMPOS ESPECÍFICOS
====================================================== */
$campos_obrigatorios = [
    'id_upload_foto'         => 'Foto de perfil',
    'num_telefone_eleitor_2' => 'Telefone secundário',
    'email_colaborador'      => 'E-mail',
    'ds_camiseta'            => 'Tamanho da camiseta',
    'endereco_atualizado'    => 'Endereço atualizado'
];

$pendencias = [];
foreach ($campos_obrigatorios as $campo => $label) {
    // Verifica se o campo está vazio no objeto $colaborador
    if (empty($colaborador->$campo)) {
        $pendencias[] = $label;
    }
}

$perfil_completo = empty($pendencias);
$total_campos    = count($campos_obrigatorios);
$preenchidos     = $total_campos - count($pendencias);
$percentual      = ($preenchidos / $total_campos) * 100;
?>


<style>
    :root {
        --ze-primary: #1a237e; --ze-secondary: #0d47a1; --ze-accent: #fbc02d;
        --ze-success: #2e7d32; --ze-danger: #c62828; --ze-bg: #f4f7f9;
    }
    
    .ze-status-container {
            margin: 20px;
            padding: 25px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .ze-status-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .ze-status-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ze-status-percentage {
            font-size: 14px;
            font-weight: 600;
            color: <?= $perfil_completo ? 'var(--ze-success)' : '#64748b' ?>;
            background: <?= $perfil_completo ? '#f0fdf4' : '#f1f5f9' ?>;
            padding: 4px 12px;
            border-radius: 20px;
        }
        /* Barra de Progresso Minimalista */
        .ze-progress-wrapper {
            background: #f1f5f9;
            border-radius: 100px;
            height: 8px;
            width: 100%;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .ze-progress-bar-inner {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #22c55e);
            border-radius: 100px;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        /* Grid de Info e Pendências */
        .ze-status-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
        }
        .ze-info-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3b82f6;
        }
        .ze-info-box strong { color: #334155; font-size: 13px; display: block; margin-bottom: 5px; }
        .ze-info-box p { margin: 0; font-size: 12px; color: #64748b; line-height: 1.4; }

        .ze-pendency-box {
            padding: 10px 15px;
            border-radius: 8px;
            background: <?= $perfil_completo ? '#f0fdf4' : '#fff1f2' ?>;
            border: 1px solid <?= $perfil_completo ? '#bbf7d0' : '#fecdd3' ?>;
        }
        .ze-pendency-list {
            font-size: 12px;
            color: <?= $perfil_completo ? '#166534' : '#991b1b' ?>;
            margin-top: 5px;
            font-weight: 500;
        }
        .ze-link-profile {
            display: inline-block;
            margin-top: 10px;
            font-size: 12px;
            font-weight: 700;
            color: #3b82f6;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .ze-link-profile:hover { text-decoration: underline; }

       
       
    .ze-wrapper { font-family: 'Segoe UI', sans-serif; max-width: 850px; margin: 20px auto; background: #fff; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.1); overflow: hidden; }
    .ze-header { background: var(--ze-primary); color: white; padding: 40px 20px; text-align: center; }
    .ze-header h1 { color: white !important; margin: 0; font-size: 22px; text-transform: uppercase; }
    
    .ze-badge { display: inline-block; padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: bold; margin-top: 15px; }
    .status-aceita { background: var(--ze-success); color: white; }
    .status-pendente { background: var(--ze-accent); color: #000; }

    .ze-content { padding: 30px; }
    .ze-section { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
    .ze-section h2 { font-size: 18px; color: var(--ze-primary); display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
    .ze-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .ze-data-box { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--ze-secondary); }
    .ze-label { font-size: 11px; text-transform: uppercase; color: #666; font-weight: bold; display: block; }
    .ze-value { font-size: 15px; font-weight: 500; color: #333; }
    .ze-list { list-style: none; padding: 0; margin: 0; }
    .ze-list li { margin-bottom: 10px; padding-left: 25px; position: relative; font-size: 14px; line-height: 1.5; }
    .ze-list li::before { content: "❌"; position: absolute; left: 0; top: 0; }
    .ze-btn { background: var(--ze-primary); color: white !important; padding: 15px 30px; border-radius: 6px; text-decoration: none; display: inline-block; font-weight: 600; border: none; cursor: pointer; }
    .ze-footer-info { text-align: center; font-size: 13px; color: #777; margin-top: 40px; padding-top: 20px; border-top: 1px dashed #ccc; }
    @media (max-width: 600px) { .ze-grid { grid-template-columns: 1fr; } }
</style>

<div class="ze-admin-container">
    
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>
</div>    
<div class="ze-wrapper">

    <?php if (!$aceita && !$dispensado): ?>
        <div class="ze-status-container">
            <div class="ze-status-header">
                <h3 class="ze-status-title">
                    <span class="dashicons dashicons-admin-users" style="color:#3b82f6"></span> 
                    Status do Perfil de Colaborador
                </h3>
                <span class="ze-status-percentage"><?= round($percentual) ?>% Completo</span>
            </div>
    
            <div class="ze-progress-wrapper">
                <div class="ze-progress-bar-inner" style="width: <?= $percentual ?>%;"></div>
            </div>
    
            <div class="ze-status-grid">
                <div class="ze-info-box">
                    <strong>📸 Requisito de Identificação</strong>
                    <p>Sua foto será usada no <strong>crachá oficial</strong> e validada no Cartório. Use fundo neutro, sem óculos escuros ou boné (mínimo de formalidade exigido).</p>
                </div>
    
                <div class="ze-pendency-box">
                    <strong style="font-size: 11px; text-transform: uppercase; color: #475569;">
                        <?= $perfil_completo ? '✅ Tudo pronto' : '⚠️ Pendente para aceite' ?>
                    </strong>
                    <div class="ze-pendency-list">
                        <?php if (!$perfil_completo): ?>
                            Falta preencher: <?= implode(', ', $pendencias) ?>
                            <br>
                            <a href="<?= admin_url('admin.php?page=ze-legal-meu-perfil') ?>" class="ze-link-profile">Completar Perfil →</a>
                        <?php else: ?>
                            Seu cadastro atende aos requisitos para o aceite.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>   
<div class="ze-wrapper">    
    <header class="ze-header">
        <div style="font-size: 40px; margin-bottom: 10px;">🏛️</div>
        <h1>JUSTIÇA ELEITORAL</h1>
        <p style="margin: 5px 0 0 0; opacity: 0.9;"><?= esc_html($convocacao->num_zona) ?>ª ZONA ELEITORAL</p>
        
        <div class="ze-badge 
            <?= $aceita ? 'status-aceita' : ($dispensado ? '' : 'status-pendente') ?>" 
            style="<?= $dispensado ? 'background:#fee2e2;color:#991b1b;' : '' ?>">
            
            <?= $aceita 
                ? '✓ CONVOCAÇÃO ACEITA' 
                : ($dispensado 
                    ? '⛔ STATUS: DISPENSADO' 
                    : '⏳ AGUARDANDO ACEITE') ?>
        </div>
        
        <div style="margin-top: 15px; font-size: 12px; opacity: 0.8;">
            DOC: <?= esc_html($numero_convocacao) ?>
        </div>
    </header>

    <div class="ze-content">
        <div class="ze-section">
            <h2>👤 1. Identificação e Função</h2>
            <div class="ze-grid">
                <div class="ze-data-box">
                    <span class="ze-label">Nome do Convocado</span>
                    <span class="ze-value"><?= esc_html($colaborador->nom_eleitor) ?></span>
                </div>
                <div class="ze-data-box">
                    <span class="ze-label">Inscrição / CPF</span>
                    <span class="ze-value"><?= esc_html($colaborador->num_inscricao) ?> / <?= esc_html($colaborador->num_cpf) ?></span>
                </div>
                <div class="ze-data-box" style="grid-column: span 2; border-left-color: var(--ze-accent);">
                    <span class="ze-label">Função Designada</span>
                    <span class="ze-value" style="font-size: 17px; color: var(--ze-primary);">
                        <?= esc_html($convocacao->nom_funcao) ?> <?= $eh_mrv ? '(MRV)' : '' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="ze-section">
            <h2>📍 2. Local de Atuação</h2>
            <div class="ze-data-box">
                <span class="ze-value" style="display:block; margin-bottom: 5px;"><?= esc_html($convocacao->nom_local) ?></span>
                <span class="ze-label">Endereço: <?= esc_html($convocacao->endereco) ?></span>
                <span class="ze-label" style="margin-top:10px; color: var(--ze-primary);">Seção: <?= esc_html($convocacao->num_secao) ?></span>
            </div>
        </div>

        <div class="ze-section">
            <h2>🗓️ 3. Eventos e Treinamentos</h2>
            <?php if (!empty($eventos_lista)): ?>
                <div class="ze-grid">
                    <?php foreach ($eventos_lista as $e): ?>
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; border-top: 3px solid var(--ze-primary); box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <strong style="font-size: 14px; color: var(--ze-primary); display: block; margin-bottom: 5px;">
                                <?= esc_html($e->ds_tipo_evento) ?>
                            </strong>
                            
                            <div style="background: #f1f5f9; padding: 5px 10px; border-radius: 4px; display: inline-block; margin-bottom: 8px;">
                                <span style="font-size: 15px; font-weight: 800; color: #1e293b;">
                                    📅 <?= date('d/m/Y', strtotime($e->data_evento)) ?>
                                </span>
                            </div>
        
                            <br>
        
                            <small style="font-size: 13px; font-weight: 600; color: #475569;">
                                🕒 <?= !empty($e->hora_inicio) ? substr($e->hora_inicio, 0, 5) : '--:--' ?>
                                <?= !empty($e->hora_fim) ? ' às ' . substr($e->hora_fim, 0, 5) : '' ?>
                            </small>
        
                            <br>
        
                            <small style="color: #64748b; font-size: 12px; display: block; margin-top: 5px; line-height: 1.3;">
                                📍 <strong>Local:</strong> <?= esc_html($e->ds_local_evento) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="font-style: italic; font-size: 13px; color: #888; background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center;">
                    Nenhum evento agendado até o momento.
                </p>
            <?php endif; ?>
        </div>

        <div class="ze-section">
            <h2>✔️ 4. Direitos do Convocado</h2>
            <p style="font-size: 14px; background: #f1f8e9; padding: 15px; border-radius: 8px; border-left: 4px solid var(--ze-success);">
                A atuação nos serviços eleitorais assegura <strong>dispensa do serviço pelo dobro dos dias de convocação</strong>, conforme a legislação eleitoral vigente.
            </p>
        </div>

        <div class="ze-section">
            <?php if ($eh_mrv): ?>
                <h2>❌ 5. Vedações Específicas (MRV)</h2>
                <p style="font-size: 13px; font-weight: bold;">NÃO PODEM ATUAR COMO MEMBRO DE MESA RECEPTORA (MRV):</p>
                <ul class="ze-list">
                    <li>Candidatos e seus parentes, até o segundo grau, inclusive cônjuge;</li>
                    <li>Integrantes de diretórios partidários ou federações com função executiva;</li>
                    <li>Autoridades, agentes policiais e ocupantes de cargos de confiança do Executivo;</li>
                    <li>Eleitores menores de 18 anos.</li>
                </ul>
            <?php else: ?>
                <h2>❌ 5. Vedações e Neutralidade</h2>
                <p style="font-size: 13px; font-weight: bold;">É VEDADO AO CONVOCADO:</p>
                <ul class="ze-list">
                    <li>Fazer propaganda eleitoral de qualquer natureza;</li>
                    <li>Manifestar preferência político-partidária em serviço;</li>
                    <li>Atuar de forma a comprometer a neutralidade do pleito.</li>
                </ul>
            <?php endif; ?>
        </div>

        <div class="ze-section" style="text-align: center; background: #fafafa; padding: 40px 20px; border: 2px dashed #ddd; border-radius: 10px;">
            <h2>📝 7. Aceite da Convocação</h2>
            <?php if (!$aceita && !$dispensado && $perfil_completo): ?>
                <form method="post" action="<?= esc_url(admin_url('admin-post.php')) ?>">
                    <?php wp_nonce_field('ze_aceitar_convocacao'); ?>
                    <input type="hidden" name="action" value="ze_aceitar_convocacao">
                    <input type="hidden" name="id_convocacao" value="<?= intval($convocacao->id_convocacao) ?>">
                    
                    <p style="font-size: 16px; margin-bottom: 25px;">
                        <label style="cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <input type="checkbox" required style="width: 20px; height: 20px; margin-right: 10px;">
                            <strong>Declaro ciência desta convocação e aceito a designação.</strong>
                        </label>
                    </p>
                    <button type="submit" class="ze-btn">Confirmar Aceite Eletrônico</button>
                </form>
            <?php elseif ($aceita): ?>
                <div style="color: var(--ze-success);">
                    <div style="font-size: 40px; margin-bottom: 5px;">✅</div>
                    <p><strong>Aceite eletrônico realizado com sucesso!</strong><br>
                    Realizado em: <?= date('d/m/Y H:i', strtotime($convocacao->data_aceite)) ?></p>
                    <a href="<?= esc_url(admin_url('admin-post.php?action=ze_gerar_pdf_convocacao&id=' . $convocacao->id_convocacao)) ?>" class="ze-btn" style="background: #444; margin-top: 20px;">
                        📥 Baixar Carta Convocatória (PDF)
                    </a>
                </div>
            <?php elseif ($dispensado): ?>
                <div style="color:#991b1b;">
                    <div style="font-size:40px;margin-bottom:5px;">⛔</div>
                    <p><strong>Convocação dispensada.</strong><br>
                    Esta convocação não está mais ativa e não pode ser aceita nem gerar carta.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>