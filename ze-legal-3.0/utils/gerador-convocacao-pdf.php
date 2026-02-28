<?php
if ( ! defined('ABSPATH') ) exit;


use Dompdf\Dompdf;
use Dompdf\Options;


/**
 * Função principal para gerar a Carta Convocatória em PDF
 */
function ze_legal_gerar_pdf_carta() {
    global $wpdb;

    // --- SEGURANÇA: COLOQUE AQUI DENTRO ---
    if ( ! current_user_can( 'ze_profile_edit' ) ) {
        wp_die( 'Acesso não autorizado.' );
    }
    // --------------------------------------

    $prefix = $wpdb->prefix . 'ze_';
    
    // 1. Validar ID e Segurança adicional
    if ( ! isset($_GET['id']) ) {
        wp_die('ID da convocação não fornecido.');
    }

    $id_conv = intval($_GET['id']);
    
    // 2. Consulta ajustada para trazer o nome do usuário que entregou/registrou
    $sql = "SELECT c.*, col.nom_eleitor, col.id_upload_foto, col.num_inscricao, col.num_cpf, col.num_telefone_eleitor,
                   f.nom_funcao, l.nom_local, l.endereco as local_endereco, 
                   z.num_zona, z.chefe_cartorio, z.descricao as nome_zona, 
                   z.juiz, z.endereco as zona_endereco,
                   p.ano as ano_pleito, m.nom_municipio,
                   u_ent.display_name as nome_usuario_entrega
            FROM {$prefix}tb_convocacao c
            INNER JOIN {$prefix}tb_colaboradores col ON col.id_colaborador = c.id_colaborador
            INNER JOIN {$prefix}tb_pleitos p ON p.id_pleito = c.id_pleito
            LEFT JOIN {$prefix}tb_funcoes f ON f.id_funcao = c.id_funcao
            LEFT JOIN {$prefix}tb_locais l ON l.id_local = c.id_local
            LEFT JOIN {$prefix}tb_zonas z ON z.id_zona = l.id_zona
            LEFT JOIN {$prefix}tb_municipios m ON m.id_municipio = l.id_municipio
            LEFT JOIN {$wpdb->users} u_ent ON u_ent.ID = c.id_usuario_entrega
            WHERE c.id_convocacao = %d";

    $data = $wpdb->get_row($wpdb->prepare($sql, $id_conv));

    if (!$data) wp_die('Convocação não encontrada.');

    /* ======================================================
     * NOVO: BUSCA DE EVENTOS PELA VIEW (Substituindo JSON)
     * ====================================================== */
    $eventos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ze_vw_eventos_vagas_locais 
         WHERE id_colaborador = %d 
         AND id_vaga_pleito = %d
         ORDER BY data_evento ASC, hora_inicio ASC",
        $data->id_colaborador,
        $data->id_vaga_pleito
    ));


    /* ======================================================
     * ALTERAÇÃO: LÓGICA DA FOTO DO COLABORADOR
     * ====================================================== */
    // Verificamos se existe um ID de upload e tentamos pegar a URL da imagem
    $foto_colaborador_url = !empty($data->id_upload_foto) ? wp_get_attachment_url($data->id_upload_foto) : false;

    // Se a URL existir, usamos ela. Caso contrário, usamos o carimbo padrão.
    $imagem_perfil = ($foto_colaborador_url) ? $foto_colaborador_url : ZE_LEGAL_PATH . 'admin/assets/images/ui/carimbo-voluntario.png';
    /* ====================================================== */

    /* ======================================================
     * REGISTRO DE DOWNLOAD (AUDITORIA)
     * ====================================================== */
    $wpdb->update(
        "{$prefix}tb_convocacao",
        array(
            'data_download' => current_time('mysql'),
            'ip_download'   => $_SERVER['REMOTE_ADDR']
        ),
        array('id_convocacao' => $id_conv),
        array('%s', '%s'),
        array('%d')
    );

    // 3. Processamento de Dados
    $is_mrv = (intval($data->tp_secao_mrv) === 1);
    
    
    //$eventos = json_decode($data->eventos);
    $num_convocacao = sprintf('%03dZE-%d-%06d', $data->num_zona, $data->ano_pleito, $data->id_convocacao);

    // Lógica do Carimbo (Stamp)
    // Se houve IP de aceite, foi Web. Se não tem IP mas está entregue, foi Cartório.
    $is_web = !empty($data->ip_aceite);
    $is_cartorio = !$is_web && (intval($data->entregue_em_maos) === 1);

    $ip_raw = $is_web ? $data->ip_aceite : $_SERVER['REMOTE_ADDR'];
    $ip_formatado = (filter_var($ip_raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 'IPv6: ' : 'IPv4: ') . $ip_raw;

    // Máscara de Telefone Internacional (+55...)
    $fone = preg_replace('/\D/', '', $data->num_telefone_eleitor); // Remove caracteres não numéricos
    if (strlen($fone) >= 12) {
        $fone_formatado = "+" . substr($fone, 0, 2) . " (" . substr($fone, 2, 2) . ") " . substr($fone, 4, 5) . "-" . substr($fone, 9);
    } else {
        $fone_formatado = $data->num_telefone_eleitor ?: 'Não informado';
    }
    
    /* ======================================================
     * IP DE ACEITE – NORMALIZAÇÃO E IDENTIFICAÇÃO
     * ====================================================== */
    $ip_raw = $data->ip_aceite;
    
    if (filter_var($ip_raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $ip_formatado = 'IPv6: ' . $ip_raw;
    } elseif (filter_var($ip_raw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip_formatado = 'IPv4: ' . $ip_raw;
    } else {
        $ip_formatado = 'Não registrado';
    }
    
    // URL do Código de Barras
    $barcode_url = "https://bwipjs-api.metafloor.com/?bcid=code128&text=" . $data->num_inscricao . "&scale=2&rotate=N&includetext=false";   

    // 4. Geração do HTML
    ob_start();
    ?>
    <!DOCTYPE html>
    
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <style>
            @page { margin: 1.2cm; }
            body { font-family: 'Helvetica', sans-serif; color: #1a1a1a; line-height: 1.4; font-size: 11pt; }
            
            /* Header com suporte a Dompdf (Table em vez de Flex) */
            .header-table { width: 100%; border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 10px; }
            .header-center { text-align: center; }
            .header-center h1 { margin: 0; font-size: 22pt; text-transform: uppercase; color: #000; }
            .header-center .tre-info { font-weight: bold; font-size: 13pt; margin-top: 2px; }

            .doc-info { text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 20px; color: #333; }
            
            .section-title { background: #f0f0f0; padding: 5px 10px; font-weight: bold; color: #1a237e; 
                             margin-top: 15px; border-left: 4px solid #1a237e; text-transform: uppercase; font-size: 10pt; }
            
            .content-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            .label { font-size: 8pt; text-transform: uppercase; color: #666; font-weight: bold; display: block; margin-bottom: 2px; }
            .value { font-size: 11pt; font-weight: bold; color: #000; }
            
            .event-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            .event-table td { padding: 6px; border-bottom: 1px solid #eee; font-size: 9pt; }
            
            .stamp-box { margin-top: 25px; border: 2px dashed #2e7d32; padding: 12px; text-align: center; border-radius: 8px; background-color: #f9fff9; }
            .stamp-title { color: #2e7d32; font-weight: bold; font-size: 10pt; margin-bottom: 3px; }
            
            .footer { margin-top: 40px; text-align: center; font-size: 10pt; }
            .signature-line { width: 280px; border-top: 1px solid #333; margin: 0 auto 5px auto; }
        </style>
    </head>
    
    <body>
    <table class="header-table">
        <tr>
            <td style="width:100px;"><img src="<?= str_replace('\\', '/', ZE_LEGAL_PATH) . 'admin/assets/images/ui/logo-je.png' ?>" style="width:90px;">
            <td class="header-center">
                <h1>Justiça Eleitoral</h1>
                <div style="font-weight:bold;font-size:12pt;">Tribunal Regional Eleitoral</div>
                <div style="font-weight:bold;font-size:12pt;"><?= esc_html($data->nome_zona) ?></div>
            </td>
            <td style="width:100px;text-align:right;">
                <img src="<?= $imagem_perfil ?>" style="width:90px; height: auto; max-height: 120px;">
            </td>
        </tr>
    </table>
    
    <div class="doc-info">CARTA CONVOCATÓRIA Nº <?= $num_convocacao ?></div>
    
    <div class="section-title">1. Convocação</div>
    
    <p style="
    text-align: justify;
    text-justify: inter-word;
    line-height: 1.6;
    text-indent: 0cm;
    word-spacing: -0.05em;
    ">
    
        Por determinação do(a) Juiz(a) Eleitoral
        <strong><?= $data->juiz ?> da </strong>
        <strong><?= $data->nome_zona ?></strong>,
        o(a) senhor(a)
        <strong><?= esc_html($data->nom_eleitor) ?></strong>,
        Título Eleitoral
        <strong><?= $data->num_inscricao ?></strong>,
        CPF
        <strong><?= $data->num_cpf ?></strong>,
        Telefone
        <strong><?= $fone_formatado ?></strong>,
        está convocado(a) para atuar na função de
        <strong><?= esc_html($data->nom_funcao) ?></strong>
        <?= $is_mrv ? '(MRV)' : '' ?>,
        no(a)
        <strong><?= $data->nom_local ?></strong>,
        <?= $is_mrv ? 'Seção <strong>' . $data->num_secao . '</strong>,' : '' ?>
        localizado a
        <strong><?= esc_html($data->local_endereco) ?></strong>.
    </p>
    
    <div class="section-title">2. Direitos do Convocado</div>
    
    <p>
        A atuação nos serviços eleitorais assegura
        <strong>dispensa do serviço pelo dobro dos dias de convocação</strong>,
        conforme a legislação eleitoral vigente.
    </p>
    
    <?php if ($is_mrv): ?>
    
        <div class="section-title">3. Vedações Específicas (MRV)</div>
    
        <p><strong>NÃO PODEM ATUAR COMO MEMBRO DE MESA RECEPTORA DE VOTOS (MRV):</strong></p>
    
        <ul style="font-size:9.5pt;">
            <li>Candidatos e seus parentes, até o segundo grau, inclusive cônjuge;</li>
            <li> Integrantes de diretórios partidários ou federações com função executiva;</li>
            <li> Autoridades e agentes policiais, bem como ocupantes de cargos de confiança do Poder Executivo;</li>
            <li> Pessoas que já integrem o serviço eleitoral;</li>
            <li> Eleitores menores de 18 (dezoito) anos.</li>
        </ul>
    
        <p><strong> <li>Também é vedada a participação, na mesma mesa receptora, de parentes em qualquer grau ou de servidores da mesma repartição pública ou empresa privada.</li>
        </strong></p>
    
        <p>
            Caso o(a) convocado(a) se enquadre em qualquer das situações acima,
            deverá comunicar o Juízo Eleitoral no prazo de até
            <strong>5 (cinco) dias</strong>, o descumprimento desse dever poderá resultar nas penalidades previstas em lei.
        </p>
    
        <div class="section-title">4. Consequências do Não Comparecimento</div>
    
        <p>
            O não atendimento injustificado a esta convocação poderá resultar
            na aplicação das penalidades previstas na legislação eleitoral.
        </p>
    
    <?php else: ?>
    
        <div class="section-title">3. Vedações Aplicáveis às Demais Funções</div>
    
        <p>
            Mesmo não sendo equiparado(a) aos Membros de Mesa Receptora de Votos,
            o(a) convocado(a) para demais funções <strong>NÃO PODERÁ</strong>:
        </p>
    
        <ul style="font-size:9.5pt;">
            <li> Fazer propaganda eleitoral;</li>
            <li> Manifestar preferência político-partidária;</li>
            <li> Comprometer a neutralidade do pleito.</li>
        </ul>
    
        <div class="section-title">4. Consequências do Não Comparecimento</div>
    
        <p>
            O não atendimento injustificado a esta convocação poderá resultar
            na aplicação das penalidades previstas na legislação eleitoral.
        </p>
    
    <?php endif; ?>
    
    <p><?= esc_html($data->nom_municipio) ?>, <?= date_i18n('d \d\e F \d\e Y') ?></p>
    <div class="footer">
        
        <div style="margin-top:5px;">
            Documento gerado eletronicamente – Portaria 02/2026/2ª Zona Eleitoral
            <div class=""></div>
            <strong><?= esc_html($data->chefe_cartorio) ?></strong><br>
            Chefe de Cartório – <?= $data->nome_zona ?>
        </div>
    </div>
    
    <div style="page-break-before: always;"></div>
    
    <div class="section-title">Registro do Aceite da Convocação</div>
    
    <div class="stamp-box">
            <?php if ($is_web): ?>
                <div style="color:#2e7d32; font-weight:bold; font-size:11pt; margin-bottom:5px;">ACEITE ELETRÔNICO CONFIRMADO</div>
                <div style="font-size:9pt; color:#2e7d32; line-height:1.5;">
                    Recebido eletronicamente por <strong><?= esc_html($data->nom_eleitor) ?></strong><br>
                    em <strong><?= date('d/m/Y \à\s H:i', strtotime($data->data_aceite)) ?></strong><br>
                    Endereço IP: <?= $ip_formatado ?>
                </div>

            <?php elseif ($is_cartorio): ?>
                <div style="color:#2e7d32; font-weight:bold; font-size:11pt; margin-bottom:5px;">ACEITE EM CARTÓRIO CONFIRMADO</div>
                <div style="font-size:9pt; color:#2e7d32; line-height:1.5;">
                    A Carta Convocatória foi entregue por <strong><?= esc_html($data->nome_usuario_entrega) ?></strong><br>
                    em <strong><?= date('d/m/Y \à\s H:i', strtotime($data->data_entrega_em_maos)) ?></strong><br>
                    
                </div>

            <?php else: ?>
                <div style="color:#d32f2f; font-weight:bold; font-size:11pt;">AGUARDANDO FORMALIZAÇÃO DO ACEITE</div>
            <?php endif; ?>
        </div>
    
    <div class="section-title">ANEXO I – Agenda de Eventos e Treinamentos</div>

    
    <?php if (!empty($eventos)): ?>
    
    <?php
    usort($eventos, function ($a, $b) {
        return strtotime($a->data_evento . ' ' . $a->hora_inicio)
             <=> strtotime($b->data_evento . ' ' . $b->hora_inicio);
    });
    ?>
    
    <ul style="list-style:none;padding-left:0;font-size:10pt;">
    <?php foreach ($eventos as $e): ?>
        <li style="margin-bottom:6px;">
            <strong><?= date('d/m/Y', strtotime($e->data_evento)) ?></strong>
            às <?= substr($e->hora_inicio,0,5) ?>h —
            <strong><?= esc_html($e->ds_tipo_evento) ?></strong> —
            <?= esc_html($e->ds_local_evento) ?>
        </li>
    <?php endforeach; ?>
    </ul>
    
    <?php else: ?>
    <p style="font-size:9pt;">Não há eventos registrados para esta convocação.</p>
    <?php endif; ?>
    
    </body>
        
    
    
    </html>
    <?php
    $html = ob_get_clean();
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('chroot', ZE_LEGAL_PATH); // Permite que o Dompdf acesse seus arquivos locais
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Convocacao-" . $num_convocacao . "-" . $data->nom_eleitor . ".pdf", array("Attachment" => false));
    exit;
}
add_action('admin_post_ze_gerar_pdf_convocacao', 'ze_legal_gerar_pdf_carta');