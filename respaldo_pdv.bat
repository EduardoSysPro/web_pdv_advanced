@echo off
setlocal enabledelayedexpansion

:: ========================================================
:: CONFIGURACIÓN DEL RESPALDO PDV
:: ========================================================
:: Detectar carpeta de OneDrive automáticamente o usar ruta predeterminada
if defined OneDrive (
    set "DESTINO=%OneDrive%\RespaldosPDV"
) else if defined OneDriveConsumer (
    set "DESTINO=%OneDriveConsumer%\RespaldosPDV"
) else if defined OneDriveCommercial (
    set "DESTINO=%OneDriveCommercial%\RespaldosPDV"
) else (
    set "DESTINO=C:\Users\%USERNAME%\OneDrive\RespaldosPDV"
)

set "DB_NAME=web_pdv_db"
set "DB_USER=root"
set "DB_PASS="
set "DB_HOST=localhost"
set "DB_PORT=3306"

:: Ruta a mysqldump en XAMPP (o WampServer como alternativa)
set "MYSQLDUMP=C:\xampp\mysql\bin\mysqldump.exe"

:: Si no existe en la ruta fija, buscarlo automáticamente en XAMPP y WampServer
if not exist "%MYSQLDUMP%" (
    for /f "delims=" %%f in ('dir /b /s "C:\xampp\mysql\bin\*mysqldump.exe" 2^>nul') do (
        set "MYSQLDUMP=%%f"
        goto :dump_encontrado
    )
    for /f "delims=" %%f in ('dir /b /s "C:\wamp64\bin\mysql\*mysqldump.exe" 2^>nul') do (
        set "MYSQLDUMP=%%f"
        goto :dump_encontrado
    )
    for /f "delims=" %%f in ('dir /b /s "C:\wamp64\bin\mariadb\*mysqldump.exe" 2^>nul') do (
        set "MYSQLDUMP=%%f"
        goto :dump_encontrado
    )
)
:dump_encontrado

:: Crear carpeta de destino si no existe
if not exist "%DESTINO%" (
    mkdir "%DESTINO%"
)

:: Obtener fecha y hora en formato exacto (YYYY-MM-DD_HH-mm-ss) compatible con cualquier región
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"') do (
    set "TIMESTAMP=%%i"
)

set "ARCHIVO=%DESTINO%\backup_%DB_NAME%_%TIMESTAMP%.sql"
set "LOG=%DESTINO%\registro_respaldos.log"

:: Parámetro de contraseña (solo se envía si se define una)
set "AUTH_PASS="
if not "%DB_PASS%"=="" (
    set "AUTH_PASS=--password=%DB_PASS%"
)

:: Registrar inicio en log (incluye contexto de ejecución para depurar el Programador de tareas)
echo [%TIMESTAMP%] Iniciando respaldo de '%DB_NAME%'... >> "%LOG%"
echo [%TIMESTAMP%] Usuario: %USERNAME% ^| OneDrive: "%OneDrive%" ^| mysqldump: "%MYSQLDUMP%" >> "%LOG%"

if not exist "%MYSQLDUMP%" (
    echo [%TIMESTAMP%] ERROR: No se encontro mysqldump.exe en XAMPP ni WampServer >> "%LOG%"
    endlocal
    exit /b 1
)

:: Ejecutar mysqldump
"%MYSQLDUMP%" --host=%DB_HOST% --port=%DB_PORT% --user=%DB_USER% %AUTH_PASS% --databases %DB_NAME% --routines --triggers --single-transaction --quick --default-character-set=utf8mb4 > "%ARCHIVO%" 2>> "%LOG%"

if !ERRORLEVEL! equ 0 (
    echo [%TIMESTAMP%] Respaldo generado exitosamente: %ARCHIVO% >> "%LOG%"
    echo Respaldo completado con exito: %ARCHIVO%
) else (
    echo [%TIMESTAMP%] ERROR al generar el respaldo. Codigo: !ERRORLEVEL! >> "%LOG%"
    echo ERROR: Ocurrio un problema al generar el respaldo. Revisa %LOG%
)

:: Mantenimiento automático: Eliminar respaldos con más de 15 días de antigüedad para ahorrar espacio
forfiles /p "%DESTINO%" /m *.sql /d -15 /c "cmd /c del @path" 2>nul

endlocal
