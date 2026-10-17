@echo off
title Restart Apache Service
color 0a

:: Check for administrative rights
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ===============================================================
    echo [ERROR] Administrative permissions required!
    echo.
    echo Please right-click this file and select "Run as administrator".
    echo ===============================================================
    echo.
    pause
    exit /b 1
)

echo Restarting Apache service (wampapache64)...
net stop wampapache64
net start wampapache64

echo.
echo ===============================================================
echo Done! Apache has reloaded its configuration.
echo "Require all granted" is now active for all network devices!
echo ===============================================================
echo.
pause
