@echo off
title Fix WampServer Port 80 Conflict (IIS)
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

echo.
echo [1/3] Stopping IIS (World Wide Web Publishing Service)...
net stop w3svc

echo.
echo [2/3] Disabling IIS from starting automatically...
sc config w3svc start= disabled

echo.
echo [3/3] Starting WampServer Apache service (wampapache64)...
net stop wampapache64 >nul 2>&1
net start wampapache64

echo.
echo ===============================================================
echo Success! Port 80 has been freed for WampServer.
echo The WampServer icon in your system tray should now turn GREEN.
echo.
echo You can now open your browser and visit:
echo    http://localhost
echo ===============================================================
echo.
pause
