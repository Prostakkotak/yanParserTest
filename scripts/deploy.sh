#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"

cd "$APP_DIR"

echo "==> Pull latest code"
git pull --ff-only origin main

echo "==> Build and start containers"
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d --remove-orphans

echo "==> Prune unused images"
docker image prune -f

echo "==> Health check"
set -a
[ -f .env ] && . ./.env
set +a

curl -fsS "http://127.0.0.1:${APP_PORT:-80}/up" >/dev/null
echo "Deploy finished successfully."
