$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Windows.Forms

$title = 'English Presentation Generator'
$message = "Install English Presentation Generator on this computer?`n`nThe installer will copy the app to your user folder and create a desktop shortcut."
$choice = [System.Windows.Forms.MessageBox]::Show(
    $message,
    $title,
    [System.Windows.Forms.MessageBoxButtons]::YesNo,
    [System.Windows.Forms.MessageBoxIcon]::Question
)

if ($choice -ne [System.Windows.Forms.DialogResult]::Yes) {
    exit 0
}

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
& (Join-Path $scriptDir 'install.ps1')

$installRoot = Join-Path $env:LOCALAPPDATA 'EnglishPresentationGenerator'
$portablePhp = Join-Path $installRoot 'runtime\php\php.exe'
$systemPhp = Get-Command php.exe -ErrorAction SilentlyContinue

if (-not (Test-Path $portablePhp) -and -not $systemPhp) {
    [System.Windows.Forms.MessageBox]::Show(
        "Installation is complete, but PHP was not found.`n`nThe program needs PHP 8+ to start the local server. Use the full installer ZIP with portable PHP included, or install PHP/XAMPP on this computer.",
        $title,
        [System.Windows.Forms.MessageBoxButtons]::OK,
        [System.Windows.Forms.MessageBoxIcon]::Warning
    ) | Out-Null
}

$launchChoice = [System.Windows.Forms.MessageBox]::Show(
    "Installation is complete.`n`nA desktop shortcut was created.`n`nOpen the program now?",
    $title,
    [System.Windows.Forms.MessageBoxButtons]::YesNo,
    [System.Windows.Forms.MessageBoxIcon]::Information
)

if ($launchChoice -eq [System.Windows.Forms.DialogResult]::Yes) {
    Start-Process -FilePath (Join-Path $installRoot 'start.bat') -WorkingDirectory $installRoot
}
