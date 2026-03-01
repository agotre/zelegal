# Registro de Decisao Tecnica - Hostinger

## Contexto
Projeto ZE Legal em migracao do legado WordPress para nova aplicacao web multiusuario.

## Decisao aprovada
- Hosting alvo: Hostinger Business Web Hosting
- Backend principal: Laravel 12 + PHP 8.x
- Banco de dados: MySQL
- ORM: Eloquent
- Frontend inicial: Blade + Tailwind (podendo evoluir depois para frontend separado)

## Justificativa
- Melhor aderencia operacional ao ambiente compartilhado.
- Menor complexidade de deploy e manutencao.
- Melhor estabilidade para execucao continua no plano atual.

## Escopo inicial de implementacao
1. Autenticacao por CPF + senha.
2. Controle de acesso por perfil/permissao.
3. Contexto operacional por zona e pleito.
4. Modulo inicial de colaboradores.

## Diretriz de repositorio
- A nova aplicacao sera mantida em `apps/api-laravel`.
- O legado `ze-legal-3.0` permanece apenas como referencia de regras e migracao.

