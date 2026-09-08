$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$appRoot = Split-Path -Parent $scriptDir
$envPath = Join-Path $appRoot '.env'
$envExamplePath = Join-Path $appRoot '.env.example'

if (-not (Test-Path $envPath) -and (Test-Path $envExamplePath)) {
    Copy-Item $envExamplePath $envPath
}

function Read-EnvValue {
    param([string] $Name, [string] $Default = '')

    if (-not (Test-Path $envPath)) {
        return $Default
    }

    $line = Get-Content $envPath | Where-Object { $_ -match "^\s*$([regex]::Escape($Name))\s*=" } | Select-Object -First 1
    if (-not $line) {
        return $Default
    }

    return (($line -split '=', 2)[1]).Trim().Trim('"').Trim("'")
}

function Find-Php {
    $candidates = @(
        (Join-Path $appRoot 'runtime\php\php.exe'),
        (Join-Path $appRoot 'php\php.exe'),
        'C:\php\php.exe',
        'C:\xampp\php\php.exe'
    )

    foreach ($candidate in $candidates) {
        if (Test-Path $candidate) {
            return $candidate
        }
    }

    $cmd = Get-Command php.exe -ErrorAction SilentlyContinue
    if ($cmd) {
        return $cmd.Source
    }

    return ''
}

function Test-PortOpen {
    param([int] $Port)

    try {
        $client = New-Object Net.Sockets.TcpClient
        $async = $client.BeginConnect('127.0.0.1', $Port, $null, $null)
        $connected = $async.AsyncWaitHandle.WaitOne(350)
        if ($connected) {
            $client.EndConnect($async)
        }
        $client.Close()
        return $connected
    } catch {
        return $false
    }
}

function Test-PortOwnedByApp {
    param([int] $Port, [string] $Root)

    try {
        $connections = Get-NetTCPConnection -LocalAddress '127.0.0.1' -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
        foreach ($connection in $connections) {
            $process = Get-CimInstance Win32_Process -Filter "ProcessId=$($connection.OwningProcess)" -ErrorAction SilentlyContinue
            if ($process -and $process.CommandLine -and $process.CommandLine.Contains($Root)) {
                return $true
            }
        }
    } catch {
        return $false
    }

    return $false
}

function Find-FreePort {
    param([int] $StartPort)

    $candidate = $StartPort
    while ($candidate -lt 65535) {
        if (-not (Test-PortOpen $candidate)) {
            return $candidate
        }
        $candidate++
    }

    return $StartPort
}

$portValue = Read-EnvValue 'APP_PORT' '8010'
$port = 8010
if (-not [int]::TryParse($portValue, [ref] $port)) {
    $port = 8010
}

$url = "http://127.0.0.1:$port/"
$serverAlreadyRunning = Test-PortOpen $port

if ($serverAlreadyRunning -and -not (Test-PortOwnedByApp $port $appRoot)) {
    $port = Find-FreePort ($port + 1)
    $url = "http://127.0.0.1:$port/"
    $serverAlreadyRunning = $false
}

if (-not $serverAlreadyRunning) {
    $php = Find-Php
    if ($php -eq '') {
        Add-Type -AssemblyName PresentationFramework
        [System.Windows.MessageBox]::Show(
            "PHP не найден. Переустановите программу из полного ZIP-архива или установите PHP 8+ / XAMPP. Программа также поддерживает portable PHP в папке runtime\php\php.exe.",
            "Генератор презентаций",
            'OK',
            'Warning'
        ) | Out-Null
        exit 1
    }

    $zipCheck = & $php -m 2>$null | Select-String -Pattern '^zip$' -Quiet
    if (-not $zipCheck) {
        Add-Type -AssemblyName PresentationFramework
        [System.Windows.MessageBox]::Show(
            "Найден PHP, но в нём не включено расширение ZIP. Без него программа не сможет собирать PPTX. Переустановите программу из полного ZIP-архива или включите extension=zip в php.ini.",
            "Генератор презентаций",
            'OK',
            'Warning'
        ) | Out-Null
        exit 1
    }

    Start-Process -FilePath $php -ArgumentList @('-S', "127.0.0.1:$port", '-t', 'public') -WorkingDirectory $appRoot -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

Start-Process $url
