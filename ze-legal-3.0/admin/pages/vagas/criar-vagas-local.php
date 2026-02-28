<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão (ajuste para ze_cadastro_adm se necessário)
if ( ! current_user_can( 'ze_cadastro_adm_zona' ) ) {
    wp_die( 'Acesso não autorizado.' );
}


global $wpdb;
$prefix = $wpdb->prefix . 'ze_';

$tbl_pleitos = "{$prefix}tb_pleitos";
$tbl_zonas   = "{$prefix}tb_zonas";
$tbl_locais  = "{$prefix}tb_locais";
$tbl_secoes  = "{$prefix}tb_secoes";
$tbl_funcoes = "{$prefix}tb_funcoes";
$tbl_vagas   = "{$prefix}tb_vagas_pleitos";

/* Pleito ativo */
$pleito_ativo = $wpdb->get_row( "SELECT * FROM {$tbl_pleitos} WHERE status_pleito = 1 ORDER BY ano DESC, id_pleito DESC LIMIT 1" );

$id_local = isset($_REQUEST['id_local']) ? intval($_REQUEST['id_local']) : 0;
$id_zona  = isset($_REQUEST['id_zona']) ? intval($_REQUEST['id_zona']) : 0;

$mensagem = '';
$errors = [];

/* Buscar local e zona */
$local = null;
if ( $id_local ) {
    $local = $wpdb->get_row( $wpdb->prepare(
        "SELECT l.*, z.num_zona, z.descricao as zona_desc FROM {$tbl_locais} l JOIN {$tbl_zonas} z ON z.id_zona = l.id_zona WHERE l.id_local = %d",
        $id_local
    ) );
}
if ( ! $local ) {
    echo '<div class="notice notice-warning"><p>Local não encontrado. Volte para a visão geral e escolha um local.</p></div>';
    return;
}

/* Carregar seções do local (originais) — usadas para gerar MRV */
$secoes = $wpdb->get_results( $wpdb->prepare("SELECT id_secao, num_secao FROM {$tbl_secoes} WHERE id_local = %d ORDER BY num_secao ASC", $id_local) );
$num_secoes = count($secoes);

/* Funções ativas para select e cards */
$funcoes = $wpdb->get_results( "SELECT * FROM {$tbl_funcoes} WHERE status_funcao = 1 ORDER BY nom_funcao" );

/* Valores do formulário (preservados após POST) */
$num_secao_input = isset($_POST['num_secao']) ? sanitize_text_field($_POST['num_secao']) : ( isset($_GET['prefill_num_secao']) ? sanitize_text_field($_GET['prefill_num_secao']) : '' );
$vaga_seq_input  = isset($_POST['vaga_seq']) ? intval($_POST['vaga_seq']) : ( isset($_GET['prefill_vaga_seq']) ? intval($_GET['prefill_vaga_seq']) : 1 );
$id_funcao_input = isset($_POST['id_funcao']) ? intval($_POST['id_funcao']) : ( isset($_GET['prefill_id_funcao']) ? intval($_GET['prefill_id_funcao']) : 0 );
$tp_mrv_input    = isset($_POST['tp_secao_mrv']) ? 1 : ( isset($_GET['prefill_tp_mrv']) ? intval($_GET['prefill_tp_mrv']) : 1 );

/* ------------------------------------------------------------------
   HANDLERS
   ------------------------------------------------------------------ */

