@echo off
setlocal
title Cybersecurity Transfer Setup
echo Cybersecurity Transfer Setup
echo Copy the entire Cybersecurity folder to C:\xampp\htdocs first.
echo Start MySQL in the XAMPP Control Panel before continuing.
echo Existing accounts and records will be preserved.
echo.
set "CYBER_PHP=%~dp0..\..\php\php.exe"
if not exist "%CYBER_PHP%" set "CYBER_PHP=C:\xampp\php\php.exe"
if not exist "%CYBER_PHP%" (
    echo ERROR: XAMPP PHP was not found.
    echo Place this project inside your XAMPP htdocs folder.
    pause
    exit /b 1
)
"%CYBER_PHP%" "%~dp0includes\setup-database.php"
if errorlevel 1 (
    echo.
    echo Setup failed. Read the message above, fix the issue, and run this file again.
    pause
    exit /b 1
)
echo.
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0includes\setup-mail-worker.ps1" -PhpPath "%CYBER_PHP%"
if errorlevel 1 (
    echo Database setup succeeded, but the automatic email task could not be installed.
    echo Run this batch file as Administrator to retry the task setup.
)
echo.
echo Gmail Alerts: enter the Google app password for alimaomadelyn@gmail.com.
echo Enable Automatic notifications and save the connection.
echo Open http://localhost/Cybersecurity/ after starting Apache.
pause
exit /b 0
