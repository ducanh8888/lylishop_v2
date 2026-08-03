<#
.SYNOPSIS
    Local pre-flight checks before committing or building. Read-only — makes no changes.
.DESCRIPTION
    Verifies repo hygiene (no secrets staged, PLAN.md/TECH_STACK.md tracked) and, when
    the tools are installed, runs composer validate + PHP syntax checks. Missing tools
    are reported as warnings, not failures, since this machine may not have PHP/Composer
    installed yet (see docs/HOSTING-AUDIT.md section "Local audit").
#>

$ErrorActionPreference = 'Stop'
$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

$failures = @()
$warnings = @()

Write-Host "== Git repository state ==" -ForegroundColor Cyan
if (-not (Test-Path '.git')) {
    $warnings += "Not a git repository yet — run 'git init' before committing."
} else {
    git status --short
    $staged = git diff --cached --name-only
    foreach ($pattern in @('.env', 'auth.json', '*.sql', '*.sql.gz')) {
        if ($staged -match [regex]::Escape($pattern).Replace('\*', '.*')) {
            $failures += "Staged file matches forbidden pattern '$pattern' — do not commit secrets/dumps."
        }
    }
}

Write-Host "== Required binding documents ==" -ForegroundColor Cyan
foreach ($doc in @('PLAN.md', 'TECH_STACK.md')) {
    if (-not (Test-Path $doc)) {
        $failures += "$doc is missing from repository root."
    }
}

Write-Host "== composer.json ==" -ForegroundColor Cyan
$composerCmd = Get-Command composer -ErrorAction SilentlyContinue
if ($composerCmd) {
    composer validate --strict
    if ($LASTEXITCODE -ne 0) { $failures += "composer validate --strict failed." }
} else {
    $warnings += "composer not found on PATH — skipping composer validate. Install Composer or run this in CI."
}

Write-Host "== PHP syntax check (mu-plugin) ==" -ForegroundColor Cyan
$phpCmd = Get-Command php -ErrorAction SilentlyContinue
if ($phpCmd) {
    Get-ChildItem -Recurse -Filter '*.php' -Path 'web/app/mu-plugins', 'web/app/themes/shop-child' -ErrorAction SilentlyContinue |
        ForEach-Object {
            php -l $_.FullName
            if ($LASTEXITCODE -ne 0) { $failures += "PHP syntax error in $($_.FullName)" }
        }
} else {
    $warnings += "php not found on PATH — skipping syntax check. Install PHP 8.3 locally or rely on CI."
}

Write-Host ""
if ($warnings.Count -gt 0) {
    Write-Host "== Warnings ==" -ForegroundColor Yellow
    $warnings | ForEach-Object { Write-Host "  - $_" -ForegroundColor Yellow }
}

if ($failures.Count -gt 0) {
    Write-Host "== FAILURES ==" -ForegroundColor Red
    $failures | ForEach-Object { Write-Host "  - $_" -ForegroundColor Red }
    exit 1
}

Write-Host "Local validation passed." -ForegroundColor Green
