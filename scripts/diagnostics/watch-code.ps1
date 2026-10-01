# Victorious MARKET -- Real-Time File Diagnostic Watcher
# Continuously checks syntax, types, and AST on every file modification.

param(
    [string]$TargetDir = "."
)

$PhpPath = "C:\xamp\php\php.exe"
if (-not (Test-Path $PhpPath)) {
    $PhpPath = "C:\Users\SOOQ ELASER\.config\herd\bin\php84\php.exe"
}
$DartPath = "C:\src\flutter\bin\dart.bat"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " Victorious MARKET -- Real-Time Code Diagnostic Watcher   " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "[*] PHP Engine:   $PhpPath" -ForegroundColor DarkGray
Write-Host "[*] Dart Engine:  $DartPath" -ForegroundColor DarkGray
Write-Host "[*] Watching:     $((Resolve-Path $TargetDir).Path)" -ForegroundColor Green
Write-Host "[*] Press Ctrl+C in this terminal to stop watcher.`n" -ForegroundColor Yellow

$Watcher = New-Object System.IO.FileSystemWatcher
$Watcher.Path = (Resolve-Path $TargetDir).Path
$Watcher.IncludeSubdirectories = $true
$Watcher.EnableRaisingEvents = $true
$Watcher.NotifyFilter = [System.IO.NotifyFilters]::LastWrite -bor [System.IO.NotifyFilters]::FileName

$LastCheckTime = [ordered]@{}
$ThrottleMs = 300

$Action = {
    param($SourceObj, $EventDetails)
    $FilePath = $EventDetails.FullPath

    # If file was deleted or no longer exists, do not analyze
    if (-not (Test-Path $FilePath)) {
        return
    }

    $Ext = [System.IO.Path]::GetExtension($FilePath).ToLower()

    # Filter out temp/git/cache files
    if ($FilePath -match '(\\(\.git|\.idea|\.vscode|storage|cache|vendor|node_modules|build|\.dart_tool)\\)') {
        return
    }

    $Now = [DateTime]::UtcNow
    if ($LastCheckTime.Contains($FilePath)) {
        if (($Now - $LastCheckTime[$FilePath]).TotalMilliseconds -lt $ThrottleMs) {
            return
        }
    }
    $LastCheckTime[$FilePath] = $Now

    $RelPath = $FilePath.Replace($Watcher.Path, "").TrimStart('\/')

    if ($Ext -eq ".php") {
        if (Test-Path $PhpPath) {
            $Result = & $PhpPath -l $FilePath 2>&1
            if ($LASTEXITCODE -ne 0) {
                Write-Host "`n[SYNTAX ERROR] $RelPath" -ForegroundColor Red
                Write-Host "  $Result`n" -ForegroundColor Yellow
                [Console]::Beep(800, 200)
            }
            else {
                Write-Host "[OK PHP] $RelPath" -ForegroundColor Green
            }
        }
    }
    elseif ($Ext -eq ".ps1") {
        $tokens = $null
        $errors = $null
        [void][System.Management.Automation.Language.Parser]::ParseFile($FilePath, [ref]$tokens, [ref]$errors)
        if ($errors.Count -gt 0) {
            Write-Host "`n[SYNTAX ERROR] $RelPath" -ForegroundColor Red
            foreach ($err in $errors) {
                Write-Host "  Line $($err.Extent.StartLineNumber): $($err.Message)" -ForegroundColor Yellow
            }
            Write-Host ""
            [Console]::Beep(800, 200)
        }
        else {
            Write-Host "[OK PS1] $RelPath" -ForegroundColor Green
        }
    }
    elseif ($Ext -eq ".dart") {
        if (Test-Path $DartPath) {
            $DartResult = & $DartPath analyze --no-fatal-warnings $FilePath 2>&1
            if ($DartResult -match 'error - ') {
                Write-Host "`n[DART ERROR] $RelPath" -ForegroundColor Red
                Write-Host "  $DartResult`n" -ForegroundColor Yellow
                [Console]::Beep(800, 200)
            }
            else {
                Write-Host "[OK DART] $RelPath" -ForegroundColor Green
            }
        }
    }
}

Register-ObjectEvent $Watcher "Changed" -Action $Action | Out-Null
Register-ObjectEvent $Watcher "Created" -Action $Action | Out-Null

try {
    while ($true) {
        Start-Sleep -Seconds 1
    }
}
finally {
    Unregister-Event -SourceIdentifier $Watcher.Site -ErrorAction SilentlyContinue
    $Watcher.Dispose()
    Write-Host "`n[*] Diagnostic watcher stopped." -ForegroundColor DarkGray
}