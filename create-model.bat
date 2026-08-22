@echo off
title Atom - Create Personal AI Model
echo ========================================
echo   Create Personal AI Model
echo ========================================
echo.
echo Answer the prompts below to add your model.
echo

cd /d "%~dp0backend"
php spark db:seed AddCustomModel

echo.
echo Model added. Restart server to use it.
pause
