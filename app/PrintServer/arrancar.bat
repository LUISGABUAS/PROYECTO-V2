@echo off

:: Descargar NSSM si no existe
if not exist "%~dp0nssm.exe" (
    powershell -Command "Invoke-WebRequest -Uri 'https://nssm.cc/release/nssm-2.24.zip' -OutFile '%~dp0nssm.zip'"
    powershell -Command "Expand-Archive '%~dp0nssm.zip' -DestinationPath '%~dp0'"
    copy "%~dp0nssm-2.24\win64\nssm.exe" "%~dp0nssm.exe"
)

:: Detener e instalar limpio si ya existe
"%~dp0nssm.exe" stop PrintServer 2>nul
"%~dp0nssm.exe" remove PrintServer confirm 2>nul

:: Instalar PrintServer como servicio
"%~dp0nssm.exe" install PrintServer "%~dp0PrintServer.exe"
"%~dp0nssm.exe" set PrintServer Start SERVICE_AUTO_START

:: Reiniciar automaticamente si el proceso muere
"%~dp0nssm.exe" set PrintServer AppExit Default Restart
"%~dp0nssm.exe" set PrintServer AppRestartDelay 5000

:: Guardar logs para diagnostico
"%~dp0nssm.exe" set PrintServer AppStdout "%~dp0logs\stdout.log"
"%~dp0nssm.exe" set PrintServer AppStderr "%~dp0logs\stderr.log"
"%~dp0nssm.exe" set PrintServer AppRotateFiles 1
"%~dp0nssm.exe" set PrintServer AppRotateSeconds 86400

:: Crear carpeta de logs si no existe
if not exist "%~dp0logs" mkdir "%~dp0logs"

net start PrintServer

echo PrintServer instalado como servicio con reinicio automatico
timeout /t 3