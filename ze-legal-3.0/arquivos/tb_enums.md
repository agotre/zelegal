# DADOS DO SISTEMA — tb_enums

## 1. Nome da Tabela
tb_enums

## 2. Finalidade
Tabela central de enums do sistema ZE-LEGAL 3.0.

Responsável por armazenar valores padronizados que serão utilizados
em campos específicos de tabelas do sistema, evitando valores fixos
hardcoded no código.

## 3. Campos da Tabela

| Campo | Finalidade |
|------|-----------|
| id_enum | Identificador único do enum |
| ds_enum | Descrição ou valor do enum |
| tb_alvo_enum | Nome da tabela onde o enum será aplicado |
| campo_alvo_enum | Nome do campo da tabela alvo |
| num_orden_enum | Ordem de exibição do enum |
| status_enum | Status lógico do enum |

## 4. Regras Básicas

- A tabela tb_enums é genérica e reutilizável
- Cada enum deve estar vinculado a:
  - uma tabela alvo
  - um campo alvo
- A aplicação do enum é controlada pelo sistema
- Nenhum valor é criado automaticamente
- A manutenção é feita exclusivamente por telas administrativas

## 5. Observações

- Tipos de dados serão definidos no momento da criação da tabela
- Este arquivo é obrigatório para a existência da tabela no banco
- Alterações neste arquivo exigem versionamento
