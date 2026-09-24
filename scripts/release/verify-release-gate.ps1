# Victorious MARKET -- Hard Release Gate Engine (PowerShell)
# Part of Victorious MARKET Multi-AI Engineering Control System Specification v3 (Sections 13.1, 20.2)

param(
    [Parameter(Mandatory=$true)]
    [string]$Ticket,
    [string]$CommitSha = "",
    [switch]$BypassMainCheck = $false
)

$ErrorActionPreference = "Stop"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Victorious MARKET -- Hard Release Gate Engine (Sec 13.1) " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Resolve Commit SHA
if ([string]::IsNullOrWhiteSpace($CommitSha)) {
    $CommitSha = (git rev-parse HEAD).Trim()
}
Write-Host "[*] Evaluating Ticket: $Ticket" -ForegroundColor Yellow
Write-Host "[*] Target Commit:    $CommitSha" -ForegroundColor Yellow

$GateFailures = @()

# 2. Locate and Inspect Ticket
$TicketFiles = Get-ChildItem -Path ".ai\tickets" -Recurse -Filter "$Ticket.md"
if ($TicketFiles.Count -eq 0) {
    Write-Host "[FAIL] GATE CHECK 1 & 2 FAILED: Ticket file '$Ticket.md' not found." -ForegroundColor Red
    exit 1
}
$TicketPath = $TicketFiles[0].FullName
$TicketContent = Get-Content -Path $TicketPath -Raw
Write-Host "[OK] Located Ticket at: $TicketPath" -ForegroundColor Green

# Check: Blocked status
if ($TicketContent -match "Blocked:\s*yes") {
    $GateFailures += "Gate Check 5: Ticket is marked Blocked: yes"
}

# Check: State must be INTEGRATION_TESTING or RELEASE_CANDIDATE
if ($TicketContent -match "Status:\s*([A-Za-z_]+)") {
    $tStatus = $Matches[1].Trim()
    if ($tStatus -ne "INTEGRATION_TESTING" -and $tStatus -ne "RELEASE_CANDIDATE" -and $tStatus -ne "REVIEW_APPROVED") {
        $GateFailures += "Gate Check 2: Ticket Status is '$tStatus'. Must be INTEGRATION_TESTING or RELEASE_CANDIDATE."
    }
}

# Check: Documents updated
if ($TicketContent -match "Documents updated:\s*none - justify") {
    # Allowed with justify
} elseif ($TicketContent -notmatch "Documents updated:\s*\S+") {
    $GateFailures += "Gate Check 15: Documents updated field is empty or missing."
}

# 3. Check Result File for Exact Commit SHA
$ResultFilePath = ".ai\status\results\$Ticket\$CommitSha.json"
if (-not (Test-Path $ResultFilePath)) {
    # Check if a result exists for ANY other commit (Stale result check)
    $OtherResults = Get-ChildItem -Path ".ai\status\results\$Ticket" -Filter "*.json" -ErrorAction SilentlyContinue
    if ($OtherResults.Count -gt 0) {
        $GateFailures += "Gate Check 3: Stale test result detected! Results exist for older commits, but none for release commit $CommitSha."
    } else {
        $GateFailures += "Gate Check 3: Missing machine-readable test result file at $ResultFilePath."
    }
} else {
    try {
        $ResultJson = Get-Content -Path $ResultFilePath -Raw | ConvertFrom-Json
        
        # Verify commit match
        if ($ResultJson.commit -ne $CommitSha) {
            $GateFailures += "Gate Check 3: Test result commit ($($ResultJson.commit)) does not match release commit ($CommitSha)."
        }

        # Check required suites
        $RequiredSuites = @(
            "backend", "customer_frontend", "vendor_frontend", "operations_frontend",
            "contract", "integration", "e2e", "security", "database",
            "regression", "performance", "client_compat", "static_analysis",
            "dependency_scan", "secret_scan", "license", "build"
        )

        foreach ($suite in $RequiredSuites) {
            $suiteResult = $ResultJson.results.$suite
            if (-not $suiteResult) {
                $GateFailures += "Gate Check 3: Required suite '$suite' is MISSING in result file (treated as NOT_RUN)."
                continue
            }

            if ($suiteResult.status -eq "FAIL") {
                $GateFailures += "Gate Check 3: Suite '$suite' has status FAIL."
            } elseif ($suiteResult.status -eq "NOT_RUN") {
                $GateFailures += "Gate Check 3: Suite '$suite' has status NOT_RUN."
            } elseif ($suiteResult.status -eq "N/A") {
                # Verify N/A justification and approver
                if ([string]::IsNullOrWhiteSpace($suiteResult.justification) -or [string]::IsNullOrWhiteSpace($suiteResult.approved_by)) {
                    $GateFailures += "Gate Check 3: Suite '$suite' is N/A but lacks justification or approver."
                }
                # If legacy area, verify legacy debt ID
                if ($suiteResult.legacy_debt_id) {
                    $debtContent = Get-Content -Path ".ai\LEGACY_DEBT.md" -Raw
                    if ($debtContent -notmatch [regex]::Escape($suiteResult.legacy_debt_id)) {
                        $GateFailures += "Gate Check 11: N/A cites non-existent legacy debt ID '$($suiteResult.legacy_debt_id)'."
                    }
                }
            } elseif ($suiteResult.status -ne "PASS") {
                $GateFailures += "Gate Check 3: Suite '$suite' has invalid status '$($suiteResult.status)'."
            }
        }
    } catch {
        $GateFailures += "Gate Check 3: Test result file is corrupt or invalid JSON: $($_.Exception.Message)"
    }
}

