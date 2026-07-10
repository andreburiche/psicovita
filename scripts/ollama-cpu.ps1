# Reinicia o Ollama forçando CPU (contorna erros CUDA).
# Uso: .\scripts\ollama-cpu.ps1

$ErrorActionPreference = "Stop"

[System.Environment]::SetEnvironmentVariable("OLLAMA_NUM_GPU", "0", "User")
$env:OLLAMA_NUM_GPU = "0"
$env:CUDA_VISIBLE_DEVICES = ""

Write-Host "==> A parar processos Ollama..." -ForegroundColor Cyan
Get-Process | Where-Object { $_.ProcessName -match "ollama" } | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

$ollama = Join-Path $env:LOCALAPPDATA "Programs\Ollama\ollama.exe"
if (-not (Test-Path $ollama)) {
    $cmd = Get-Command ollama -ErrorAction SilentlyContinue
    if (-not $cmd) { throw "Ollama nao encontrado." }
    $ollama = $cmd.Source
}

Write-Host "==> A iniciar ollama serve (CPU)..." -ForegroundColor Cyan
$psi = New-Object System.Diagnostics.ProcessStartInfo
$psi.FileName = $ollama
$psi.Arguments = "serve"
$psi.UseShellExecute = $false
$psi.CreateNoWindow = $true
$psi.EnvironmentVariables["OLLAMA_NUM_GPU"] = "0"
$psi.EnvironmentVariables["CUDA_VISIBLE_DEVICES"] = ""
[void][System.Diagnostics.Process]::Start($psi)

Start-Sleep -Seconds 4

$modelfile = Join-Path $PSScriptRoot "Modelfile.llama32-cpu"
if (Test-Path $modelfile) {
    Write-Host "==> A garantir modelo llama3.2-cpu..." -ForegroundColor Cyan
    & $ollama create llama3.2-cpu -f $modelfile | Out-Null
}

Write-Host "==> Teste rapido..." -ForegroundColor Cyan
$body = '{"model":"llama3.2-cpu","messages":[{"role":"user","content":"OK"}],"stream":false}'
try {
    $r = Invoke-RestMethod -Uri "http://127.0.0.1:11434/v1/chat/completions" -Method Post -ContentType "application/json" -Body $body -TimeoutSec 120
    Write-Host ("OK: " + $r.choices[0].message.content) -ForegroundColor Green
} catch {
    Write-Host ("Falha: " + $_.Exception.Message) -ForegroundColor Red
    exit 1
}

Write-Host "`nNo .env use: OPENAI_CHAT_MODEL=llama3.2-cpu" -ForegroundColor Yellow
Write-Host "Depois: php artisan config:clear" -ForegroundColor Yellow
