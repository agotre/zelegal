
<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Segurança: Verifica permissão
if ( ! current_user_can( 'ze_cadastro_adm' ) ) {
    wp_die( 'Acesso não autorizado.' );
}

global $wpdb;
$table_enums = $wpdb->prefix . 'ze_tb_enums';
$slug_page = 'ze-legal-enumes'; 

/**
 * DEFINIÇÃO OFICIAL - Valores reais da tabela
 */
$ze_enum_targets = [
    'tb_colaboradores' => [
        'ds_camiseta'         => 'ds_camiseta',
        'ds_tipo_colaborador' => 'ds_tipo_colaborador',
        'ds_status_eleitoral' => 'ds_status_eleitoral',
    ],
];

/**
 * PROCESSA INSERT / UPDATE
 */
if ( isset( $_POST['ze_enum_submit'] ) ) {
    $data = [
        'ds_enum'          => sanitize_text_field( $_POST['ds_enum'] ),
        'tb_alvo_enum'     => sanitize_text_field( $_POST['tb_alvo_enum'] ),
        'campo_alvo_enum'  => sanitize_text_field( $_POST['campo_alvo_enum'] ),
        'num_orden_enum'   => intval( $_POST['num_orden_enum'] ),
        'status_enum'      => intval( $_POST['status_enum'] ),
    ];

    if ( empty( $_POST['id_enum'] ) ) {
        $wpdb->insert( $table_enums, $data );
        echo '<div class="notice notice-success"><p>Cadastrado com sucesso!</p></div>';
    } else {
        $wpdb->update( $table_enums, $data, [ 'id_enum' => intval( $_POST['id_enum'] ) ] );
        echo '<div class="notice notice-success"><p>Atualizado com sucesso!</p></div>';
    }
}

/**
 * PROCESSA DELETE
 */
if ( isset( $_GET['delete'] ) ) {
    $wpdb->delete( $table_enums, [ 'id_enum' => intval( $_GET['delete'] ) ] );
    echo '<div class="notice notice-warning"><p>Removido.</p></div>';
}

$enum_edit = null;
if ( isset( $_GET['edit'] ) ) {
    $enum_edit = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_enums} WHERE id_enum = %d", intval( $_GET['edit'] ) ) );
}

$enums = $wpdb->get_results( "SELECT * FROM {$table_enums} ORDER BY tb_alvo_enum, campo_alvo_enum, num_orden_enum" );
?>

