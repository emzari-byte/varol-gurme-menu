@echo off
setlocal

set "TASK_NAME=VarolGurmeAkinsoftBridge"

echo Bridge gorevi yeniden baslatiliyor: %TASK_NAME%
schtasks /End /TN "%TASK_NAME%" >nul 2>&1
timeout /t 2 /nobreak >nul
schtasks /Run /TN "%TASK_NAME%"

echo.
echo Son log satirlari:
if exist "%~dp0bridge_agent.log" (
  powershell -NoProfile -Command "Get-Content -Path '%~dp0bridge_agent.log' -Tail 20"
) else (
  echo Log dosyasi henuz olusmadi.
)

pause
