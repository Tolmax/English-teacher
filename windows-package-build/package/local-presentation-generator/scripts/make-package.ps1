$ErrorActionPreference = 'Stop'

$appRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$parent = Split-Path -Parent $appRoot
$zipPath = Join-Path $parent 'local-presentation-generator-install.zip'
$tempRoot = Join-Path $env:TEMP ('lpg-package-' + [guid]::NewGuid().ToString('N'))
$packageRoot = Join-Path $tempRoot 'local-presentation-generator'

New-Item -ItemType Directory -Path $packageRoot | Out-Null

$excludeDirs = @('\storage\output\', '\storage\images\', '\public\downloads\')
$files = Get-ChildItem $appRoot -Recurse -File | Where-Object {
    $full = $_.FullName
    foreach ($dir in $excludeDirs) {
        if ($full.Contains($dir)) {
            return $false
        }
    }
    return $true
}

foreach ($file in $files) {
    $relative = $file.FullName.Substring($appRoot.Length).TrimStart('\')
    $target = Join-Path $packageRoot $relative
    $targetDir = Split-Path -Parent $target
    if (-not (Test-Path $targetDir)) {
        New-Item -ItemType Directory -Path $targetDir | Out-Null
    }
    Copy-Item $file.FullName $target -Force
}

foreach ($dir in @('storage\output', 'storage\images', 'public\downloads')) {
    New-Item -ItemType Directory -Path (Join-Path $packageRoot $dir) | Out-Null
}

if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

Compress-Archive -Path $packageRoot -DestinationPath $zipPath -Force
Remove-Item $tempRoot -Recurse -Force

Write-Host "Пакет создан: $zipPath"
