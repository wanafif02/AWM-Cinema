# LOCAL PHP SYNTAX CHECK — run from the awm-cinema project root.
# No database mutations; no API credentials are displayed.
$ErrorActionPreference = 'Stop'
$awmPhpExecutable = 'C:\xampp\php\php.exe'
if (-not (Test-Path $awmPhpExecutable)) {
    throw 'Update $awmPhpExecutable to your XAMPP PHP path first.'
}
$awmFailures = 0
Get-ChildItem -Path . -Filter *.php -Recurse | ForEach-Object {
    & $awmPhpExecutable -l $_.FullName
    if ($LASTEXITCODE -ne 0) { $awmFailures++ }
}
if ($awmFailures -gt 0) { throw "$awmFailures PHP file(s) failed syntax checks." }
Write-Host 'All PHP files passed local PHP syntax checks.'
