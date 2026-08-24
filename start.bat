@echo off
title Atom Backend Server
echo ========================================
echo   Atom Backend - Starting Server
echo ========================================
echo.

cd /d "%~dp0backend"

echo [1/2] Running database migrations...
php -d extension=intl spark migrate --force 2>nul

echo.
echo [2/2] Starting API server on http://localhost:8080
echo.
php -d extension=intl spark serve --host 0.0.0.0

pause