<div class="ze-admin-container">
    <a href="<?php echo admin_url( 'admin.php?page=ze-legal-dashboard' ); ?>" class="ze-btn-back">
        <span class="dashicons dashicons-arrow-left-alt"></span> Voltar para o Dashboard
    </a>

    <h1 class="ze-page-title">Cadastro de Enums</h1>

    <div class="ze-card">
        <form method="post" action="admin.php?page=<?php echo $slug_page; ?>">
            <input type="hidden" name="id_enum" value="<?php echo esc_attr( $enum_edit->id_enum ?? '' ); ?>">

            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Tabela Alvo</label>
                    <select name="tb_alvo_enum" id="tb_alvo_enum" required>
                        <option value="">Selecione...</option>
                        <?php foreach ( $ze_enum_targets as $table => $fields ) : ?>
                            <option value="<?php echo esc_attr( $table ); ?>" <?php selected( $enum_edit->tb_alvo_enum ?? '', $table ); ?>>
                                <?php echo esc_html( $table ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ze-form-group">
                    <label>Campo Alvo</label>
                    <select name="campo_alvo_enum" id="campo_alvo_enum" required>
                        <option value="">Selecione a tabela primeiro</option>
                    </select>
                </div>
            </div>
            <div class="ze-form-grid">
                <div class="ze-form-group">
                    <label>Descrição</label>
                    <input type="text" name="ds_enum" required value="<?php echo esc_attr( $enum_edit->ds_enum ?? '' ); ?>">
                </div>

                <div class="ze-form-group">
                    <label>Ordem de Exibição</label>
                    <input type="number" name="num_orden_enum" value="<?php echo esc_attr( $enum_edit->num_orden_enum ?? 0 ); ?>">
                </div>

                <div class="ze-form-group">
                    <label>Status</label>
                    <select name="status_enum">
                        <option value="1" <?php selected( $enum_edit->status_enum ?? 1, 1 ); ?>>Ativo</option>
                        <option value="0" <?php selected( $enum_edit->status_enum ?? 1, 0 ); ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" name="ze_enum_submit" class="ze-btn-submit">
                    <?php echo $enum_edit ? 'Salvar Alterações' : 'Finalizar Cadastro'; ?>
                </button>
                <?php if ( $enum_edit ): ?>
                    <a href="admin.php?page=<?php echo $slug_page; ?>" class="button" style="margin-left:10px;">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <h2 class="ze-section-title">Enums Configurados</h2>
    
    <div class="ze-card no-padding">
        <table class="ze-table" id="tabela-enums-list">
            <thead>
                <tr>
                    <th>Tabela / Campo</th>
                    <th>Descrição</th>
                    <th>Ordem</th>
                    <th>Status</th>
                    <th style="text-align:right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $enums ) : foreach ( $enums as $e ) : ?>
                    <tr class="enum-row" 
                        data-tabela="<?php echo esc_attr($e->tb_alvo_enum); ?>" 
                        data-campo="<?php echo esc_attr($e->campo_alvo_enum); ?>">
                        <td>
                            <small><?php echo esc_html( $e->tb_alvo_enum ); ?></small><br>
                            <strong><?php echo esc_html( $e->campo_alvo_enum ); ?></strong>
                        </td>
                        <td><?php echo esc_html( $e->ds_enum ); ?></td>
                        <td><?php echo $e->num_orden_enum; ?></td>
                        <td><?php echo $e->status_enum ? 'Ativo' : 'Inativo'; ?></td>
                        <td style="text-align:right;">
                            <a href="admin.php?page=<?php echo $slug_page; ?>&edit=<?php echo $e->id_enum; ?>">Editar</a> | 
                            <a href="admin.php?page=<?php echo $slug_page; ?>&delete=<?php echo $e->id_enum; ?>" style="color:red;" onclick="return confirm('Excluir?')">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5">Nenhum registro.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const enumTargets = <?php echo json_encode( $ze_enum_targets ); ?>;
    const tableSelect = document.getElementById('tb_alvo_enum');
    const fieldSelect = document.getElementById('campo_alvo_enum');
    const rows = document.querySelectorAll('.enum-row');
    const selectedField = "<?php echo esc_js( $enum_edit->campo_alvo_enum ?? '' ); ?>";

    function filtrarTabela() {
        const valTabela = tableSelect.value;
        const valCampo = fieldSelect.value;

        rows.forEach(row => {
            const rowTabela = row.getAttribute('data-tabela');
            const rowCampo = row.getAttribute('data-campo');

            const matchTabela = !valTabela || rowTabela === valTabela;
            const matchCampo = !valCampo || rowCampo === valCampo;

            row.style.display = (matchTabela && matchCampo) ? '' : 'none';
        });
    }

    function loadFields(table, initial = false) {
        fieldSelect.innerHTML = '<option value="">Todos os campos...</option>';
        if (enumTargets[table]) {
            Object.keys(enumTargets[table]).forEach(field => {
                const opt = document.createElement('option');
                opt.value = field;
                // Exibe o nome real (valor do array) no lugar da tradução
                opt.textContent = enumTargets[table][field]; 
                if (initial && field === selectedField) opt.selected = true;
                fieldSelect.appendChild(opt);
            });
        }
        filtrarTabela();
    }

    tableSelect.addEventListener('change', function() {
        loadFields(this.value);
    });

    fieldSelect.addEventListener('change', function() {
        filtrarTabela();
    });

    if (tableSelect.value) {
        loadFields(tableSelect.value, true);
    }
});
</script>