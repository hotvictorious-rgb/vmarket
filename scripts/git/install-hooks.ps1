# Victorious MARKET -- Git Pre-Commit Hook Installer (PowerShell)
# Part of Victorious MARKET Multi-AI Engineering Control System Specification v3 (Section 4.2)

$ErrorActionPreference = "Stop"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Victorious MARKET -- Git Hook Installer                  " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$HooksDir = ".git\hooks"
if (-not (Test-Path $HooksDir)) {
    Write-Host "[!] .git/hooks directory not found in current directory. Ensure you run this from repo root." -ForegroundColor Red
    exit 1
}

$PreCommitPath = Join-Path $HooksDir "pre-commit"

# Shell wrapper calling PowerShell validator
$HookScript = @"
#!/bin/sh
# Victorious MARKET Multi-AI Control System Pre-Commit Hook
powershell.exe -ExecutionPolicy Bypass -NoProfile -File scripts/git/validate-commit.ps1
exit `$?
"@

Set-Content -Path $PreCommitPath -Value $HookScript -Encoding ASCII
Write-Host "[OK] Successfully installed pre-commit hook at $PreCommitPath" -ForegroundColor Green

