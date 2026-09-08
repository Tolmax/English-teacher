$ErrorActionPreference = 'Stop'

$sourceRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$installRoot = Join-Path $env:LOCALAPPDATA 'EnglishPresentationGenerator'
$desktop = [Environment]::GetFolderPath('Desktop')
$shortcutName = [string]::Concat(
    [char]0x0413, [char]0x0435, [char]0x043D, [char]0x0435, [char]0x0440, [char]0x0430,
    [char]0x0442, [char]0x043E, [char]0x0440, [char]0x0020,
    [char]0x043F, [char]0x0440, [char]0x0435, [char]0x0437, [char]0x0435, [char]0x043D,
    [char]0x0442, [char]0x0430, [char]0x0446, [char]0x0438, [char]0x0439,
    '.lnk'
)
$shortcutPath = Join-Path $desktop $shortcutName

if (-not (Test-Path $installRoot)) {
    New-Item -ItemType Directory -Path $installRoot | Out-Null
}

$excludeDirs = @('\storage\output\', '\storage\images\', '\public\downloads\')
$files = Get-ChildItem $sourceRoot -Recurse -File | Where-Object {
    $full = $_.FullName
    foreach ($dir in $excludeDirs) {
        if ($full.Contains($dir)) {
            return $false
        }
    }
    return $true
}

foreach ($file in $files) {
    $relative = $file.FullName.Substring($sourceRoot.Length).TrimStart('\')
    $target = Join-Path $installRoot $relative
    $targetDir = Split-Path -Parent $target
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir | Out-Null
    }
    if ($relative -eq '.env' -and (Test-Path $target)) {
        continue
    }
    Copy-Item $file.FullName $target -Force
}

foreach ($dir in @('storage\output', 'storage\images', 'public\downloads')) {
    $path = Join-Path $installRoot $dir
    if (-not (Test-Path $path)) {
        New-Item -ItemType Directory -Path $path | Out-Null
    }
}

$envPath = Join-Path $installRoot '.env'
$envExamplePath = Join-Path $installRoot '.env.example'
if (-not (Test-Path $envPath) -and (Test-Path $envExamplePath)) {
    Copy-Item $envExamplePath $envPath
}

$launcher = Join-Path $installRoot 'start.bat'
$shell = New-Object -ComObject WScript.Shell
$shortcut = $shell.CreateShortcut($shortcutPath)
$shortcut.TargetPath = $launcher
$shortcut.WorkingDirectory = $installRoot
$shortcut.Description = 'Local PPTX presentation generator'
$shortcut.IconLocation = "$env:SystemRoot\System32\shell32.dll,265"
$shortcut.Save()

Write-Host ''
Write-Host 'Done.'
Write-Host "Application installed to: $installRoot"
Write-Host "Desktop shortcut created: $shortcutPath"
Write-Host 'Launch the app from the desktop shortcut.'
