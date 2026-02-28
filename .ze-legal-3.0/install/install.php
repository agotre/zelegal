<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$charset_collate = $wpdb->get_charset_collate();


/*
|--------------------------------------------------------------------------
| 01. TABELA: ze_tb_enums
|--------------------------------------------------------------------------
*/
$table_enums = $wpdb->prefix . 'ze_tb_enums';
$sql = "CREATE TABLE {$table_enums} (
    id_enum INT NOT NULL AUTO_INCREMENT,
    ds_enum VARCHAR(255) NOT NULL,
    tb_alvo_enum VARCHAR(100) NOT NULL,
    campo_alvo_enum VARCHAR(100) NOT NULL,
    num_orden_enum INT NOT NULL DEFAULT 0,
    status_enum TINYINT NOT NULL DEFAULT 1,
    PRIMARY KEY (id_enum)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 02. TABELA: ze_tb_colaboradores
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_colaboradores';
$sql = "CREATE TABLE {$table} (
  id_colaborador int(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  num_cpf char(11) NOT NULL,
  nom_eleitor varchar(255) DEFAULT NULL,
  id_upload_foto bigint(20) UNSIGNED DEFAULT NULL,
  num_inscricao varchar(12) DEFAULT NULL,
  num_zona_votacao varchar(4) DEFAULT NULL,
  num_secao_votacao varchar(4) DEFAULT NULL,
  num_local_votacao varchar(10) DEFAULT NULL,
  num_telefone_eleitor varchar(20) DEFAULT NULL,
  num_telefone_eleitor_2 varchar(20) DEFAULT NULL,
  nom_municipio_votacao varchar(60) DEFAULT NULL,
  email_colaborador varchar(255) DEFAULT NULL,
  ds_experiencia text DEFAULT NULL,
  ds_camiseta varchar(4) DEFAULT NULL,
  ds_tipo_colaborador varchar(20) DEFAULT 'CONVENCIONAL',
  ds_status_eleitoral varchar(20) DEFAULT 'DISPONIVEL',
  status_observacao text DEFAULT NULL,
  endereco_atualizado varchar(130) DEFAULT NULL,
  compartilhar_contato tinyint(1) DEFAULT 0,
  compartilhar_contato_em datetime DEFAULT NULL,
  id_usuario_compartilhar bigint(20) UNSIGNED DEFAULT NULL,
  id_user bigint(20) UNSIGNED DEFAULT NULL,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_colaborador),
  UNIQUE KEY uniq_num_cpf (num_cpf),
  KEY idx_nom_eleitor (nom_eleitor),
  KEY idx_id_user (id_user),
  KEY idx_num_inscricao (num_inscricao),
  KEY idx_status_eleitoral (ds_status_eleitoral),
  KEY idx_tipo_colaborador (ds_tipo_colaborador)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 03. TABELA: ze_tb_checkin_local
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_checkin_local';
$sql = "CREATE TABLE {$table} (
  id_checkin int(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pleito bigint(20) UNSIGNED NOT NULL,
  id_local_origem bigint(20) UNSIGNED NOT NULL,
  id_local_destino bigint(20) UNSIGNED NOT NULL,
  id_colaborador bigint(20) UNSIGNED NOT NULL,
  data_hora_checkin datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_checkin),
  KEY idx_id_pleito (id_pleito),
  KEY idx_id_local_origem (id_local_origem),
  KEY idx_id_local_destino (id_local_destino),
  KEY idx_id_colaborador (id_colaborador),
  KEY idx_data_hora_checkin (data_hora_checkin)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 04. TABELA: ze_tmp_import_colaboradores
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tmp_import_colaboradores';
$sql = "CREATE TABLE {$table} (
  id_tmp int(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  num_cpf char(11) DEFAULT NULL,
  nom_eleitor varchar(255) DEFAULT NULL,
  num_inscricao varchar(20) DEFAULT NULL,
  num_zona_votacao varchar(4) DEFAULT NULL,
  num_secao_votacao varchar(4) DEFAULT NULL,
  num_local_votacao varchar(10) DEFAULT NULL,
  nom_municipio_votacao varchar(60) DEFAULT NULL,
  num_telefone_eleitor varchar(20) DEFAULT NULL,
  num_telefone_eleitor_2 varchar(20) DEFAULT NULL,
  ds_experiencia text DEFAULT NULL,
  linha_origem int(11) DEFAULT NULL,
  data_importacao datetime DEFAULT NULL,
  PRIMARY KEY (id_tmp),
  KEY idx_num_cpf (num_cpf),
  KEY idx_num_inscricao (num_inscricao),
  KEY idx_linha_origem (linha_origem)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 05. TABELA: ze_tb_pleitos
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_pleitos';
$sql = "CREATE TABLE {$table} (
  id_pleito bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  ano int(11) NOT NULL,
  descricao varchar(255) DEFAULT NULL,
  dt_1turno date DEFAULT NULL,
  dt_2turno date DEFAULT NULL,
  status_pleito tinyint(1) DEFAULT 0,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_pleito),
  UNIQUE KEY uniq_ano (ano),
  KEY idx_status_pleito (status_pleito)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 06. TABELA: ze_tb_funcoes
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_funcoes';
$sql = "CREATE TABLE {$table} (
  id_funcao bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  num_funcao varchar(16) NOT NULL,
  nom_funcao varchar(255) NOT NULL,
  status_funcao tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_funcao),
  UNIQUE KEY uniq_num_funcao (num_funcao),
  KEY idx_status_funcao (status_funcao)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 07. TABELA: ze_tb_municipios
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_municipios';
$sql = "CREATE TABLE {$table} (
  id_municipio bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo_ibge varchar(16) DEFAULT NULL,
  nom_municipio varchar(255) DEFAULT NULL,
  nom_municipio_elo varchar(60) DEFAULT NULL,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_municipio),
  UNIQUE KEY uniq_codigo_ibge (codigo_ibge)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 08. TABELA: ze_tb_zonas
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_zonas';
$sql = "CREATE TABLE {$table} (
  id_zona bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  num_zona varchar(4) DEFAULT NULL,
  descricao varchar(255) DEFAULT NULL,
  endereco text DEFAULT NULL,
  contato_1 varchar(50) DEFAULT NULL,
  contato_2 varchar(50) DEFAULT NULL,
  contato_3 varchar(50) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  chefe_cartorio varchar(255) DEFAULT NULL,
  juiz varchar(255) DEFAULT NULL,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_zona),
  UNIQUE KEY uniq_num_zona (num_zona)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 09. TABELA: ze_tb_tipos_locais
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_tipos_locais';
$sql = "CREATE TABLE {$table} (
  id_tipo_local bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  ds_tipo_local varchar(255) DEFAULT NULL,
  ativo tinyint(1) DEFAULT 1,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_tipo_local),
  UNIQUE KEY uniq_ds_tipo_local (ds_tipo_local)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 10. TABELA: ze_tb_tipos_eventos
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_tipos_eventos';
$sql = "CREATE TABLE {$table} (
  id_tipo_evento bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  ds_tipo_evento varchar(255) NOT NULL,
  status tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_tipo_evento),
  UNIQUE KEY uniq_ds_tipo_evento (ds_tipo_evento)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 11. TABELA: ze_tb_locais
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_locais';
$sql = "CREATE TABLE {$table} (
  id_local bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_municipio bigint(20) UNSIGNED NOT NULL,
  id_zona bigint(20) UNSIGNED NOT NULL,
  id_tipo_local bigint(20) UNSIGNED NOT NULL,
  nom_local varchar(255) DEFAULT NULL,
  num_local char(4) DEFAULT NULL,
  endereco varchar(255) DEFAULT NULL,
  num_latitude double DEFAULT NULL,
  num_longitude double DEFAULT NULL,
  code_plus varchar(32) DEFAULT NULL,
  contato_1_local varchar(20) DEFAULT NULL,
  contato_2_local varchar(20) DEFAULT NULL,
  email_local varchar(255) DEFAULT NULL,
  flg_acessibilidade tinyint(1) DEFAULT 1,
  flg_rota tinyint(1) DEFAULT 0,
  flg_tarefa tinyint(1) DEFAULT 0,
  flg_check_in tinyint(1) DEFAULT 0,
  status_local tinyint(1) DEFAULT 1,
  observacao text DEFAULT NULL,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_local)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 12. TABELA: ze_tb_qr_code_eventos
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_qr_code_eventos';
$sql = "CREATE TABLE {$table} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_evento_vaga bigint(20) UNSIGNED NOT NULL,
  data_evento date NOT NULL,
  turno_evento enum('MANHA','TARDE','DIA_TODO') NOT NULL,
  codigo_evento char(4) NOT NULL,
  tipo_codigo enum('LOCAL','EVENTO') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_evento_data_turno (id_evento_vaga, data_evento, turno_evento)
) {$charset_collate};";