/* 1) Gerar MRV automático (todas as seções do local) */
if ( isset($_POST['ze_gerar_mrv_local_nonce']) && wp_verify_nonce($_POST['ze_gerar_mrv_local_nonce'],'ze_gerar_mrv_local') ) {
    if ( ! $pleito_ativo ) {
        $errors[] = 'Não há pleito ativo.';
    } else {
        $mrv_funcs = $wpdb->get_results( "SELECT * FROM {$tbl_funcoes} WHERE status_funcao = 1 AND (num_funcao='1' OR num_funcao='2' OR num_funcao='3' OR num_funcao='4') ORDER BY CAST(num_funcao AS UNSIGNED)" );
        if ( empty($mrv_funcs) ) {
            $errors[] = 'Nenhuma função MRV ativa (num_funcao 1..4).';
        } else {
            $inserts = 0;
            foreach ( $secoes as $s ) {
                foreach ( $mrv_funcs as $f ) {
                    $vaga_seq = intval($f->num_funcao);
                    // checar existência por pleito + local + num_secao + id_funcao + vaga_seq
                    $exists = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$tbl_vagas} WHERE id_pleito=%d AND id_local=%d AND num_secao=%s AND id_funcao=%d AND vaga_seq=%d",
                        $pleito_ativo->id_pleito, $id_local, $s->num_secao, $f->id_funcao, $vaga_seq
                    ) );
                    if ( intval($exists) === 0 ) {
                        $dados = [
                            'id_pleito' => $pleito_ativo->id_pleito,
                            'id_local' => $id_local,
                            'num_secao' => $s->num_secao,
                            'vaga_seq' => $vaga_seq,
                            'tp_secao_mrv' => 1,
                            'id_funcao' => $f->id_funcao,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                        ];
                        $wpdb->insert($tbl_vagas,$dados);
                        $inserts++;
                    }
                }
            }
            $mensagem = $inserts>0 ? "Vagas MRV geradas: {$inserts}" : "Nenhuma vaga MRV nova (já existiam).";
        }
    }
}

/* 2) Limpar vagas vinculadas às seções físicas deste local (apaga todas as vagas do local/pleito) */
if ( isset($_POST['ze_limpar_local_nonce']) && wp_verify_nonce($_POST['ze_limpar_local_nonce'],'ze_limpar_local') ) {
    if ( ! $pleito_ativo ) {
        $errors[] = 'Não há pleito ativo.';
    } else {
        $wpdb->query( $wpdb->prepare("DELETE FROM {$tbl_vagas} WHERE id_pleito = %d AND id_local = %d", $pleito_ativo->id_pleito, $id_local) );
        $mensagem = 'Vagas vinculadas ao local removidas.';
    }
}

/* 3) Limpar apenas MRV do local (apaga tp_secao_mrv = 1 com id_local = local) */
if ( isset($_POST['ze_limpar_local_mrv_nonce']) && wp_verify_nonce($_POST['ze_limpar_local_mrv_nonce'],'ze_limpar_local_mrv') ) {
    if ( ! $pleito_ativo ) {
        $errors[] = 'Não há pleito ativo.';
    } else {
        $wpdb->query( $wpdb->prepare("DELETE FROM {$tbl_vagas} WHERE id_pleito = %d AND id_local = %d AND tp_secao_mrv = 1", $pleito_ativo->id_pleito, $id_local) );
        $mensagem = 'Vagas MRV do local removidas.';
    }
}

/* 3b) Bulk delete (seleção múltipla) - só remove vagas com status = 'DISPONIVEL' */
if ( isset($_POST['ze_bulk_delete_nonce']) && wp_verify_nonce($_POST['ze_bulk_delete_nonce'],'ze_bulk_delete') ) {
    $bulk_ids = isset($_POST['bulk_ids']) && is_array($_POST['bulk_ids']) ? array_map('intval',$_POST['bulk_ids']) : [];
    if ( empty($bulk_ids) ) {
        $errors[] = 'Nenhuma vaga selecionada para exclusão.';
    } else {
        $bulk_ids = array_filter($bulk_ids, function($v){ return $v > 0; });
        if ( empty($bulk_ids) ) {
            $errors[] = 'IDs inválidos na seleção.';
        } else {
            $in = implode(',', $bulk_ids);
            // buscar apenas os IDs que pertencem ao local/pleito e estão com status 'DISPONIVEL'
            $allowed = $wpdb->get_col( $wpdb->prepare(
                "SELECT id_vaga_pleito FROM {$tbl_vagas} WHERE id_vaga_pleito IN ({$in}) AND id_local = %d AND id_pleito = %d AND status_vaga = %s",
                $id_local, $pleito_ativo->id_pleito, 'DISPONIVEL'
            ) );

            if ( empty($allowed) ) {
                $errors[] = 'Nenhuma das vagas selecionadas pode ser removida (somente vagas com status "DISPONIVEL" são removíveis).';
            } else {
                $in_allowed = implode(',', array_map('intval', $allowed));
                $wpdb->query( "DELETE FROM {$tbl_vagas} WHERE id_vaga_pleito IN ({$in_allowed})" );
                $mensagem = count($allowed) . ' vaga(s) removida(s).';
                $skipped = count($bulk_ids) - count($allowed);
                if ( $skipped > 0 ) {
                    $mensagem .= " {$skipped} não foram removidas por terem status diferente de 'DISPONIVEL'.";
                }
            }
        }
    }
}

