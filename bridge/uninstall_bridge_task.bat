@echo off
setlocal

set "TASK_NAME=VarolGurmeAkinsoftBridge"

schtasks /End /TN "%TASK_NAME%" >nul 2>nul
schtasks /Delete /TN "%TASK_NAME%" /F

echo Bridge gorevi kaldirildi.
pause
