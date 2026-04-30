@echo off
setlocal

set "TASK_NAME=VarolGurmeAkinsoftBridge"

schtasks /Query /TN "%TASK_NAME%" /V /FO LIST
echo.
echo Son log satirlari:
if exist "%~dp0bridge_agent.log" (
  powershell -NoProfile -Command "Get-Content -LiteralPath '%~dp0bridge_agent.log' -Tail 30"
) else (
  echo Log dosyasi henuz olusmadi.
)
pause
