# Reinicia Evolution com imagem corrigida e força novo QR code
# Uso: .\scripts\evolution-fix-qrcode.ps1

$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$ApiKey = if ($env:EVOLUTION_API_KEY) { $env:EVOLUTION_API_KEY } else { "minha-chave-segura-123" }
$BaseUrl = "http://127.0.0.1:8082"
$Instance = if ($env:EVOLUTION_INSTANCE) { $env:EVOLUTION_INSTANCE } else { "psiconecta" }
$Headers = @{ apikey = $ApiKey }

Set-Location $ProjectRoot

Write-Host "==> Recriando Evolution API (v2.3.7)..." -ForegroundColor Cyan
docker compose up -d evolution --force-recreate

Write-Host "==> Aguardando API..." -ForegroundColor Cyan
Start-Sleep -Seconds 20

Write-Host "==> Reiniciando instancia $Instance..." -ForegroundColor Cyan
try {
    Invoke-RestMethod -Uri "$BaseUrl/instance/restart/$Instance" -Method Put -Headers $Headers | Out-Null
} catch {
    Write-Host "Restart opcional falhou (ok se instancia nova): $($_.Exception.Message)" -ForegroundColor Yellow
}

try {
    Invoke-RestMethod -Uri "$BaseUrl/instance/logout/$Instance" -Method Delete -Headers $Headers | Out-Null
} catch {
    Write-Host "Logout opcional falhou: $($_.Exception.Message)" -ForegroundColor Yellow
}

Start-Sleep -Seconds 3

Write-Host "==> Pedindo QR code..." -ForegroundColor Cyan
$qr = Invoke-RestMethod -Uri "$BaseUrl/instance/connect/$Instance" -Method Get -Headers $Headers
$qr | ConvertTo-Json -Depth 5

if ($qr.base64 -or $qr.qrcode?.base64) {
    Write-Host "`nQR code recebido! Abra tambem: $BaseUrl/manager" -ForegroundColor Green
} elseif ($qr.pairingCode) {
    Write-Host "`nCodigo de pareamento: $($qr.pairingCode)" -ForegroundColor Green
} else {
    Write-Host "`nSe count=0, aguarde 10s e abra $BaseUrl/manager" -ForegroundColor Yellow
}

Write-Host "`nEstado:" -ForegroundColor Cyan
Invoke-RestMethod -Uri "$BaseUrl/instance/connectionState/$Instance" -Method Get -Headers $Headers | ConvertTo-Json
