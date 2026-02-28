<?php
if (!defined('ABSPATH')) exit;

if (!current_user_can('ze_cadastro_adm_cartorio')) {
    wp_die('Acesso não autorizado.');
}

global $wpdb;
$prefix = $wpdb->prefix;
$vw_eventos = $prefix . 'ze_vw_eventos_vagas_locais';
$table_eventos = $prefix . 'ze_tb_eventos_vagas';

$datas_disponiveis = $wpdb->get_col("SELECT DISTINCT data_evento FROM $vw_eventos ORDER BY data_evento DESC");
$locais_disponiveis = $wpdb->get_results("SELECT DISTINCT id_local, nom_local FROM $vw_eventos ORDER BY nom_local ASC");

$lista_presentes = [];

if (!empty($_GET['data_evento'])) {
    $data_sel = sanitize_text_field($_GET['data_evento']);
    $sql = $wpdb->prepare(
        "SELECT nom_eleitor, num_inscricao, nom_funcao
         FROM $vw_eventos
         WHERE data_evento = %s
         AND compareceu = 1
         ORDER BY nom_eleitor ASC",
        $data_sel
    );
    $lista_presentes = $wpdb->get_results($sql);
}
?>

<style>
    .ze-attendance-wrap { max-width: 1000px; margin: 20px auto; font-family: 'Inter', sans-serif; }
    .ze-flex-row { display: flex; gap: 20px; margin-bottom: 20px; }
    .ze-panel { background: #fff; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .scan-zone { background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 30px; text-align: center; }
    .scan-input { width:100%; max-width:400px; font-size:28px; padding:15px; text-align:center; border:3px solid #1e293b; border-radius:8px; margin-top:15px; font-family:monospace; }
    .scan-input:focus { border-color:#2563eb; outline:none; background:#fff; box-shadow:0 0 0 4px rgba(37,99,235,0.1); }
    .status-display { margin-top:20px; min-height:80px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:bold; }
    .success-box { background:#dcfce7; color:#166534; width:100%; padding:15px; border-radius:8px; }
    .error-box { background:#fee2e2; color:#991b1b; width:100%; padding:15px; border-radius:8px; }
    .ze-table { width:100%; border-collapse:collapse; margin-top:15px; font-size:13px; }
    .ze-table th { background:#f1f5f9; padding:12px; text-align:left; color:#475569; border-bottom:2px solid #e2e8f0; }
    .ze-table td { padding:12px; border-bottom:1px solid #f1f5f9; }
    .ze-table tr:nth-child(even){ background:#fafafa; }
    .badge-presente{ background:#dcfce7; color:#166534; padding:3px 8px; border-radius:4px; font-weight:bold; font-size:11px; }
    .filter-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:15px; align-items:flex-end; }
    label{ display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:5px; text-transform:uppercase; }
    select,input{ padding:8px; border:1px solid #cbd5e1; border-radius:6px; width:100%; }
</style>

<div class="ze-attendance-wrap">

    <div class="ze-flex-row">
        <div class="ze-panel" style="flex:1;">
            <h3 style="margin-top:0;">📄 Gerar Listas de Assinatura</h3>
            <form method="get" action="<?= admin_url('admin-post.php') ?>" target="_blank">
                <input type="hidden" name="action" value="ze_gerar_lista_assinatura_pdf">
                <div class="filter-grid">
                    <div>
                        <label>Data do Evento</label>
                        <select name="data" required>
                            <?php foreach($datas_disponiveis as $d): ?>
                                <option value="<?= esc_attr($d) ?>"><?= date('d/m/Y', strtotime($d)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Período</label>
                        <select name="turno">
                            <option value="AM">Manhã (AM)</option>
                            <option value="PM">Tarde (PM)</option>
                        </select>
                    </div>
                    <div>
                        <label>Filtrar Local</label>
                        <select name="local">
                            <option value="0">Todos os Locais</option>
                            <?php foreach($locais_disponiveis as $loc): ?>
                                <option value="<?= esc_attr($loc->id_local) ?>"><?= esc_html($loc->nom_local) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="button button-primary" style="height:35px;">Gerar PDF</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ze-panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">🚀 Registro em Lote (Scanner)</h2>
            <div style="background:#1e293b; color:#fff; padding:5px 15px; border-radius:20px; font-size:12px;">
                Data Ativa: 
                <span id="label_data_ativa">
                    <?= !empty($_GET['data_evento']) ? date('d/m/Y', strtotime($_GET['data_evento'])) : '--/--/----' ?>
                </span>
            </div>
        </div>

        <div class="scan-zone">
            <form method="get">
                <input type="hidden" name="page" value="<?= esc_attr($_GET['page'] ?? '') ?>">
                <label><strong>1. Selecione a data para registro:</strong></label>
                <select name="data_evento" style="max-width:300px; margin:10px auto 25px; display:block;" onchange="this.form.submit()">
                    <option value="">Escolha a data...</option>
                    <?php foreach($datas_disponiveis as $d): ?>
                        <option value="<?= esc_attr($d) ?>" <?= (isset($_GET['data_evento']) && $_GET['data_evento']==$d)?'selected':'' ?>>
                            <?= date('d/m/Y', strtotime($d)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <label><strong>2. Leia o Título de Eleitor / Inscrição:</strong></label>
            <input type="text" id="barcode_input" class="scan-input" placeholder="Aguardando Leitor..." autocomplete="off">
            <p style="font-size:12px; color:#94a3b8; margin-top:10px;">O sistema processa automaticamente após a leitura completa.</p>
        </div>

        <div id="status_feedback" class="status-display">
            <div style="color:#94a3b8; font-style:italic;">Aguardando primeira leitura...</div>
        </div>
    </div>

    <div class="ze-panel">
        <h3 style="margin-top:0;">✅ Colaboradores Presentes</h3>

        <?php if (!empty($_GET['data_evento'])): ?>
            <?php if (!empty($lista_presentes)): ?>
                <table class="ze-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Inscrição</th>
                            <th>Função</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($lista_presentes as $item): ?>
                            <tr>
                                <td><strong><?= esc_html($item->nom_eleitor) ?></strong></td>
                                <td><?= esc_html($item->num_inscricao) ?></td>
                                <td><?= esc_html($item->nom_funcao) ?></td>
                                <td><span class="badge-presente">Presente</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; color:#94a3b8;">Nenhum registro de presença para este dia.</p>
            <?php endif; ?>
        <?php else: ?>
            <p style="text-align:center; color:#94a3b8;">Selecione uma data para carregar os presentes.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    const barcodeInput = document.getElementById('barcode_input');
    const feedback = document.getElementById('status_feedback');
    const dataSelect = document.querySelector('select[name="data_evento"]');

    barcodeInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            processarLeitura();
        }
    });

    barcodeInput.addEventListener('change', function() {
        if (this.value.length > 5) processarLeitura();
    });

    function processarLeitura() {
        const inscricao = barcodeInput.value.trim();
        const dataEv = dataSelect.value;

        if (!dataEv) {
            alert('Por favor, selecione primeiro a data do evento.');
            barcodeInput.value = '';
            dataSelect.focus();
            return;
        }

        if (inscricao.length < 5) return;

        barcodeInput.disabled = true;
        feedback.innerHTML = '<span style="color:#2563eb">Processando inscrição ' + inscricao + '...</span>';

        fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'ze_registro_barcode',
                num_inscricao: inscricao,
                data_evento: dataEv
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                feedback.innerHTML = `<div class="success-box">
                    <span style="font-size:24px;">✅</span><br>
                    <strong>${data.data.nome}</strong><br>
                    <small>${data.data.funcao}</small>
                </div>`;
                tocarSomSucesso();
                location.reload(); 
            } else {
                feedback.innerHTML = `<div class="error-box"><span style="font-size:24px;">❌</span><br>${data.data.msg}</div>`;
            }
        })
        .catch(() => {
            feedback.innerHTML = '<div class="error-box">Erro de conexão com o servidor.</div>';
        })
        .finally(() => {
            barcodeInput.disabled = false;
            barcodeInput.value = '';
            barcodeInput.focus();
        });
    }

    function tocarSomSucesso() {
        const context = new(window.AudioContext || window.webkitAudioContext)();
        const osc = context.createOscillator();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, context.currentTime);
        osc.connect(context.destination);
        osc.start();
        osc.stop(context.currentTime + 0.1);
    }
</script>