/* 4) Criar vaga individual (permite múltiplas vagas idênticas; incrementa seq no formulário) */
if ( isset($_POST['ze_criar_vaga_local_nonce']) && wp_verify_nonce($_POST['ze_criar_vaga_local_nonce'],'ze_criar_vaga_local') ) {
    if ( ! $pleito_ativo ) {
        $errors[] = 'Não há pleito ativo.';
    } else {
        $num_secao = substr( preg_replace('/[^0-9A-Za-z]/','', sanitize_text_field($_POST['num_secao']) ), 0, 4 );
        $num_secao = str_pad( $num_secao, 4, '0', STR_PAD_LEFT );
        $vaga_seq = isset($_POST['vaga_seq']) ? intval($_POST['vaga_seq']) : 1;
        $id_funcao = isset($_POST['id_funcao']) && intval($_POST['id_funcao'])>0 ? intval($_POST['id_funcao']) : null;
        $tp_mrv = isset($_POST['tp_secao_mrv']) ? 1 : 0;

        // manter valores no form
        $num_secao_input = $num_secao;
        $vaga_seq_input = $vaga_seq;
        $id_funcao_input = $id_funcao ?: 0;
        $tp_mrv_input = $tp_mrv;

        // Inserção direta
        $dados = [
            'id_pleito' => $pleito_ativo->id_pleito,
            'id_local' => $id_local,
            'num_secao' => $num_secao,
            'vaga_seq' => $vaga_seq,
            'tp_secao_mrv' => $tp_mrv,
            'id_funcao' => $id_funcao ?: null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $ok = $wpdb->insert( $tbl_vagas, $dados );
        if ( $ok === false ) {
            $errors[] = 'Erro ao criar vaga (tente novamente).';
        } else {
            $mensagem = 'Vaga criada com sucesso.';
            // incrementar sequencia para criação em série
            $vaga_seq_input = $vaga_seq_input + 1;
        }
    }
}

/* 5) Exclusão em massa via formulário de duplicação removida (não usamos duplicar) */

/* 6) Exclusão de vaga via GET (com nonce) - valida status_vaga antes de excluir */
if ( isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id_vaga']) && isset($_GET['_wpnonce']) ) {
    $idv = intval($_GET['id_vaga']);
    if ( ! wp_verify_nonce($_GET['_wpnonce'],'ze_excluir_vaga') ) {
        $errors[] = 'Nonce inválido para exclusão.';
    } else {
        $row = $wpdb->get_row( $wpdb->prepare("SELECT id_vaga_pleito, id_local, id_pleito, status_vaga FROM {$tbl_vagas} WHERE id_vaga_pleito = %d", $idv) );
        if ( ! $row ) {
            $errors[] = 'Vaga não encontrada.';
        } else if ( intval($row->id_local) !== intval($id_local) || intval($row->id_pleito) !== intval($pleito_ativo->id_pleito) ) {
            $errors[] = 'A vaga não pertence a este local/pleito.';
        } else if ( strtoupper(trim($row->status_vaga)) !== 'DISPONIVEL' ) {
            $errors[] = 'Somente vagas com status "DISPONIVEL" podem ser removidas.';
        } else {
            $wpdb->delete( $tbl_vagas, ['id_vaga_pleito' => $idv], ['%d'] );
            $mensagem = 'Vaga excluída.';
        }
    }
}

/* ------------------------------------------------------------------
   CONSULTAS / LISTAGENS
   ------------------------------------------------------------------ */

/* Listagem de vagas deste local */
$vagas = [];
if ( $pleito_ativo ) {
    $vagas = $wpdb->get_results( $wpdb->prepare("
        SELECT v.*, f.num_funcao, f.nom_funcao
        FROM {$tbl_vagas} v
        LEFT JOIN {$tbl_funcoes} f ON f.id_funcao = v.id_funcao
        WHERE v.id_pleito = %d AND v.id_local = %d
        ORDER BY v.num_secao ASC, v.vaga_seq ASC
    ", $pleito_ativo->id_pleito, $id_local) );
}

/* Totais por função (apenas para este local) */
$totais_funcoes_local = [];
if ( $pleito_ativo ) {
    $sql = "
        SELECT f.id_funcao, f.num_funcao, f.nom_funcao, 
               SUM(CASE WHEN v.tp_secao_mrv = 1 THEN 1 ELSE 0 END) AS mrv,
               SUM(CASE WHEN v.tp_secao_mrv = 0 THEN 1 ELSE 0 END) AS comum,
               COUNT(v.id_vaga_pleito) AS qtd
        FROM {$tbl_funcoes} f
        LEFT JOIN {$tbl_vagas} v ON v.id_funcao = f.id_funcao AND v.id_pleito = %d AND v.id_local = %d
        GROUP BY f.id_funcao, f.num_funcao, f.nom_funcao
        ORDER BY CAST(f.num_funcao AS UNSIGNED)
    ";
    $totais_funcoes_local = $wpdb->get_results( $wpdb->prepare( $sql, $pleito_ativo->id_pleito, $id_local ) );
}

/* ------------------------------------------------------------------
   RENDER HTML
   ------------------------------------------------------------------ */
?>
<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-criar-vagas' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para para criar vagas
    </a>

    <h1 class="ze-page-title">Criar Vagas Pleitos - Local </h1>
    
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <?php if($pleito_ativo): ?>
            <div style="background: #f1f5f9; padding: 8px 15px; border-radius: 8px; font-weight: 700; color: #475569;">
                <span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html($pleito_ativo->descricao.' — '.$pleito_ativo->ano); ?>
            </div>
        <?php endif; ?>
    </div>
    
    
    <?php if(!empty($mensagem)): ?><div class="notice notice-success"><p><?php echo esc_html($mensagem); ?></p></div><?php endif; ?>
    <?php if(!empty($errors)): foreach($errors as $e){ echo '<div class="notice notice-error"><p>'.esc_html($e).'</p></div>'; } endif; ?>

    <!-- Cabeçalho com cards -->
    <div style="display:flex;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
        <div style="flex:0 0 220px;padding:12px;border-radius:8px;background:linear-gradient(180deg,#eef2ff,#ffffff);border:1px solid #e6eef8;">
            <div style="font-size:12px;color:#0f172a;font-weight:700;">Zona</div>
            <div style="font-size:16px;font-weight:800;"><?php echo esc_html($local->zona_desc); ?></div>
        </div>

        <div style="flex:1;padding:12px;border-radius:8px;background:linear-gradient(180deg,#fff7ed,#fff);border:1px solid #fce6c6;">
            <div style="font-size:12px;color:#111827;font-weight:700;">Local</div>
            <div style="font-size:16px;font-weight:800;"><?php echo esc_html($local->num_local . ' — ' . $local->nom_local . ' — ' . $local->endereco); ?></div>
        </div>

        <div style="flex:0 0 180px;padding:12px;border-radius:8px;background:linear-gradient(180deg,#ecfdf5,#ffffff);border:1px solid #d1fae5;">
            <div style="font-size:12px;color:#065f46;font-weight:700;">Seções físicas</div>
            <div style="font-size:22px;color:#065f46;font-weight:800;"><?php echo intval($num_secoes); ?></div>
            <div style="font-size:12px;color:#6b7280;">(existentes no cadastro)</div>
        </div>
    </div>

    <!-- Cards: vagas por função (no local) -->
    <?php
    // Bloco de cards: exibir apenas funções com vagas; mostrar qtd total e badges MRV/Comum (sem números detalhados)
    if ( empty($totais_funcoes_local) ) : ?>
        <div style="padding:12px;border-radius:8px;background:#fff;border:1px solid #eef2ff;">
            Nenhuma vaga cadastrada por função ainda.
        </div>
    <?php else: ?>
        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
        <?php foreach ( $totais_funcoes_local as $tf ) :
            if ( intval($tf->qtd) === 0 ) continue;
            $has_mrv   = intval($tf->mrv)   > 0;
            $has_comum = intval($tf->comum) > 0;

            if ( $has_mrv && ! $has_comum ) {
                $card_bg = "background:linear-gradient(180deg,#ecfdf5,#d1fae5);border:1px solid #bbf7d0;";
                $title_color = "#065f46";
            } elseif ( $has_comum && ! $has_mrv ) {
                $card_bg = "background:linear-gradient(180deg,#fff7ed,#ffedd5);border:1px solid #fed7aa;";
                $title_color = "#92400e";
            } else {
                $card_bg = "background:linear-gradient(180deg,#f0f9ff,#fff7ed);border:1px solid #e6eef8;";
                $title_color = "#111827";
            }
        ?>
            <div style="flex:0 0 200px;padding:14px;border-radius:10px;<?php echo $card_bg; ?>box-shadow:0 4px 10px rgba(0,0,0,0.05);">
                <div style="font-size:12px;color:<?php echo $title_color; ?>;font-weight:600;margin-bottom:4px;">Função</div>
                <div style="font-size:15px;font-weight:800;color:<?php echo $title_color; ?>;"><?php echo esc_html($tf->num_funcao . ' — ' . $tf->nom_funcao); ?></div>

                <div style="margin-top:6px;font-size:26px;font-weight:800;color:#1f2937;"><?php echo intval($tf->qtd); ?></div>
                <div style="font-size:12px;color:#6b7280;margin-top:-4px;">vagas criadas</div>

                <div style="display:flex;gap:8px;margin-top:10px;align-items:center;">
                    <?php if ( $has_mrv ) : ?>
                        <div style="padding:6px 10px;border-radius:999px;background:#ecfdf5;border:1px solid #bbf7d0;font-weight:700;color:#065f46;font-size:12px;">MRV</div>
                    <?php endif; ?>
                    <?php if ( $has_comum ) : ?>
                        <div style="padding:6px 10px;border-radius:999px;background:#fff7ed;border:1px solid #fed7aa;font-weight:700;color:#92400e;font-size:12px;">Comum</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Ações principais -->
    <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
        <form method="post" onsubmit="return confirm('Gerar vagas MRV para todas as seções deste local?');" style="margin:0;">
            <?php wp_nonce_field('ze_gerar_mrv_local','ze_gerar_mrv_local_nonce'); ?>
            <button class="button button-primary" type="submit">Gerar MRV automáticas (todas as seções)</button>
        </form>

        <form method="post" onsubmit="return confirm('Remover vagas vinculadas ao local?');" style="margin:0;">
            <?php wp_nonce_field('ze_limpar_local','ze_limpar_local_nonce'); ?>
            <button class="button" type="submit">Limpar vagas vinculadas ao local</button>
        </form>

        <form method="post" onsubmit="return confirm('Remover apenas vagas MRV deste local?');" style="margin:0;">
            <?php wp_nonce_field('ze_limpar_local_mrv','ze_limpar_local_mrv_nonce'); ?>
            <button class="button" type="submit">Limpar apenas MRV</button>
        </form>

        <a class="button" href="<?php echo esc_url( add_query_arg(['page'=>'ze-legal-criar-vagas','id_zona'=>$id_zona], admin_url('admin.php') ) ); ?>">Voltar à visão geral</a>
    </div>

    <!-- Formulário -> Criar vaga individual -->
    <div style="padding:12px;border-radius:8px;border:1px solid #eef2ff;background:#fff;margin-bottom:12px;">
        <h2 style="margin-top:0;">Criar vaga individual</h2>
        <form method="post">
            <?php wp_nonce_field('ze_criar_vaga_local','ze_criar_vaga_local_nonce'); ?>
            <input type="hidden" name="id_local" value="<?php echo intval($id_local); ?>">
            <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <div>
                    <label style="display:block;font-weight:600;font-size:12px;margin-bottom:6px;">Seção (manual)</label>
                    <input type="text" name="num_secao" maxlength="4" placeholder="0001" style="width:100px;text-align:center;font-weight:700;" value="<?php echo esc_attr($num_secao_input); ?>">
                </div>
                <div>
                    <label style="display:block;font-weight:600;font-size:12px;margin-bottom:6px;">Seq</label>
                    <input type="number" name="vaga_seq" min="1" value="<?php echo esc_attr($vaga_seq_input); ?>" style="width:80px;">
                </div>
                <div>
                    <label style="display:block;font-weight:600;font-size:12px;margin-bottom:6px;">Função</label>
                    <select name="id_funcao" style="min-width:200px;">
                        <option value="">(sem função)</option>
                        <?php foreach($funcoes as $f): ?>
                            <option value="<?php echo intval($f->id_funcao); ?>" <?php selected($id_funcao_input, $f->id_funcao); ?>><?php echo esc_html($f->nom_funcao); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display:block;">&nbsp;</label>
                    <label><input type="checkbox" name="tp_secao_mrv" <?php echo intval($tp_mrv_input) ? 'checked' : ''; ?>> MRV</label>
                </div>
                <div>
                    <button class="button button-primary" type="submit">Criar vaga</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk controls + Tabela de vagas do local -->
    <form method="post" id="ze_bulk_form">
        <?php wp_nonce_field('ze_bulk_delete','ze_bulk_delete_nonce'); ?>
        <div style="margin-bottom:10px;">
            <button class="button" type="submit" onclick="return confirm('Excluir vagas selecionadas?');">Excluir selecionadas</button>
        </div>

        <div style="padding:6px;border-radius:8px;border:1px solid #eef2ff;background:#fff;">
            <h2 style="margin-top:0;display:flex;justify-content:space-between;align-items:center;">
                <span>Vagas deste local</span>
                <div style="font-size:12px;color:#6b7280;">Linhas MRV destacadas em verde — somente vagas com status "DISPONIVEL" podem ser excluídas</div>
            </h2>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="ze_check_all"></th>
                        <th>Seção</th>
                        <th>Seq</th>
                        <th>Função</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th style="width:140px">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty($vagas) ) : ?>
                        <tr><td colspan="7">Nenhuma vaga cadastrada para este local.</td></tr>
                    <?php else: ?>
                        <?php foreach ( $vagas as $v ):
                            $is_mrv = intval($v->tp_secao_mrv) === 1;
                            $row_style = $is_mrv ? 'background:#f0fffa;' : '';
                            $status = isset($v->status_vaga) ? trim(strval($v->status_vaga)) : '';
                        ?>
                            <tr style="<?php echo $row_style; ?>">
                                <td><input type="checkbox" name="bulk_ids[]" value="<?php echo intval($v->id_vaga_pleito); ?>"></td>
                                <td><?php echo esc_html( $v->num_secao ); ?></td>
                                <td><?php echo intval($v->vaga_seq); ?></td>
                                <td><strong><?php echo $v->num_funcao ? esc_html($v->num_funcao . ' — ' . $v->nom_funcao) : '-'; ?></strong></td>
                                <td><?php echo $is_mrv ? '<span style="color:#065f46;font-weight:700;">MRV</span>' : '<span style="color:#7c2d12;font-weight:700;">Comum</span>'; ?></td>
                                <td><?php echo esc_html($status); ?></td>
                                <td>
                                    <?php
                                    // AÇÕES POR LINHA: somente Excluir quando status = 'DISPONIVEL'
                                    if ( strtoupper($status) === 'DISPONIVEL' ) {
                                        $url_del = add_query_arg([
                                            'page' => 'ze-legal-criar-vagas-local',
                                            'action' => 'excluir',
                                            'id_vaga' => intval($v->id_vaga_pleito),
                                            '_wpnonce' => wp_create_nonce('ze_excluir_vaga'),
                                            'id_local' => $id_local,
                                            'id_zona' => $id_zona
                                        ], admin_url('admin.php') );
                                        ?>
                                        <a href="<?php echo esc_url($url_del); ?>" onclick="return confirm('Excluir vaga?');" class="button button-secondary">Excluir</a>
                                    <?php
                                    } else {
                                        // label explicativo
                                        ?>
                                        <span style="display:inline-block;padding:6px 10px;border-radius:4px;background:#f3f4f6;color:#6b7280;font-size:13px;">
                                            Não pode excluir (<?php echo esc_html($status); ?>)
                                        </span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

</div>

<script>
(function(){
    const checkAll = document.getElementById('ze_check_all');
    if ( checkAll ) {
        checkAll.addEventListener('change', function(){
            const boxes = document.querySelectorAll('input[name="bulk_ids[]"]');
            boxes.forEach(b => b.checked = this.checked);
        });
    }
})();
</script>