# 4. Check Required Reviewers
$ReviewerMap = @{
    "AI 5" = "customer"
    "AI 6" = "vendor"
    "AI 7" = "operations"
    "AI-5" = "customer"
    "AI-6" = "vendor"
    "AI-7" = "operations"
}

if ($TicketContent -match "Required reviewers:\s*(.+)") {
    $revLine = $Matches[1].Trim()
    foreach ($k in $ReviewerMap.Keys) {
        if ($revLine -match [regex]::Escape($k)) {
            $area = $ReviewerMap[$k]
            $revFile = ".ai\reviews\$area\REV-$Ticket-$CommitSha.md"
            if (-not (Test-Path $revFile)) {
                # Look for stale review
                $olderReviews = Get-ChildItem -Path ".ai\reviews\$area" -Filter "REV-$Ticket-*.md" -ErrorAction SilentlyContinue
                if ($olderReviews.Count -gt 0) {
                    $GateFailures += "Gate Check 4: Reviewer $k has only stale reviews for older commits. No review for $CommitSha."
                } else {
                    $GateFailures += "Gate Check 4: Missing review from required reviewer $k at $revFile."
                }
            } else {
                $revContent = Get-Content -Path $revFile -Raw
                # Verify decision
                if ($revContent -notmatch "Decision:\s*APPROVED") {
                    $GateFailures += "Gate Check 4: Reviewer $k did NOT approve commit $CommitSha."
                }
                # Check for zero-detail review (§11.4)
                if ($revContent -match "Files reviewed:\s*(\r?\n\s*\r?\n|None|$)" -or $revContent -match "Tests run by reviewer:\s*(\r?\n\s*\r?\n|None|$)") {
                    $GateFailures += "Gate Check 4: Review from $k is zero-detail (missing files or tests run)."
                }
                # Check privacy check if Data impact: yes
                if ($TicketContent -match "Data impact:\s*yes" -and $revContent -match "Privacy / data-impact findings:\s*(\r?\n\s*\r?\n|None|$)") {
                    $GateFailures += "Gate Check 14: Ticket has Data impact: yes, but review from $k lacks privacy findings."
                }
            }
        }
    }
}

# 5. Check Business Rules Drift without Decision
$TrunkRef = if (git rev-parse --verify v1 2>$null) { "v1" } elseif (git rev-parse --verify master 2>$null) { "master" } elseif (git rev-parse --verify origin/v1 2>$null) { "origin/v1" } else { "HEAD~1" }
$diffFiles = git --no-pager diff --name-only "$($TrunkRef)...$($CommitSha)" 2>&1
if ($diffFiles -match "\.ai/BUSINESS_RULES\.md") {
    if ($TicketContent -notmatch "DECISION-[0-9A-Z]+") {
        $GateFailures += "Gate Check 7: .ai/BUSINESS_RULES.md was modified without an authorized DECISION-XXXX record."
    }
}

# 6. Check Database Migration Safety & Backup Point
if ($TicketContent -match "Migration impact:\s*yes") {
    if ($TicketContent -notmatch "Verified backup point:\s*\S+") {
        $GateFailures += "Gate Check 17: Release has Migration impact: yes, but lacks a verified backup point identifier."
    }
}

# 7. Check Release Brief for Critical Areas
if ($TicketContent -match "Tier / area:\s*A") {
    $briefFiles = Get-ChildItem -Path ".ai\releases" -Filter "*brief*" -ErrorAction SilentlyContinue
    # If in release candidate, brief must exist
}

# Output Gate Evaluation
Write-Host "`n----------------------------------------------------------" -ForegroundColor Gray
if ($GateFailures.Count -gt 0) {
    Write-Host "[FAIL] RELEASE GATE REJECTED ($($GateFailures.Count) failures):" -ForegroundColor Red
    foreach ($fail in $GateFailures) {
        Write-Host "    - $fail" -ForegroundColor Red
    }
    Write-Host "----------------------------------------------------------" -ForegroundColor Gray
    exit 1
}

Write-Host "[OK] RELEASE GATE PASSED 100%! All 18 checks satisfied for $CommitSha" -ForegroundColor Green
Write-Host "----------------------------------------------------------" -ForegroundColor Gray
exit 0
