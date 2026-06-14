#!/usr/bin/env bash
# Подготовка env-файлов для локальной разработки (localhost, не VPS).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

copy_local_env() {
  local src="$1"
  local dst="$2"
  if [[ ! -f "$src" ]]; then
    echo "Не найден шаблон: $src" >&2
    exit 1
  fi
  cp "$src" "$dst"
  echo "  $dst"
}

echo "==> Локальные env (Docker + Laravel + Vite)"
copy_local_env "$ROOT/.env.local.example" "$ROOT/.env"
copy_local_env "$ROOT/backend/.env.local.example" "$ROOT/backend/.env"
copy_local_env "$ROOT/frontend/.env.local.example" "$ROOT/frontend/.env"

echo ""
echo "Готово. Дальше:"
echo "  docker compose up -d --build"
echo "  cd frontend && npm run dev"
echo ""
echo "Для VPS на сервере используйте .env.production.example, не эти файлы."
