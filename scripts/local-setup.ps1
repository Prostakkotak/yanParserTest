# Local dev env setup (localhost, not VPS).
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)

function Copy-LocalEnv($source, $dest) {
    if (-not (Test-Path $source)) {
        throw "Template not found: $source"
    }
    Copy-Item -Path $source -Destination $dest -Force
    Write-Host "  $dest"
}

Write-Host "==> Local env (Docker + Laravel + Vite)"
Copy-LocalEnv (Join-Path $Root ".env.local.example") (Join-Path $Root ".env")
Copy-LocalEnv (Join-Path $Root "backend\.env.local.example") (Join-Path $Root "backend\.env")
Copy-LocalEnv (Join-Path $Root "frontend\.env.local.example") (Join-Path $Root "frontend\.env")

Write-Host ""
Write-Host "Done. Next:"
Write-Host "  docker compose up -d --build"
Write-Host "  cd frontend; npm run dev"
Write-Host ""
Write-Host "On VPS use .env.production.example instead."
