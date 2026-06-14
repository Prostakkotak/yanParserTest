#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if [ ! -d vendor ]; then
  composer install --prefer-dist --no-progress
fi

# Generate APP_KEY when missing or malformed in .env (not only when env var is empty)
app_key_line=$(grep -m1 '^APP_KEY=' .env 2>/dev/null || true)
app_key_value=${app_key_line#APP_KEY=}
if [ -z "$app_key_value" ] || ! echo "$app_key_value" | grep -qE '^base64:[A-Za-z0-9+/]{40,}={0,2}$'; then
  php artisan key:generate --force
fi

php artisan package:discover --ansi

if [ "$APP_ENV" = "production" ]; then
  php artisan config:cache
  php artisan route:cache
fi

until php artisan migrate --force; do
  echo "Waiting for database..."
  sleep 2
done

php artisan db:seed --force

exec "$@"
