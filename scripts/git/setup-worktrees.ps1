# Victorious MARKET -- Worktree & Identity Setup Script (PowerShell)
# Part of Victorious MARKET Multi-AI Engineering Control System Specification v3 (Sections 5.2, 12.1)

param(
    [string]$BaseDir = "..\VictoriousAI",
    [switch]$Force = $false
)

$ErrorActionPreference = "Stop"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Victorious MARKET -- Multi-AI Worktree Setup Engine       " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$CurrentRepo = (Get-Item .).FullName
Write-Host "[*] Source Repository Root: $CurrentRepo" -ForegroundColor Yellow

# Resolve absolute BaseDir
$TargetParent = [System.IO.Path]::GetFullPath((Join-Path $CurrentRepo $BaseDir))
Write-Host "[*] Target Worktree Directory: $TargetParent" -ForegroundColor Yellow

if (-not (Test-Path $TargetParent)) {
    New-Item -ItemType Directory -Path $TargetParent -Force | Out-Null
    Write-Host "[+] Created base directory: $TargetParent" -ForegroundColor Green
}

# Define the 8 roles plus MAIN reference checkout
$Roles = @(
    @{ Id = "AI-1"; Name = "AI-1-Backend";            Email = "ai1@local"; Role = "Backend, DB & API";          Port = 8001; Db = "vmarket_test_ai1" },
    @{ Id = "AI-2"; Name = "AI-2-Customer";           Email = "ai2@local"; Role = "Customer App & Web";         Port = 8002; Db = "vmarket_test_ai2" },
    @{ Id = "AI-3"; Name = "AI-3-Vendor";             Email = "ai3@local"; Role = "Vendor App & Web";           Port = 8003; Db = "vmarket_test_ai3" },
    @{ Id = "AI-4"; Name = "AI-4-Operations";         Email = "ai4@local"; Role = "Delivery App & Admin Web";   Port = 8004; Db = "vmarket_test_ai4" },
    @{ Id = "AI-5"; Name = "AI-5-Customer-Reviewer";  Email = "ai5@local"; Role = "Customer Domain Reviewer";   Port = 8005; Db = "vmarket_test_ai5" },
    @{ Id = "AI-6"; Name = "AI-6-Vendor-Reviewer";    Email = "ai6@local"; Role = "Vendor Domain Reviewer";     Port = 8006; Db = "vmarket_test_ai6" },
    @{ Id = "AI-7"; Name = "AI-7-Operations-Reviewer";Email = "ai7@local"; Role = "Operations Domain Reviewer"; Port = 8007; Db = "vmarket_test_ai7" },
    @{ Id = "AI-8"; Name = "AI-8-Coordinator";        Email = "ai8@local"; Role = "Lead Architect & Gatekeeper";Port = 8008; Db = "vmarket_test_ai8" }
)

foreach ($r in $Roles) {
    $WorktreePath = Join-Path $TargetParent $r.Id
    $BranchName = "$($r.Id.ToLower())/workspace"

    Write-Host "`n[*] Configuring Worktree: $($r.Id) ($($r.Role))..." -ForegroundColor Cyan

    if (Test-Path $WorktreePath) {
        Write-Host "    [!] Worktree directory already exists at $WorktreePath. Skipping git worktree add." -ForegroundColor Yellow
    } else {
        # Check if branch exists
        $branchExists = git branch --list $BranchName
        if ($branchExists) {
            git worktree add $WorktreePath $BranchName
        } else {
            git worktree add -b $BranchName $WorktreePath HEAD
        }
        Write-Host "    [+] Added Git worktree at $WorktreePath" -ForegroundColor Green
    }

    # Configure per-worktree git identity
    Push-Location $WorktreePath
    try {
        git config extensions.worktreeConfig true
        git config --worktree user.name "$($r.Name)"
        git config --worktree user.email "$($r.Email)"
        git config --worktree vmarket.role "$($r.Id)"
        Write-Host "    [+] Git identity configured: $($r.Name) <$($r.Email)>" -ForegroundColor Gray

        # Create isolated .env.ai environment file
        $EnvAiPath = Join-Path $WorktreePath "backend\vmarket-web\.env.ai"
        $WorktreeDb = (Join-Path $WorktreePath "backend\vmarket-web\database\database_$($r.Id.ToLower()).sqlite").Replace("\", "/")
        $EnvContent = @"
APP_NAME="Victorious MARKET ($($r.Id))"
APP_ENV=testing
APP_KEY=base64:1vaU3dfc+sWjx8TuDXzginRsEa2dp2SBL+Ujs6QCb5c=
APP_DEBUG=true
APP_URL=http://127.0.0.1:$($r.Port)

DB_CONNECTION=sqlite
DB_DATABASE="$WorktreeDb"

CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

VMARKET_AI_ROLE=$($r.Id)
VMARKET_WORKTREE_PORT=$($r.Port)
"@
        if (Test-Path (Join-Path $WorktreePath "backend\vmarket-web")) {
            Set-Content -Path $EnvAiPath -Value $EnvContent -Encoding UTF8
            $EnvFile = Join-Path $WorktreePath "backend\vmarket-web\.env"
            Copy-Item -Path $EnvAiPath -Destination $EnvFile -Force

            # Seed isolated sqlite db from main repo if available
            $MainDb = Join-Path $CurrentRepo "backend\vmarket-web\database\database.sqlite"
            $WorktreeDbDest = Join-Path $WorktreePath "backend\vmarket-web\database\database_$($r.Id.ToLower()).sqlite"
            if ((Test-Path $MainDb) -and -not (Test-Path $WorktreeDbDest)) {
                Copy-Item -Path $MainDb -Destination $WorktreeDbDest -Force
                Write-Host "    [+] Seeded isolated SQLite database at $WorktreeDbDest" -ForegroundColor Gray
            }
            Write-Host "    [+] Created isolated environment config at $EnvAiPath" -ForegroundColor Gray

            # Junction shared vendor and storage
            $TargetVendor = Join-Path $CurrentRepo "backend\vmarket-web\vendor"
            $WorktreeVendor = Join-Path $WorktreePath "backend\vmarket-web\vendor"
            if ((Test-Path $TargetVendor) -and -not (Test-Path $WorktreeVendor)) {
                New-Item -ItemType Junction -Path $WorktreeVendor -Target $TargetVendor -Force | Out-Null
                Write-Host "    [+] Linked shared vendor junction" -ForegroundColor Gray
            }
            $TargetStorage = Join-Path $CurrentRepo "backend\vmarket-web\storage"
            $WorktreeStorage = Join-Path $WorktreePath "backend\vmarket-web\storage"
            if ((Test-Path $TargetStorage) -and -not (Test-Path $WorktreeStorage)) {
                New-Item -ItemType Junction -Path $WorktreeStorage -Target $TargetStorage -Force | Out-Null
                Write-Host "    [+] Linked shared storage junction" -ForegroundColor Gray
            }
        }
    } finally {
        Pop-Location
    }
}

Write-Host "`n[OK] All 8 AI worktrees successfully configured!" -ForegroundColor Green
Write-Host "Location: $TargetParent" -ForegroundColor Green

