@echo off
title ATOM AI Assistant Platform - All-in-One Launcher
color 0A
cls
echo ================================================================================
echo                    ATOM AI ASSISTANT PLATFORM
echo                        ALL-IN-ONE LAUNCHER
echo ================================================================================
echo.

:: 1. Database Migrations
echo [1/4] Running database migrations...
cd /d "%~dp0backend"
php -d extension=intl spark migrate --force >nul 2>&1
echo       Done.

:: 2. Start Backend API Server
echo.
echo [2/4] Starting Backend REST API Server (http://localhost:8080)...
start cmd /k "title ATOM Backend API Server ^& cd /d "%~dp0backend" ^& php -d extension=intl spark serve --host 0.0.0.0"

:: Wait for API server initialization
timeout /t 3 /nobreak >nul

:: 3. Run Self-Learning & Health Initialization
echo.
echo [3/4] Initializing Self-Learning Engine & Health Check...
cd /d "%~dp0backend"
php -d extension=intl spark atom:self-improve >nul 2>&1
echo       Self-Learning & Health Active.

:: 4. Launch Desktop Assistant & Web Control Panel
echo.
echo [4/4] Launching ATOM Desktop Assistant & Web Dashboard...
cd /d "%~dp0"
start "" "http://localhost/my%%20work/Atom/frontend/web/admin/" 2>nul
start cmd /k "title ATOM Desktop Assistant ^& cd /d "%~dp0" ^& dotnet run --project src/PersonalAIAssistant/PersonalAIAssistant.csproj"

echo.
echo ================================================================================
echo   [SUCCESS] All ATOM Services Running!
echo   ----------------------------------------------------------------------------
echo   * Backend REST API   : http://localhost:8080
echo   * Web Admin & GoT    : http://localhost/my work/Atom/frontend/web/admin/
echo   * Web Chat Interface : http://localhost/my work/Atom/frontend/web/
echo   * Desktop Client     : Launching WPF UI
echo ================================================================================
echo.
echo Press any key to stop all ATOM background processes and exit...
pause >nul

echo.
echo Stopping all ATOM PHP services...
taskkill /F /FI "WINDOWTITLE eq ATOM Backend API Server*" >nul 2>&1
taskkill /F /FI "WINDOWTITLE eq ATOM Desktop Assistant*" >nul 2>&1
echo ATOM services stopped cleanly.
timeout /t 2 /nobreak >nul
exit
