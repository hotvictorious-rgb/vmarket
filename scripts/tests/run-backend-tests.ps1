# Victorious MARKET — Backend Test Runner (PowerShell)
# Part of Victorious MARKET Multi-AI Engineering Control System Specification v3 (§12, §19.4)

param(
    [string]$Filter = "",
    [string]$LogFile = ""
)

$ErrorActionPreference = "Stop"

# Set up PHP environment from Herd if present
$HerdPhp = "C:\Users\SOOQEL~1\.config\herd\bin\php84"
if (Test-Path $HerdPhp) {
    $env:PATH = "$HerdPhp;$env:PATH"
}

$BackendDir = "backend\vmarket-web"
if (-not (Test-Path $BackendDir)) {
    Write-Host "[X] Backend directory not found: $BackendDir" -ForegroundColor Red
    exit 1
}

Push-Location $BackendDir
try {
    Write-Host "[*] Executing Backend PHP Tests..." -ForegroundColor Cyan

    $PhpUnitCmd = "php"
    $PhpUnitArgs = @("artisan", "test")
    if (-not [string]::IsNullOrWhiteSpace($Filter)) {
        $PhpUnitArgs += @("--filter", $Filter)
    }

    if (-not [string]::IsNullOrWhiteSpace($LogFile)) {
        & $PhpUnitCmd $PhpUnitArgs 2>&1 | Tee-Object -FilePath $LogFile
        $exitCode = $LASTEXITCODE
    } else {
        & $PhpUnitCmd $PhpUnitArgs
        $exitCode = $LASTEXITCODE
    }

    if ($exitCode -ne 0) {
        Write-Host "[X] Backend tests failed with exit code $exitCode" -ForegroundColor Red
        exit $exitCode
    }

    Write-Host "[OK] Backend tests passed successfully." -ForegroundColor Green
    exit 0
} finally {
    Pop-Location
}

