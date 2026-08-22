@echo off
title Atom - Add Custom AI Model
echo ========================================
echo   Add Custom AI Model
echo ========================================
echo.

cd /d "%~dp0backend"
php spark db:seed AddCustomModel

echo.
pause
