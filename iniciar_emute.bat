@echo off
setlocal
title EMUTE - Gestao Hospitalar Portatil
color 0b

echo ======================================================
echo           SISTEMA EMUTE - ARRANQUE AUTOMATICO
echo ======================================================
echo.

cd /d "%~dp0"

:: 1. Verificar Dependencias (Vendor)
if not exist "vendor\autoload.php" (
    color 0c
    echo [ERRO] Pasta 'vendor' nao encontrada ou incompleta!
    echo Por favor, termine o 'composer install' antes de rodar este script.
    echo.
    pause
    exit
)

:: 2. Verificar PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0c
    echo [ERRO] PHP nao encontrado no sistema.
    echo Certifique-se de que o XAMPP esta instalado ou PHP esta no PATH.
    echo.
    pause
    exit
)

:: 3. Garantir Base de Dados SQLite
if not exist "database\database.sqlite" (
    echo [INFO] Criando base de dados local...
    type nul > "database\database.sqlite"
)

:: 4. Limpar cache e preparar (opcional mas recomendado)
echo [1/3] Limpando configuracoes antigas...
php artisan config:clear >nul 2>nul

:: 5. Iniciar Servidor
echo [2/3] Iniciando servidor na porta 8000...
start /b php artisan serve --port=8000 --host=0.0.0.0

:: 6. Abrir Navegador
echo [3/3] Abrindo o sistema no navegador...
timeout /t 3 /nobreak >nul
start http://127.0.0.1:8000

echo.
echo ======================================================
echo    SISTEMA ATIVO! ACESSE: http://127.0.0.1:8000
echo    NAO FECHE ESTA JANELA ENQUANTO USAR O SISTEMA.
echo ======================================================
echo.

:: Manter a janela aberta para ver logs se houver erro
php artisan serve --port=8000 --host=0.0.0.0
pause
