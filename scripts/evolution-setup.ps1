# PsiConecta — subir Evolution API no Windows (porta 8082)
# Requer Docker Desktop: https://www.docker.com/products/docker-desktop/

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$ApiKey = if ($env:EVOLUTION_API_KEY) { $env:EVOLUTION_API_KEY } else { "minha-chave-segura-123" }
$BaseUrl = "http://127.0.0.1:8082"
$Instance = if ($env:EVOLUTION_INSTANCE) { $env:EVOLUTION_INSTANCE } else { "psiconecta" }

Write-Host "==> Verificando Docker..." -ForegroundColor Cyan
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "ERRO: Docker nao instalado. Instale o Docker Desktop e tente novamente." -ForegroundColor Red
    exit 1
}

Set-Location $ProjectRoot
Write-Host "==> Subindo Evolution API na porta 8082..." -ForegroundColor Cyan
docker compose up -d evolution

Start-Sleep -Seconds 5

Write-Host "==> Criando instancia '$Instance' (se ainda nao existir)..." -ForegroundColor Cyan
$body = @{
    instanceName = $Instance
    qrcode       = $true
    integration  = "WHATSAPP-BAILEYS"
} | ConvertTo-Json

try {
    $create = Invoke-RestMethod -Uri "$BaseUrl/instance/create" -Method Post `
        -Headers @{ apikey = $ApiKey } -ContentType "application/json" -Body $body
    Write-Host "Instancia criada. Abra o Manager para o QR code:" -ForegroundColor Green
    Write-Host "$BaseUrl/manager" -ForegroundColor Yellow
    $create | ConvertTo-Json -Depth 5
} catch {
    Write-Host "Aviso ao criar instancia (pode ja existir): $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host "`n==> Estado da conexao:" -ForegroundColor Cyan
try {
    $state = Invoke-RestMethod -Uri "$BaseUrl/instance/connectionState/$Instance" -Method Get `
        -Headers @{ apikey = $ApiKey }
    $state | ConvertTo-Json -Depth 5
} catch {
    Write-Host "ERRO: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nConfigure no .env do PsiConecta:" -ForegroundColor Cyan
Write-Host "EVOLUTION_API_URL=$BaseUrl"
Write-Host "EVOLUTION_API_KEY=$ApiKey"
Write-Host "EVOLUTION_INSTANCE=$Instance"
Write-Host "`nDepois: php artisan config:clear" -ForegroundColor Cyan
