@echo off
setlocal
cd /d "%~dp0"

set "PHP_EXE="

if exist "%~dp0php\php.exe" set "PHP_EXE=%~dp0php\php.exe"
if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if "%PHP_EXE%"=="" for %%P in (php.exe) do set "PHP_EXE=%%~$PATH:P"

if "%PHP_EXE%"=="" (
  echo PHP bulunamadi. XAMPP kuruluysa C:\xampp\php\php.exe yolunu kontrol edin.
  pause
  exit /b 1
)

"%PHP_EXE%" "%~dp0akinsoft_bridge_agent.php" --once
pause
