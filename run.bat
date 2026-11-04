@echo off
title ZK Attendance & HRMS ERP System
echo ===================================================
echo   Starting ZK Attendance & HRMS ERP (Laravel)
echo ===================================================
echo.

where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "D:\xampp\php\php.exe" (
        set "PATH=D:\xampp\php;%PATH%"
    ) else (
        echo [ERROR] PHP is not found in your system PATH or D:\xampp\php.
        echo Please ensure D:\xampp\php is in your PATH environment variable.
        pause
        exit /b
    )
)

echo [1/3] Running database migrations...
php artisan migrate --force --quiet

echo [2/3] Optimizing configuration and route cache...
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

echo [3/3] Opening browser at http://127.0.0.1:8000 ...
start "" "http://127.0.0.1:8000"

echo.
echo ===================================================
echo   Server is running at: http://127.0.0.1:8000
echo   Default Admin: admin@local.com / admin123
echo   Press Ctrl+C to stop the server.
echo ===================================================
echo.

php artisan serve --port=8000
pause
