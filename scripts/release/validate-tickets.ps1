# Victorious MARKET -- Ticket Lifecycle & Metadata Validator (PowerShell)
# Part of Victorious MARKET Multi-AI Engineering Control System Specification v3 (Sections 7.1, 14.2, 22)

$ErrorActionPreference = "Stop"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Victorious MARKET -- Ticket Governance & Parity Validator " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$TicketsRoot = ".ai\tickets"
if (-not (Test-Path $TicketsRoot)) {
    Write-Host "[FAIL] Tickets directory not found: $TicketsRoot" -ForegroundColor Red
    exit 1
}

$FolderStateMap = @{
    "backlog"          = @("BACKLOG")
    "ready"            = @("READY")
    "in-progress"      = @("ASSIGNED", "IN_PROGRESS", "IMPLEMENTED", "SELF_CHECKED")
    "review"           = @("UNDER_REVIEW")
    "changes-required" = @("CHANGES_REQUIRED")
    "approved"         = @("REVIEW_APPROVED", "INTEGRATION_TESTING", "RELEASE_CANDIDATE")
    "released"         = @("RELEASED")
    "cancelled"        = @("CANCELLED")
}

$RequiredFields = @(
    "Ticket ID:",
    "Title:",
    "Type:",
    "Status:",
    "Blocked:",
    "Tier / area:",
    "Assigned AI:",
    "Required reviewers:",
    "Branch / base commit:",
    "Acceptance criteria:"
)

$TotalChecked = 0
$Errors = @()

$TicketFiles = Get-ChildItem -Path $TicketsRoot -Recurse -Filter "*.md" | Where-Object { $_.Name -ne "README.md" }

foreach ($file in $TicketFiles) {
    $TotalChecked++
    $Folder = $file.Directory.Name.ToLower()
    $Content = Get-Content -Path $file.FullName -Raw

    # 1. Check folder state parity
    if ($FolderStateMap.ContainsKey($Folder)) {
        $AllowedStates = $FolderStateMap[$Folder]
        if ($Content -match "Status:\s*([A-Za-z_]+)") {
            $TicketStatus = $Matches[1].Trim()
            if ($AllowedStates -notcontains $TicketStatus) {
                $Errors += "State mismatch in $($file.Name): Folder is '$Folder' but Status is '$TicketStatus'. Allowed states: $($AllowedStates -join ', ')"
            }
        } else {
            $Errors += "Missing 'Status:' field in $($file.FullName)"
        }
    }

    # 2. Check required fields
    foreach ($field in $RequiredFields) {
        if ($Content -notmatch [regex]::Escape($field)) {
            $Errors += "Missing mandatory field '$field' in $($file.FullName)"
        }
    }

    # 3. Check cycle counters & escalation trigger (Section 22.2)
    if ($Content -match "review_cycles:\s*\{([^}]+)\}") {
        $cyclesStr = $Matches[1]
        $matches = [regex]::Matches($cyclesStr, "(\w+):\s*(\d+)")
        foreach ($m in $matches) {
            $reviewer = $m.Groups[1].Value
            $count = [int]$m.Groups[2].Value
            if ($count -gt 3) {
                # Must be escalated
                if ($Content -notmatch "Blocked:\s*yes\s*\(ESCALATED\)") {
                    $Errors += "Escalation breach in $($file.Name): Review cycles for $reviewer is $count (> 3), but ticket is not marked 'Blocked: yes (ESCALATED)'"
                }
            }
        }
    }
}

Write-Host "[*] Total tickets validated: $TotalChecked" -ForegroundColor Yellow

if ($Errors.Count -gt 0) {
    Write-Host "`n[FAIL] TICKET VALIDATION FAILED ($($Errors.Count) errors):" -ForegroundColor Red
    foreach ($err in $Errors) {
        Write-Host "    - $err" -ForegroundColor Red
    }
    exit 1
}

Write-Host "[OK] All tickets passed folder parity, required fields, and cycle limit checks!" -ForegroundColor Green
exit 0

