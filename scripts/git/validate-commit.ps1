# Victorious MARKET -- Path Scope & Secret Pre-Commit Validator (PowerShell)
# Part of Victorious MARKET 3-AI Control System (Human -> Reviewer -> Backend/Frontend -> Reviewer pushes)

param(
    [string]$StagedFilesList = "",
    [string]$RoleOverride = ""
)

$ErrorActionPreference = "Stop"

# Determine Agent Role
$Role = $RoleOverride
if ([string]::IsNullOrWhiteSpace($Role)) {
    $Role = git config vmarket.role
    if ([string]::IsNullOrWhiteSpace($Role)) {
        $UserName = git config user.name
        if ($UserName -match "AI-(\d)") {
            $Role = "AI-$($Matches[1])"
        } else {
            $Role = "HUMAN"
        }
    }
}

Write-Host "[*] Evaluating pre-commit boundaries for committer role: $Role" -ForegroundColor Cyan

# Fetch staged files
if ([string]::IsNullOrWhiteSpace($StagedFilesList)) {
    $StagedFiles = git --no-pager diff --cached --name-only
} else {
    $StagedFiles = $StagedFilesList -split "`r?`n" | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }
}

if (-not $StagedFiles) {
    Write-Host "[OK] No staged files to evaluate." -ForegroundColor Green
    exit 0
}

# Control Zone path patterns (Human-only authority)
$ControlZonePatterns = @(
    "^\.ai/BUSINESS_RULES\.md$",
    "^\.ai/DATABASE_RULES\.md$",
    "^\.ai/SECURITY_RULES\.md$",
    "^\.ai/TESTING_RULES\.md$",
    "^\.ai/RELEASE_RULES\.md$",
    "^\.ai/DESIGN_RULES\.md$",
    "^\.ai/LOCALIZATION\.md$",
    "^\.ai/SCOPE\.md$",
    "^\.ai/GATED_AREAS\.md$",
    "^\.ai/LEGACY_DEBT\.md$",
    "^\.ai/DATA_INVENTORY\.md$",
    "^\.ai/agents/",
    "^\.ai/templates/",
    "^\.ai/schemas/",
    "^\.ai/runbooks/",
    "^scripts/",
    "^\.github/",
    "^CODEOWNERS$"
)

# Test results path (Runner scripts only)
$ResultPattern = "^\.ai/status/results/"

$Violations = @()

foreach ($file in $StagedFiles) {
    # Check 1: Test results write protection (No AI can write by hand)
    if ($file -match $ResultPattern -and $Role -ne "RUNNER" -and $Role -ne "HUMAN") {
        $Violations += "Role $Role is FORBIDDEN from writing to test result directory: $file (Runner scripts only)"
    }

    # Check 2: Control Zone protection
    $isControlZone = $false
    foreach ($czPattern in $ControlZonePatterns) {
        if ($file -match $czPattern) {
            $isControlZone = $true
            break
        }
    }

    if ($isControlZone -and $Role -ne "HUMAN") {
        $Violations += "Role $Role is FORBIDDEN from modifying Control Zone file: $file (Human-only authority)"
    }

    # Check 3: Role-specific path scoping (3-AI Control System: BACKEND_AI, FRONTEND_AI, REVIEWER_AI)
    $IsMergeCommit = $false
    try { git rev-parse --verify MERGE_HEAD 2>$null | Out-Null; if ($?) { $IsMergeCommit = $true } } catch { }
    $AppCodePattern = "^(backend/|User app/|Vendor app/|Delivery Man App/|tests/)"
    switch ($Role) {
        "BACKEND_AI" {
            # Backend AI: PHP logic only — cannot touch Flutter apps, Blade views, or theme assets
            if ($file -match "^User app/" -or $file -match "^Vendor app/" -or $file -match "^Delivery Man App/" -or $file -match "^backend/vmarket-web/resources/views/" -or $file -match "^backend/vmarket-web/public/") {
                $Violations += "BACKEND_AI is FORBIDDEN from touching Frontend paths (Flutter, Blade, theme assets): $file"
            }
            if ($file -match "^(main$|.*frontend/)" -or $file -match "\.ai/reviews/") {
                $Violations += "BACKEND_AI is FORBIDDEN from merging, touching main, or writing reviews: $file"
            }
        }
        "FRONTEND_AI" {
            # Frontend AI: all UI only — cannot touch backend PHP logic
            if ($file -match "^backend/vmarket-web/app/" -or $file -match "^backend/vmarket-web/routes/" -or $file -match "^backend/vmarket-web/config/" -or $file -match "^backend/vmarket-web/database/") {
                $Violations += "FRONTEND_AI is FORBIDDEN from touching Backend PHP logic: $file"
            }
            if ($file -match "\.ai/reviews/") {
                $Violations += "FRONTEND_AI is FORBIDDEN from writing reviews: $file"
            }
        }
        "REVIEWER_AI" {
            # Reviewer AI: tickets, reviews, drafts only. Implementation code ONLY inside a true
            # merge commit executed via scripts/release/merge-release (integration, never content edits).
            $reviewerAllowed = $file -match "^\.ai/reviews/" -or $file -match "^\.ai/tickets/" -or $file -match "^\.ai/decisions/" -or $file -match "^\.ai/releases/" -or $file -match "^\.ai/incidents/" -or $file -match "^AI_CHANGELOG\.md$"
            if (-not $reviewerAllowed) {
                if ($file -match $AppCodePattern -and $IsMergeCommit) {
                    # Allowed: gate-passed integration merge to main via the release script.
                } else {
                    $Violations += "REVIEWER_AI is FORBIDDEN from modifying implementation code outside a release-script merge: $file (never write code; dispatch exact-prompt work orders instead)"
                }
            }
        }
    }
}

# Check 4: Secret Scanning in Staged Diffs
if ($StagedFiles.Count -gt 0) {
    $StagedDiff = git --no-pager diff --cached -- $StagedFiles
} else {
    $StagedDiff = ""
}
$SecretPatterns = @(
    "AKIA[0-9A-Z]{16}",
    "sk_live_[0-9a-zA-Z]{24}",
    "sk_test_[0-9a-zA-Z]{24}",
    "ghp_[0-9a-zA-Z]{36}",
    "-----BEGIN (RSA|EC|OPENSSH) PRIVATE KEY-----"
)

foreach ($pattern in $SecretPatterns) {
    if ($StagedDiff -match $pattern) {
        $Violations += "CRITICAL: Potential secret or private key pattern detected matching '$pattern' in staged changes!"
    }
}

# Report and Exit
if ($Violations.Count -gt 0) {
    Write-Host "`n[FAIL] PRE-COMMIT POLICY VIOLATION! Commit rejected:" -ForegroundColor Red
    foreach ($v in $Violations) {
        Write-Host "    - $v" -ForegroundColor Red
    }
    Write-Host "`nTo resolve: unstage the forbidden files with 'git restore --staged filename' or request assignment from the human operator." -ForegroundColor Yellow
    exit 1
}

Write-Host "[OK] Pre-commit scope and secret verification PASSED for role: $Role" -ForegroundColor Green
exit 0
