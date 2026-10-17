@echo off
title Configure Static IP 10.10.10.1 + Gateway 10.10.10.254
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
echo ===============================================================
echo Configuring Ethernet Port:
echo   IP Address:      10.10.10.1
echo   Subnet Mask:     255.255.255.0
echo   Default Gateway: 10.10.10.254 (MikroTik Router ether10)
echo   DNS Servers:     10.10.10.254, 8.8.8.8
echo ===============================================================
echo.

:: Configure Ethernet 3 (Active adapter)
echo [1/4] Setting IP and Gateway on "Ethernet 3"...
netsh interface ipv4 set address name="Ethernet 3" static 10.10.10.1 255.255.255.0 10.10.10.254 1

echo [2/4] Setting DNS servers on "Ethernet 3"...
netsh interface ipv4 set dns name="Ethernet 3" static 10.10.10.254
netsh interface ipv4 add dns name="Ethernet 3" 8.8.8.8 index=2

:: Also configure Ethernet 2 (Onboard port) in case it is used
echo [3/4] Configuring "Ethernet 2" if present...
netsh interface ipv4 set address name="Ethernet 2" static 10.10.10.1 255.255.255.0 10.10.10.254 1 >nul 2>&1
netsh interface ipv4 set dns name="Ethernet 2" static 10.10.10.254 >nul 2>&1
netsh interface ipv4 add dns name="Ethernet 2" 8.8.8.8 index=2 >nul 2>&1

echo.
echo ===============================================================
echo Current IP Configuration on Ethernet 3:
netsh interface ipv4 show addresses "Ethernet 3"
echo.
netsh interface ipv4 show dns "Ethernet 3"
echo ===============================================================
echo.
echo SUCCESS! Your static IP and Gateway have been configured.
echo The server now has a route to the internet via 10.10.10.254.
echo.
pause
