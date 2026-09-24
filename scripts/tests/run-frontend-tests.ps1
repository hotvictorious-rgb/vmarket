# Victorious MARKET -- Frontend Test Runner (PowerShell)
# Part of Victorious MARKET Multi-AI Engineering Control System Specification v3 (Sections 12, 19.4)

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("customer", "vendor", "operations", "delivery")]
    [string]$App,
    [string]$LogFile = ""
)

$ErrorActionPreference = "Stop"

if ($App -eq "delivery") {
    $App = "operations"
}

$AppDirMap = @{
    "customer"   = "User app"
    "vendor"     = "Vendor app"
    "operations" = "Delivery Man App"
}

$TargetDir = $AppDirMap[$App]
if (-not (Test-Path $TargetDir)) {
    Write-Host "[FAIL] App directory not found: $TargetDir" -ForegroundColor Red
    exit 1
}

Push-Location $TargetDir
try {
    Write-Host "[*] Executing Flutter Tests for [$App] in $TargetDir..." -ForegroundColor Cyan

    $FlutterCmd = "flutter"
    $FlutterArgs = @("test")

    if (-not [string]::IsNullOrWhiteSpace($LogFile)) {
        & $FlutterCmd $FlutterArgs 2>&1 | Tee-Object -FilePath $LogFile
        $exitCode = $LASTEXITCODE
    } else {
        & $FlutterCmd $FlutterArgs
        $exitCode = $LASTEXITCODE
    }

    if ($exitCode -ne 0) {
        Write-Host "[FAIL] Frontend tests for $App failed with exit code $exitCode" -ForegroundColor Red
        exit $exitCode
    }

    Write-Host "[OK] Frontend tests for $App passed successfully." -ForegroundColor Green
    exit 0
} finally {
    Pop-Location
}
