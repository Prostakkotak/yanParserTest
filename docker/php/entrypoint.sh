#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

if [ ! -d vendor ]; then
  composer install --prefer-dist --no-progress
fi

if [ -z "$APP_KEY" ]; then
  php artisan key:generate --force
fi

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
