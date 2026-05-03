#!/usr/bin/env sh

set -e

cd /var/www/html

echo "Iniciando container da aplicação Laravel..."

if [ ! -f .env ]; then
  echo "Arquivo app/.env não encontrado. Copiando app/.env.example..."
  cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
  echo "Dependências PHP não encontradas. Executando composer install..."
  composer install --no-interaction --prefer-dist
else
  echo "Dependências PHP já instaladas."
fi

if ! grep -q "^APP_KEY=base64:" .env; then
  echo "APP_KEY ausente. Gerando chave da aplicação..."
  php artisan key:generate --force
else
  echo "APP_KEY já configurada."
fi

if [ ! -d node_modules ]; then
  echo "Dependências JS não encontradas. Executando npm ci..."
  npm ci
else
  echo "Dependências JS já instaladas."
fi

if [ ! -f public/build/manifest.json ]; then
  echo "Build frontend não encontrado. Executando npm run build..."
  npm run build
else
  echo "Build frontend já encontrado."
fi

mkdir -p storage bootstrap/cache

chmod -R ug+rw storage bootstrap/cache || true

echo "Container pronto. Iniciando processo principal..."

exec "$@"