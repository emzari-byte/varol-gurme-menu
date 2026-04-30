@echo off
setlocal

set "TASK_NAME=VarolGurmeAkinsoftBridge"
set "BRIDGE_DIR=%~dp0"
set "PHP_EXE="

if exist "%BRIDGE_DIR%php\php.exe" set "PHP_EXE=%BRIDGE_DIR%php\php.exe"
if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if "%PHP_EXE%"=="" for %%P in (php.exe) do set "PHP_EXE=%%~$PATH:P"

if "%PHP_EXE%"=="" (
  echo PHP bulunamadi. Bu PC'ye PHP CLI kurulmasi gerekiyor.
  pause
  exit /b 1
)

if not exist "%BRIDGE_DIR%akinsoft_bridge_config.php" (
  echo akinsoft_bridge_config.php bulunamadi.
  echo Once bridge ayar dosyasini olusturun.
  pause
  exit /b 1
)

echo Bridge kontrol ediliyor...
"%PHP_EXE%" "%BRIDGE_DIR%akinsoft_bridge_agent.php" --check
if errorlevel 1 (
  echo Kontrol sirasinda hata olustu.
  pause
  exit /b 1
)

echo.
echo Windows Gorev Zamanlayici gorevi kuruluyor: %TASK_NAME%
schtasks /Create /TN "%TASK_NAME%" /SC ONSTART /TR "\"%PHP_EXE%\" \"%BRIDGE_DIR%akinsoft_bridge_agent.php\"" /RU SYSTEM /RL HIGHEST /F
if errorlevel 1 (
  echo Gorev olusturulamadi. Bu dosyayi Yonetici olarak calistirin.
  pause
  exit /b 1
)

schtasks /Run /TN "%TASK_NAME%"

echo.
echo Kurulum tamamlandi. Bridge arka planda calisacak.
echo Log dosyasi: %BRIDGE_DIR%bridge_agent.log
pause
