<#
.SYNOPSIS
    Repo checks: PHP syntax lint + test suite.

.DESCRIPTION
    The checks this repo owns:
      1. php -l over every .php file      (catches parse errors, including in files no test requires)
      2. four mechanical coding standards (see CODING_STANDARDS.md)
      3. tests/run.php + tests/weeks.php  (unit tests; --mysql adds integration tests)

    Exits non-zero on any failure, so it works as a pre-commit hook or CI step.

.PARAMETER Mysql
    Include the MySQL integration tests. Requires MySQL running and
    SIWES_TEST_DB=siwes_tracker_test. Never touches the application database.

.PARAMETER Smoke
    Also probe the running app over HTTP. Requires Apache up; see RUNNING.md.

.EXAMPLE
    .\check.ps1
    .\check.ps1 -Mysql
    $env:SIWES_TEST_DB = 'siwes_tracker_test'; .\check.ps1 -Mysql -Smoke
#>
[CmdletBinding()]
param(
    [switch]$Mysql,
    [switch]$Smoke,
    [string]$BaseUrl = 'http://localhost/siwes%20tracker'
)

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
Set-Location $root

# ---- Locate PHP -----------------------------------------------------------
# PHP is not on PATH in this environment; XAMPP's copy is the fallback.
$php = (Get-Command php -ErrorAction SilentlyContinue)
if ($php) { $php = $php.Source }
elseif (Test-Path 'C:\xampp\php\php.exe') { $php = 'C:\xampp\php\php.exe' }
else {
    Write-Host 'FAIL  php not found. Tried PATH and C:\xampp\php\php.exe (see RUNNING.md).' -ForegroundColor Red
    exit 1
}

$failed = 0

# ---- 1. Syntax lint -------------------------------------------------------
Write-Host 'Lint' -ForegroundColor Cyan
$phpFiles = Get-ChildItem -Recurse -File -Filter *.php |
    Where-Object { $_.FullName -notmatch '\\(vendor|node_modules|\.git|__pycache__)\\' }
foreach ($file in $phpFiles) {
    $null = & $php -l $file.FullName 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  PASS $($file.Name)" -ForegroundColor DarkGray
    }
    else {
        $failed++
        Write-Host "  FAIL $($file.FullName)" -ForegroundColor Red
        & $php -l $file.FullName 2>&1 | ForEach-Object { Write-Host "       $_" }
    }
}

# ---- 2. Coding standards ---------------------------------------------------
# These four rules are mechanical, so they are enforced here rather than left to
# review. See CODING_STANDARDS.md.
Write-Host 'Standards' -ForegroundColor Cyan
$appFiles = Get-ChildItem -Path api -File -Filter *.php
$allPhp   = $appFiles + (Get-ChildItem -Recurse -File -Filter *.php |
            Where-Object { $_.FullName -notmatch '\\(vendor|node_modules|\.git|__pycache__)\\' })

function Test-Rule {
    param([string]$Label, [string]$Pattern, $Files, [string[]]$Exclude = @())

    $hits = @()
    foreach ($file in $Files) {
        if ($Exclude -contains $file.Name) { continue }
        if (Select-String -Path $file.FullName -Pattern $Pattern -Quiet) { $hits += $file.Name }
    }
    if ($hits.Count -eq 0) {
        Write-Host "  PASS $Label" -ForegroundColor DarkGray
    }
    else {
        $script:failed++
        Write-Host "  FAIL $Label -> $($hits -join ', ')" -ForegroundColor Red
    }
}

# Asserts every one of $Files contains $Pattern. Violation = a file missing it.
function Test-Requires {
    param([string]$Label, [string]$Pattern, $Files, [string[]]$Skip = @())

    $missing = @()
    foreach ($file in $Files) {
        if ($Skip -contains $file.Name) { continue }
        if (-not (Select-String -Path $file.FullName -Pattern $Pattern -Quiet)) { $missing += $file.Name }
    }
    if ($missing.Count -eq 0) {
        Write-Host "  PASS $Label" -ForegroundColor DarkGray
    }
    else {
        $script:failed++
        Write-Host "  FAIL $Label -> missing in $($missing -join ', ')" -ForegroundColor Red
    }
}

# Every query is prepared with bound parameters; ->query() would interpolate.
Test-Rule 'no ->query(), use prepare() with bound params' '->\s*query\s*\(' $appFiles
Test-Rule 'no mysqli_*'                        '\bmysqli_'                 $appFiles

# Any page that reads $_POST must verify the token and render the field.
$postPages = $appFiles | Where-Object { Select-String -Path $_.FullName -Pattern '\$_POST' -Quiet }
Test-Requires 'POST handlers verify CSRF'   'csrf_verify\s*\(' $postPages @('helpers.php')
Test-Requires 'POST forms carry csrf_field()' 'csrf_field\s*\(' $postPages @('helpers.php')

# ---- 3. Test suites -------------------------------------------------------
function Invoke-Suite {
    param([string]$Label, [string[]]$Arguments)

    Write-Host $Label -ForegroundColor Cyan
    & $php @Arguments
    if ($LASTEXITCODE -ne 0) {
        $script:failed++
        Write-Host "  FAIL $Label (exit $LASTEXITCODE)" -ForegroundColor Red
    }
}

Invoke-Suite -Label 'Unit tests (tests/run.php)' -Arguments @('tests/run.php')
Invoke-Suite -Label 'Unit tests (tests/weeks.php)' -Arguments @('tests/weeks.php')

if ($Mysql) {
    if (-not $env:SIWES_TEST_DB) {
        Write-Host 'FAIL  -Mysql needs $env:SIWES_TEST_DB = ''siwes_tracker_test''' -ForegroundColor Red
        $failed++
    }
    else {
        Invoke-Suite -Label 'Integration tests (tests/run.php --mysql)' -Arguments @('tests/run.php', '--mysql')
    }
}

# ---- 4. Optional HTTP smoke ------------------------------------------------
if ($Smoke) {
    Write-Host "Smoke ($BaseUrl)" -ForegroundColor Cyan
    # 302 is correct for pages that require a session. 403 is .htaccess denying
    # files that must never be public.
    $expect = @{
        '/'            = 302
        '/login.php'   = 200
        '/register.php' = 200
        '/dashboard.php' = 302
        '/logs.php'    = 302
        '/.git/config' = 403
        '/database.sql' = 403
    }
    foreach ($path in ($expect.Keys | Sort-Object)) {
        $got = & curl.exe -s -o NUL -w '%{http_code}' "$BaseUrl$path"
        if ($got -eq $expect[$path]) {
            Write-Host "  PASS $path ($got)" -ForegroundColor DarkGray
        }
        else {
            $failed++
            Write-Host "  FAIL $path expected $($expect[$path]) got $got" -ForegroundColor Red
        }
    }
}

# ---- Verdict --------------------------------------------------------------
Write-Host ''
if ($failed -gt 0) {
    Write-Host "$failed check(s) failed." -ForegroundColor Red
    exit 1
}
Write-Host 'All checks passed.' -ForegroundColor Green
exit 0