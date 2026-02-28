<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança baseada no TOKEN e não no LOGIN
$token_valido = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

if ( empty($token_valido) ) {
    echo '<div style="text-align:center; padding:50px;">
            <h2>Acesso Restrito</h2>
            <p>Por favor, utilize o QR Code oficial do evento para registrar sua presença.</p>
          </div>';
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix;
$vw_eventos = $prefix . 'ze_vw_eventos_vagas_locais';
$table_eventos = $prefix . 'ze_tb_eventos_vagas';

$data_hoje = current_time('Y-m-d');
$token_url = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
$mensagem = '';
$sucesso = false;

// 1. Processamento do CPF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpf_colaborador'])) {
    $cpf_limpo = preg_replace('/\D/', '', $_POST['cpf_colaborador']);
    
    // Validação: Busca o evento para este CPF, nesta Data e com este Token (Código de Acesso)
    $evento = $wpdb->get_row($wpdb->prepare(
        "SELECT id_evento_vaga, nom_eleitor FROM $vw_eventos 
         WHERE num_cpf = %s AND data_evento = %s AND codigo_acesso = %s LIMIT 1",
        $cpf_limpo, $data_hoje, $token_url
    ));

    if ($evento) {
        $wpdb->update($table_eventos, ['compareceu' => 1], ['id_evento_vaga' => $evento->id_evento_vaga]);
        $sucesso = true;
        $primeiro_nome = explode(' ', $evento->nom_eleitor)[0];
    } else {
        $mensagem = 'Não encontramos nenhum evento ativo para este CPF com o código informado hoje.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Justiça Eleitoral - Registro de Presença</title>
    <style>
        :root { --primary: #1a237e; --secondary: #3b82f6; --success: #10b981; --error: #ef4444; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f1f5f9; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .card { background: white; width: 90%; max-width: 400px; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; }
        .logo { width: 70px; margin-bottom: 20px; }
        h1 { font-size: 20px; color: var(--primary); margin: 0 0 10px 0; }
        .date-badge { background: #e2e8f0; color: #475569; padding: 5px 12px; border-radius: 15px; font-size: 13px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 15px; margin: 25px 0 15px 0; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 20px; text-align: center; box-sizing: border-box; transition: border-color 0.2s; }
        input[type="text"]:focus { border-color: var(--secondary); outline: none; }
        .btn { background: var(--primary); color: white; border: none; width: 100%; padding: 15px; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .btn:active { background: var(--secondary); }
        .alert { padding: 15px; border-radius: 10px; font-size: 14px; font-weight: 500; margin-top: 15px; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; font-size: 16px; }
    </style>
</head>
<body>

<div class="card">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/Coat_of_arms_of_Brazil.svg/1024px-Coat_of_arms_of_Brazil.svg.png" class="logo" alt="Brasão">
    <h1>Presença Eletrônica</h1>
    <span class="date-badge">📅 <?= date('d/m/Y') ?></span>

    <?php if (empty($token_url)): ?>
        <div class="alert alert-error">Acesso negado. Utilize o QR Code oficial do local do evento.</div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success">
            <strong>Confirmado!</strong><br>
            Olá, <?= esc_html($primeiro_nome) ?>. Sua presença foi registrada com sucesso.
        </div>
        <p style="color:#64748b; font-size:12px; margin-top:20px;">Você já pode fechar esta página.</p>
    <?php else: ?>
        <form method="post">
            <input type="text" name="cpf_colaborador" id="cpf" placeholder="000.000.000-00" inputmode="numeric" required>
            
            <?php if ($mensagem): ?>
                <div class="alert alert-error"><?= $mensagem ?></div>
            <?php endif; ?>

            <button type="submit" class="btn">REGISTRAR PRESENÇA</button>
        </form>
    <?php endif; ?>
    
    <div style="margin-top: 40px; font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">
        Tribunal Regional Eleitoral
    </div>
</div>

<script>
    // Máscara de CPF automática
    document.getElementById('cpf').addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d)/, '$1.$2');
        v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = v;
    });
</script>

</body>
</html>