# Victorious MARKET -- Documentation Parity Validator (PowerShell)
# Part of Victorious MARKET Multi-AI Engineering Control System Specification v3 (Section 28.2)

$ErrorActionPreference = "Stop"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Victorious MARKET -- Documentation Parity Validator       " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$Errors = @()

# Check ARCHITECTURE.md exists and has a date
$ArchFile = ".ai\ARCHITECTURE.md"
if (-not (Test-Path $ArchFile)) {
    $Errors += "Missing .ai/ARCHITECTURE.md"
} else {
    $content = Get-Content -Path $ArchFile -Raw
    if ($content -notmatch "Date:" -and $content -notmatch "Last Updated:") {
        $Errors += ".ai/ARCHITECTURE.md is missing a 'Last Updated' or 'Date' timestamp"
    }
}

# Check API_CONTRACT.md exists and is non-empty
$ApiContractFile = ".ai/API_CONTRACT.md"
if (-not (Test-Path $ApiContractFile)) {
    $Errors += "Missing .ai/API_CONTRACT.md"
}

# Check SCOPE.md exists and contains systems register
$ScopeFile = ".ai/SCOPE.md"
if (-not (Test-Path $ScopeFile)) {
    $Errors += "Missing .ai/SCOPE.md"
}

# Check GATED_AREAS.md exists
$GatedFile = ".ai/GATED_AREAS.md"
if (-not (Test-Path $GatedFile)) {
    $Errors += "Missing .ai/GATED_AREAS.md"
}

# Check LEGACY_DEBT.md exists
$DebtFile = ".ai/LEGACY_DEBT.md"
if (-not (Test-Path $DebtFile)) {
    $Errors += "Missing .ai/LEGACY_DEBT.md"
}

# Check DATA_INVENTORY.md exists
$DataFile = ".ai/DATA_INVENTORY.md"
if (-not (Test-Path $DataFile)) {
    $Errors += "Missing .ai/DATA_INVENTORY.md"
}

if ($Errors.Count -gt 0) {
    Write-Host "`n[FAIL] DOCUMENTATION VALIDATION FAILED:" -ForegroundColor Red
    foreach ($e in $Errors) {
        Write-Host "    - $e" -ForegroundColor Red
    }
    exit 1
}

Write-Host "[OK] All authoritative documentation files present and verified." -ForegroundColor Green
exit 0

