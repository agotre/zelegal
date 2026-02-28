# ZE-LEGAL 3.0 – PADRÃO OFICIAL DE ESTRUTURA DE PASTAS

Este documento define a **estrutura única, oficial e obrigatória** de pastas do projeto **ZE-LEGAL 3.0**.

Tudo o que estiver aqui descrito passa a ser a **REGRA DE VERDADE** do projeto.

Qualquer criação, alteração ou inclusão fora deste padrão é **proibida**, salvo autorização expressa e documentada.

---

## 1. PRINCÍPIOS GERAIS

1. Código **não se mistura** com mídia
2. Mídia **não se mistura** com lógica
3. Assets de interface **não se misturam** com dados operacionais
4. Estrutura simples, clara e sem fragmentação desnecessária
5. Nada pode ser criado sem definição prévia neste documento

---

## 2. ESTRUTURA OFICIAL DE PASTAS

```
ze-legal-3.0/
├── ze-legal.php              # Bootstrap principal – versão 3.0
│
├── install/                  # Ativação, migração e criação de tabelas
│
├── admin/
│   ├── menu.php              # Menu administrativo e callbacks
│   │
│   ├── assets/               # Assets administrativos (UI)
│   │   ├── css/              # Estilos
│   │   ├── js/               # Scripts
│   │   └── images/
│   │       ├── icons/        # Ícones do sistema (SVG / PNG)
│   │       └── ui/           # Logos e imagens institucionais
│   │
│   └── pages/                # Páginas administrativas
│       ├── dashboard.php
│       ├── home.php
│       ├── config.php
│       │
│       ├── cadastros/        # Pleito, zona, município, locais, colaborador
│       ├── vagas/            # Criar e preencher vagas
│       ├── convocacao/
│       ├── eventos/
│       ├── gestao/           # Usuários do sistema
│       ├── portal/           # Check-in, perfil, convocações
│       └── importar/         # Importações estruturadas
│
├── media/                    # Mídia operacional do sistema
│   └── colaboradores/
│       ├── fotos/            # Fotos dos colaboradores
│       └── documentos/       # Documentos pessoais (se autorizado)
│
├── api/                      # Endpoints REST
│
├── ajax/                     # Processos AJAX em etapas
│
├── handlers/                 # admin_post e wp_ajax
│
├── domain/                   # Regras de domínio (usuários, vagas, etc.)
│
├── utils/                    # Permissões, helpers, PDF
│
└── vendor/                   # Dependências externas (Composer)
```

---

## 3. DEFINIÇÕES IMPORTANTES

### 3.1 admin/assets/

Uso exclusivo para **interface administrativa**.

Permitido:

* CSS
* JavaScript
* Ícones
* Logos
* Imagens institucionais

Proibido:

* Fotos de pessoas
* Arquivos enviados por usuários
* Documentos pessoais

---

### 3.2 media/

Uso exclusivo para **dados operacionais dinâmicos**.

Características:

* Conteúdo vinculado a registros do banco
* Upload controlado por regras do sistema
* Pode conter dados sensíveis

É **terminantemente proibido** salvar esses arquivos em `admin/assets`.

---

## 4. REGRAS ABSOLUTAS

1. Nenhuma nova pasta pode ser criada fora deste padrão
2. Nenhuma pasta pode ter dupla finalidade
3. Fotos de colaboradores **sempre** em `media/colaboradores/fotos/`
4. Ícones do sistema **sempre** em `admin/assets/images/icons/`
5. Logos e imagens institucionais **sempre** em `admin/assets/images/ui/`

---

## 5. ALTERAÇÕES FUTURAS

Qualquer alteração nesta estrutura deve:

1. Ser discutida previamente
2. Ser documentada neste arquivo
3. Receber autorização explícita

Sem isso, a alteração é considerada **inválida**.

---

📌 Este documento é parte integrante do repositório oficial do ZE-LEGAL 3.0.
