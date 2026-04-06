# Script para cambiar entre entornos en Windows PowerShell
# Uso: .\switch-env.ps1 production | .\switch-env.ps1 simulation

param(
    [string]$ENV = ""
)

$BASE_DIR = Split-Path -Parent $MyInvocation.MyCommand.Path

if ([string]::IsNullOrWhiteSpace($ENV)) {
    Write-Host "Uso: .\switch-env.ps1 production | .\switch-env.ps1 simulation"
    Write-Host ""
    
    # Mostrar entorno actual
    if (Test-Path "$BASE_DIR\.env") {
        $content = Get-Content "$BASE_DIR\.env"
        $current = ($content | Select-String "^APP_ENV=" | ForEach-Object { $_ -split '=' | Select-Object -Last 1 }).Trim()
        Write-Host "Entorno actual: $current"
    }
    
    exit 1
}

$SOURCE = "$BASE_DIR\.env.$ENV"

if (-not (Test-Path $SOURCE)) {
    Write-Host "Error: no existe el archivo .env.$ENV"
    exit 1
}

Copy-Item -Path $SOURCE -Destination "$BASE_DIR\.env" -Force

Write-Host "Entorno cambiado a: $ENV"
Write-Host "BD: $(((Get-Content $SOURCE | Select-String '^DB_NAME=') -split '=' | Select-Object -Last 1).Trim())"
Write-Host "Usuario: $(((Get-Content $SOURCE | Select-String '^DB_USER=') -split '=' | Select-Object -Last 1).Trim())"
Write-Host ""
Write-Host "✅ Configuración actualizada correctamente"
