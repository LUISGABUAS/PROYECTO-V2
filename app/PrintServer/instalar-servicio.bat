@echo off
:: Requiere ejecutar como Administrador
NET SESSION >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo ERROR: Ejecuta este archivo como Administrador.
    echo Haz clic derecho sobre el archivo y selecciona "Ejecutar como administrador".
    pause
    exit /b 1
)

SET DIR=%~dp0
SET NSSM=%DIR%nssm.exe
SET EXE=%DIR%PrintServer.exe
SET NOMBRE=PrintServerZebra

IF NOT EXIST "%NSSM%" (
    echo ERROR: No se encontro nssm.exe en la carpeta.
    echo Descargalo de https://nssm.cc/download y ponlo aqui: %DIR%
    pause
    exit /b 1
)

IF NOT EXIST "%EXE%" (
    echo ERROR: No se encontro PrintServer.exe en la carpeta.
    pause
    exit /b 1
)

echo Instalando servicio "%NOMBRE%"...

:: Detener e instalar si ya existe
"%NSSM%" stop "%NOMBRE%" >nul 2>&1
"%NSSM%" remove "%NOMBRE%" confirm >nul 2>&1

"%NSSM%" install "%NOMBRE%" "%EXE%"
"%NSSM%" set "%NOMBRE%" DisplayName "Print Server Zebra - Pacas Yadira"
"%NSSM%" set "%NOMBRE%" Description "Servicio de impresion automatica de etiquetas Zebra"
"%NSSM%" set "%NOMBRE%" AppDirectory "%DIR%"
"%NSSM%" set "%NOMBRE%" Start SERVICE_AUTO_START
"%NSSM%" set "%NOMBRE%" AppRestartDelay 5000
"%NSSM%" set "%NOMBRE%" AppStdout "%DIR%print-server.log"
"%NSSM%" set "%NOMBRE%" AppStderr "%DIR%print-server.log"
"%NSSM%" set "%NOMBRE%" AppRotateFiles 1
"%NSSM%" set "%NOMBRE%" AppRotateBytes 2097152

echo Iniciando servicio...
"%NSSM%" start "%NOMBRE%"

echo.
echo Servicio instalado correctamente.
echo El servidor de impresion iniciara automaticamente con Windows.
echo Log en: %DIR%print-server.log
echo.
pause
