# Deploy Hostinger (Laravel)

## 1) Preparar aplicacao
1. Enviar projeto para a hospedagem via Git/FTP.
2. Configurar `.env` com dados do MySQL da Hostinger.
3. Gerar `APP_KEY`:
```bash
php artisan key:generate
```

## 2) Banco de dados
1. Criar banco e usuario MySQL no painel.
2. Executar migrations:
```bash
php artisan migrate --force
```

## 3) Publicacao
1. Apontar o dominio/subdominio para a pasta `public/` do Laravel.
2. Garantir permissao de escrita em `storage/` e `bootstrap/cache/`.

## 4) Pos-deploy
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 5) Checklist minimo
- SSL ativo
- `APP_DEBUG=false`
- senha de banco forte
- backup de banco habilitado

