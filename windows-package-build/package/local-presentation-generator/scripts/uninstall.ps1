$ErrorActionPreference = 'Stop'

$installRoot = Join-Path $env:LOCALAPPDATA 'EnglishPresentationGenerator'
$desktop = [Environment]::GetFolderPath('Desktop')
$shortcutPath = Join-Path $desktop ([string]::Concat(
    [char]0x0413, [char]0x0435, [char]0x043D, [char]0x0435, [char]0x0440, [char]0x0430,
    [char]0x0442, [char]0x043E, [char]0x0440, [char]0x0020,
    [char]0x043F, [char]0x0440, [char]0x0435, [char]0x0437, [char]0x0435, [char]0x043D,
    [char]0x0442, [char]0x0430, [char]0x0446, [char]0x0438, [char]0x0439,
    '.lnk'
))

Write-Host ''
Write-Host 'Uninstalling English Presentation Generator'

if (Test-Path $shortcutPath) {
    Remove-Item $shortcutPath -Force
    Write-Host "Desktop shortcut removed: $shortcutPath"
}

if (-not (Test-Path $installRoot)) {
    Write-Host 'Installed application folder was not found.'
    exit 0
}

$answer = Read-Host 'Delete installed folder with generated presentations? Type YES to confirm'
if ($answer -ne 'YES') {
    Write-Host 'Application folder was left unchanged.'
    exit 0
}

Remove-Item $installRoot -Recurse -Force
Write-Host "Application folder removed: $installRoot"
