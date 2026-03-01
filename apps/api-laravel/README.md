# ZE Legal API (Laravel)

Repositorio da nova aplicacao backend para substituir o legado WordPress.

## Stack
- Laravel 12
- PHP 8.x
- MySQL

## Estrutura inicial
- `app/` codigo da aplicacao
- `config/` configuracoes
- `database/migrations/` migrations versionadas
- `routes/` rotas web/api
- `docs/` guias de deploy e operacao

## Bootstrap local (quando Composer estiver disponivel)
```bash
composer create-project laravel/laravel . "^12.0"
php artisan key:generate
php artisan migrate
```

## Primeiras entregas
1. Autenticacao por CPF/senha.
2. Tabelas de acesso (`usuarios`, `log_usuarios`, `usuario_escopos`).
3. Modulo de colaboradores (CRUD inicial).

