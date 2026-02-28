<?php
if ( ! defined('ABSPATH') ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$tabela = $wpdb->prefix . 'ze_tb_zonas';
$tabela_locais = $wpdb->prefix . 'ze_tb_locais';

$erro_exclusao = null;

/* =========================
 * EXCLUSÃO (Segurança Extra)
 * ========================= */
if ( isset($_POST['zelegal_excluir_zona']) ) {
    check_admin_referer('zelegal_excluir_zona');
    $id_zona = intval($_POST['zelegal_excluir_zona']);
    $total_locais = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tabela_locais} WHERE id_zona = %d", $id_zona));

    if ( $total_locais > 0 ) {
        $erro_exclusao = 'Não é possível excluir esta zona porque existem locais cadastrados.';
    } else {
        $wpdb->delete($tabela, ['id_zona' => $id_zona], ['%d']);
    }
}

/* =========================
 * INSERT / UPDATE
 * ========================= */
if ( isset($_POST['zelegal_salvar_zona']) ) {
    check_admin_referer('zelegal_cadastro_zona');
    $fmt = function($v){
        if(!$v) return null;
        $n = preg_replace('/\D/','',$v);
        if(!str_starts_with($n,'55')) $n='55'.$n;
        return '+'.$n;
    };

    $dados = [
        'num_zona'       => sanitize_text_field($_POST['num_zona']),
        'descricao'      => sanitize_text_field($_POST['descricao']),
        'juiz'           => sanitize_text_field($_POST['juiz']),
        'chefe_cartorio' => sanitize_text_field($_POST['chefe_cartorio']),
        'email'          => sanitize_email($_POST['email']),
        'endereco'       => substr(sanitize_text_field($_POST['endereco']),0,130),
        'contato_1'      => $fmt($_POST['contato_1']),
        'contato_2'      => $fmt($_POST['contato_2']),
        'contato_3'      => $fmt($_POST['contato_3']),
        'updated_at'     => current_time('mysql'),
    ];

    if (!empty($_POST['id_zona'])) {
        $wpdb->update($tabela, $dados, ['id_zona' => intval($_POST['id_zona'])]);
    } else {
        $dados['created_at'] = current_time('mysql');
        $wpdb->insert($tabela, $dados);
    }
}

$zonas = $wpdb->get_results("
    SELECT z.*, (SELECT COUNT(*) FROM {$tabela_locais} l WHERE l.id_zona = z.id_zona) AS total_locais
    FROM {$tabela} z ORDER BY z.num_zona ASC
");
?>

<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>
    
    <header class="ze-header-main">
        <h1 class="ze-page-title"></span> Cadastro de Zonas Eleitorais</h1>
        <p>Cadastre e gerencie os dados dos cartórios e zonas eleitorais.</p>
    </header>

    <?php if ($erro_exclusao): ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html($erro_exclusao); ?></p></div>
    <?php endif; ?>

    <div class="ze-card">
        <h2><span class="dashicons dashicons-location-alt"></span> Detalhes da Zona</h2>
        <form method="post" id="zelegal-form-zona">
            <?php wp_nonce_field('zelegal_cadastro_zona'); ?>
            <input type="hidden" name="id_zona">

            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Nº Zona</label>
                    <input type="text" name="num_zona" maxlength="4" required placeholder="1">
                </div>
                <div class="ze-form-group" style="grid-column: span 3">
                    <label>Descrição</label>
                    <input type="text" name="descricao">
                </div>
                <div class="ze-form-group" style="grid-column: span 2">
                    <label>Juiz Eleitoral</label>
                    <input type="text" name="juiz">
                </div>
                <div class="ze-form-group" style="grid-column: span 2">
                    <label>Chefe do Cartório</label>
                    <input type="text" name="chefe_cartorio">
                </div>
                <div class="ze-form-group" style="grid-column: span 2">
                    <label>E-mail</label>
                    <input type="email" name="email">
                </div>
                <div class="ze-form-group" style="grid-column: span 2">
                    <label>Endereço</label>
                    <input type="text" name="endereco" maxlength="130">
                </div>
                <div class="ze-form-group">
                    <label>Telefone 1</label>
                    <input type="text" name="contato_1" class="zelegal-phone">
                </div>
                <div class="ze-form-group">
                    <label>Telefone 2</label>
                    <input type="text" name="contato_2" class="zelegal-phone">
                </div>
                <div class="ze-form-group" style="grid-column: span 2">
                    <label>WhatsApp</label>
                    <input type="text" name="contato_3" class="zelegal-phone">
                </div>
            </div>

            <div class="ze-form-footer">
                <button type="submit" name="zelegal_salvar_zona" class="ze-btn-submit" >
                    Salvar Zona
                </button>
            </div>
        </form>
    </div>

    <div class="ze-card">
        <h2><span class="dashicons dashicons-list-view"></span> Zonas Registradas</h2>
        <div class="ze-table-container">
            <table class="ze-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Zona</th>
                        <th>Descrição / Localidade</th>
                        <th>Contato Principal</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($zonas): foreach ($zonas as $z): ?>
                    <tr>
                        <td><strong><?= esc_html($z->num_zona); ?></strong></td>
                        <td><?= esc_html($z->descricao); ?></td>
                        <td><?= esc_html($z->contato_1); ?></td>
                        <td style="text-align: right;">
                            <button class="btn-ze-outline zelegal-editar-zona" data-zona='<?= esc_attr(json_encode($z)); ?>'>
                                Editar
                            </button>
                            <?php if ((int)$z->total_locais === 0): ?>
                                <form method="post" style="display:inline">
                                    <?php wp_nonce_field('zelegal_excluir_zona'); ?>
                                    <input type="hidden" name="zelegal_excluir_zona" value="<?= intval($z->id_zona); ?>">
                                    <button type="submit" class="btn-ze-outline btn-ze-danger" onclick="return confirm('Excluir zona?')">Excluir</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4" style="text-align:center; padding: 30px;">Nenhuma zona cadastrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Scripts mantidos integralmente para funcionalidade
document.querySelectorAll('.zelegal-editar-zona').forEach(btn=>{
    btn.addEventListener('click',()=>{
        const z = JSON.parse(btn.dataset.zona);
        const f = document.getElementById('zelegal-form-zona');
        Object.keys(z).forEach(k=>{ if(f[k]) f[k].value = z[k] ?? ''; });
        window.scrollTo({top:0,behavior:'smooth'});
    });
});

document.querySelectorAll('.zelegal-phone').forEach(i=>{
    i.addEventListener('input',()=>{
        let v=i.value.replace(/\D/g,'').slice(0,11);
        if(v.length>=2)v='('+v.slice(0,2)+') '+v.slice(2);
        if(v.length>=10)v=v.slice(0,10)+'-'+v.slice(10);
        i.value=v;
    });
});
</script>