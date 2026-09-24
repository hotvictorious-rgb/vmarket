# Victorious MARKET -- Path Scope & Secret Pre-Commit Validator (PowerShell)
# Part of Victorious MARKET Multi-AI Engineering Control System Specification v3 (Sections 4.1, 4.2, 23, 24)

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

    # Check 3: Role-specific path scoping
    switch ($Role) {
        "AI-1" {
            # Backend AI: cannot touch Flutter apps
            if ($file -match "^User app/" -or $file -match "^Vendor app/" -or $file -match "^Delivery Man App/") {
                $Violations += "AI-1 is FORBIDDEN from touching Flutter applications: $file"
            }
        }
        "AI-2" {
            # Customer AI: cannot touch backend or other apps
            if ($file -match "^backend/" -or $file -match "^Vendor app/" -or $file -match "^Delivery Man App/") {
                $Violations += "AI-2 is FORBIDDEN from touching backend or other applications: $file"
            }
        }
        "AI-3" {
            # Vendor AI: cannot touch Customer/Delivery apps or core backend
            if ($file -match "^User app/" -or $file -match "^Delivery Man App/" -or $file -match "^backend/vmarket-web/app/") {
                $Violations += "AI-3 is FORBIDDEN from touching Customer/Delivery apps or backend logic: $file"
            }
        }
        "AI-4" {
            # Delivery AI: cannot touch Customer/Vendor apps or core backend
            if ($file -match "^User app/" -or $file -match "^Vendor app/" -or $file -match "^backend/vmarket-web/app/") {
                $Violations += "AI-4 is FORBIDDEN from touching Customer/Vendor apps or backend logic: $file"
            }
        }
        "AI-5" {
            # Reviewer: CANNOT touch application code or tests
            if ($file -notmatch "^\.ai/reviews/customer/" -and $file -notmatch "^\.ai/tickets/") {
                $Violations += "AI-5 (Reviewer) is FORBIDDEN from modifying non-review files: $file"
            }
        }
        "AI-6" {
            # Reviewer: CANNOT touch application code or tests
            if ($file -notmatch "^\.ai/reviews/vendor/" -and $file -notmatch "^\.ai/tickets/") {
                $Violations += "AI-6 (Reviewer) is FORBIDDEN from modifying non-review files: $file"
            }
        }
        "AI-7" {
            # Reviewer: CANNOT touch application code or tests
            if ($file -notmatch "^\.ai/reviews/operations/" -and $file -notmatch "^\.ai/tickets/") {
                $Violations += "AI-7 (Reviewer) is FORBIDDEN from modifying non-review files: $file"
            }
        }
        "AI-8" {
            # Coordinator: CANNOT touch application code or tests
            if ($file -notmatch "^\.ai/tickets/" -and $file -notmatch "^\.ai/decisions/" -and $file -notmatch "^\.ai/releases/" -and $file -notmatch "^\.ai/incidents/") {
                $Violations += "AI-8 is FORBIDDEN from modifying code or unauthorized metadata: $file"
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