/*
|--------------------------------------------------------------------------
| 13. TABELA: ze_tb_convocacao
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_convocacao';
$sql = "CREATE TABLE {$table} (
  id_convocacao bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pleito bigint(20) UNSIGNED NOT NULL,
  id_vaga_pleito bigint(20) UNSIGNED NOT NULL,
  id_colaborador bigint(20) UNSIGNED NOT NULL,
  id_local bigint(20) UNSIGNED DEFAULT NULL,
  num_secao varchar(4) DEFAULT NULL,
  tp_secao_mrv tinyint(1) DEFAULT 1,
  id_funcao bigint(20) UNSIGNED DEFAULT NULL,
  dt_designacao datetime DEFAULT NULL,
  id_usuario_responsavel bigint(20) UNSIGNED DEFAULT NULL,
  status_convocacao varchar(30) NOT NULL,
  eventos longtext,
  data_criacao datetime NOT NULL,
  data_limite_aceite datetime NOT NULL,
  data_aceite datetime DEFAULT NULL,
  data_expiracao datetime DEFAULT NULL,
  renovado_em datetime DEFAULT NULL,
  data_download datetime DEFAULT NULL,
  ip_download varchar(45) DEFAULT NULL,
  entregue_em_maos tinyint(1) DEFAULT 0,
  data_entrega_em_maos datetime DEFAULT NULL,
  id_usuario_entrega bigint(20) UNSIGNED DEFAULT NULL,
  id_evento_sei varchar(50) DEFAULT NULL,
  data_juntada_sei datetime DEFAULT NULL,
  id_usuario_juntada bigint(20) UNSIGNED DEFAULT NULL,
  ip_aceite varchar(45) DEFAULT NULL,
  observacoes text DEFAULT NULL,
  id_usuario_criacao bigint(20) UNSIGNED NOT NULL,
  data_atualizacao datetime DEFAULT NULL,
  id_usuario_atualizacao bigint(20) UNSIGNED DEFAULT NULL,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_convocacao)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 14. TABELA: ze_tb_eventos_vagas
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_eventos_vagas';
$sql = "CREATE TABLE {$table} (
  id_evento_vaga bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_vaga_pleito bigint(20) UNSIGNED NOT NULL,
  id_tipo_evento bigint(20) UNSIGNED DEFAULT NULL,
  id_colaborador bigint(20) UNSIGNED DEFAULT NULL,
  data_evento date NOT NULL,
  hora_inicio time DEFAULT NULL,
  hora_fim time DEFAULT NULL,
  compareceu tinyint(1) DEFAULT NULL,
  vale_alimentacao tinyint(1) DEFAULT 0,
  ds_local_evento varchar(150) DEFAULT NULL,
  codigo_acesso char(6) NULL,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  observacao text DEFAULT NULL,
  PRIMARY KEY (id_evento_vaga)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 15. TABELA: ze_tb_log_convocacao
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_log_convocacao';
$sql = "CREATE TABLE {$table} (
  id_log_convocacao bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_convocacao bigint(20) UNSIGNED NOT NULL,
  status_anterior varchar(30) NOT NULL,
  status_novo varchar(30) NOT NULL,
  acao varchar(50) NOT NULL,
  descricao text DEFAULT NULL,
  id_usuario bigint(20) UNSIGNED DEFAULT NULL,
  ip_origem varchar(45) DEFAULT NULL,
  data_evento datetime NOT NULL,
  PRIMARY KEY (id_log_convocacao)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 16. TABELA: ze_tb_log_vagas_pleitos
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_log_vagas_pleitos';
$sql = "CREATE TABLE {$table} (
  id_log_vaga bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_vaga_pleito bigint(20) UNSIGNED NOT NULL,
  status_anterior varchar(20) NOT NULL,
  status_novo varchar(20) NOT NULL,
  id_colaborador_anterior bigint(20) UNSIGNED DEFAULT NULL,
  id_colaborador_novo bigint(20) UNSIGNED DEFAULT NULL,
  motivo text DEFAULT NULL,
  id_usuario_responsavel bigint(20) UNSIGNED NOT NULL,
  ip_origem varchar(45) DEFAULT NULL,
  data_evento datetime NOT NULL,
  PRIMARY KEY (id_log_vaga)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 17. TABELA: ze_tb_vagas_pleitos
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_vagas_pleitos';
$sql = "CREATE TABLE {$table} (
  id_vaga_pleito bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pleito bigint(20) UNSIGNED DEFAULT NULL,
  id_local bigint(20) UNSIGNED DEFAULT NULL,
  num_secao varchar(4) DEFAULT NULL,
  vaga_seq smallint(6) DEFAULT 1,
  tp_secao_mrv tinyint(1) DEFAULT 1,
  tp_secao_convocacao tinyint(1) DEFAULT 1,
  num_secao_agregada1 char(4) DEFAULT NULL,
  num_secao_agregada2 char(4) DEFAULT NULL,
  num_secao_agregada3 char(4) DEFAULT NULL,
  num_secao_agregada4 char(4) DEFAULT NULL,
  id_funcao bigint(20) UNSIGNED DEFAULT NULL,
  id_colaborador bigint(20) UNSIGNED DEFAULT NULL,
  dt_designacao datetime DEFAULT NULL,
  id_usuario_responsavel bigint(20) UNSIGNED DEFAULT NULL,
  status_vaga varchar(20) DEFAULT 'DISPONIVEL',
  observacao text DEFAULT NULL,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_vaga_pleito)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 18. TABELA: ze_tb_rotas
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_rotas';
$sql = "CREATE TABLE {$table} (
  id_rota bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_local bigint(20) UNSIGNED NOT NULL,
  id_pleito bigint(20) UNSIGNED NOT NULL,
  ds_rota varchar(255) DEFAULT NULL,
  tipo_rota varchar(40) NOT NULL,
  status_rota varchar(20) DEFAULT 'planejada',
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  id_usuario_criacao bigint(20) UNSIGNED DEFAULT NULL,
  id_usuario_atualizacao bigint(20) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id_rota)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 19. TABELA: ze_tb_rotas_destinos
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_rotas_destinos';
$sql = "CREATE TABLE {$table} (
  id_rota bigint(20) UNSIGNED NOT NULL,
  sq_rota int(11) NOT NULL,
  id_local_destino bigint(20) UNSIGNED NOT NULL,
  qt_urnas_contingencia int(11) DEFAULT 0,
  observacao text DEFAULT NULL,
  PRIMARY KEY (id_rota, sq_rota)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 20. TABELA: ze_tb_veiculos
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_veiculos';
$sql = "CREATE TABLE {$table} (
  id_veiculo bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  placa varchar(10) NOT NULL,
  marca varchar(60) DEFAULT NULL,
  modelo varchar(60) DEFAULT NULL,
  cor varchar(30) DEFAULT NULL,
  ds_veiculo varchar(40) DEFAULT NULL,
  ds_combustivel varchar(30) DEFAULT NULL,
  id_motorista bigint(20) UNSIGNED DEFAULT NULL,
  status_veiculo tinyint(1) DEFAULT 1,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_veiculo),
  UNIQUE KEY uniq_placa (placa)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 21. TABELA: ze_tb_veiculos_local
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_veiculos_local';
$sql = "CREATE TABLE {$table} (
  id_veiculos_local bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pleito bigint(20) UNSIGNED NOT NULL,
  id_veiculo bigint(20) UNSIGNED NOT NULL,
  id_local bigint(20) UNSIGNED NOT NULL,
  tipo_veiculo_recomendado varchar(60) DEFAULT NULL,
  status_vinculo tinyint(1) DEFAULT 1,
  created_at datetime DEFAULT NULL,
  updated_at datetime DEFAULT NULL,
  PRIMARY KEY (id_veiculos_local)
) {$charset_collate};";
dbDelta($sql);


/*
|--------------------------------------------------------------------------
| 22. TABELA: ze_tb_secoes
|--------------------------------------------------------------------------
*/
$table = $wpdb->prefix . 'ze_tb_secoes';
$sql = "CREATE TABLE {$table} (
  id_secao bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  id_local bigint(20) UNSIGNED NOT NULL,
  num_secao char(4) NOT NULL,
  PRIMARY KEY (id_secao)
) {$charset_collate};";
dbDelta($sql);


dbDelta($sql);