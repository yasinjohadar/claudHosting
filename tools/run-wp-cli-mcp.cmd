@echo off
setlocal
set "TOOLS=%~dp0"
set "PATH=%TOOLS%;%PATH%"
npx -y wp-cli-mcp@latest
