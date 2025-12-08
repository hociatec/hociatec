<#
.SYNOPSIS
Demarre le backend Symfony et le frontend Vite du projet Hociatec.
.DESCRIPTION
Lance le serveur Symfony (ou php -S) puis npm run dev pour le frontend. Utilisez Ctrl+C pour tout arreter.
.PARAMETER BackendOnly
Demarre uniquement le backend.
.PARAMETER FrontendOnly
Demarre uniquement le frontend.
.PARAMETER LeaveBackendRunning
Laisse le backend actif a la fin du script.
.PARAMETER BackendPort
Port expose par le backend (defaut 8000).
.PARAMETER FrontendPort
Port Vite (defaut 5173).
.PARAMETER FrontendHost
Valeur passee a --host pour Vite (defaut 127.0.0.1).
#>
[CmdletBinding()]
param(
    [switch]$BackendOnly,
    [switch]$FrontendOnly,
    [switch]$LeaveBackendRunning,
    [int]$BackendPort = 8000,
    [int]$FrontendPort = 5173,
    [string]$FrontendHost = "127.0.0.1"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if ($BackendOnly -and $FrontendOnly) {
    throw "BackendOnly et FrontendOnly ne peuvent pas etre actifs en meme temps."
}

$scriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$backendDir = Join-Path $scriptRoot "backend"
$frontendDir = Join-Path $scriptRoot "frontend"

function Assert-Directory {
    param(
        [string]$Path,
        [string]$Label
    )

    if (-not (Test-Path -Path $Path -PathType Container)) {
        throw "Impossible de trouver le dossier $Label ($Path)."
    }
}

function Start-HociatecBackend {
    param(
        [string]$BackendDir,
        [int]$Port
    )

    $symfonyCli = Get-Command symfony -ErrorAction SilentlyContinue

    if ($symfonyCli) {
        Write-Host "[Backend] symfony server:start sur le port $Port..." -ForegroundColor Yellow
        Push-Location $BackendDir
        try {
            symfony server:start --no-tls "--port=$Port" -d | Out-Null
            $symfonyExitCode = $LASTEXITCODE
        }
        finally {
            Pop-Location
        }

        if ($symfonyExitCode -ne 0) {
            throw "symfony server:start a echoue (code $symfonyExitCode)."
        }

        Write-Host ("[Backend] Symfony CLI actif sur http://127.0.0.1:{0}" -f $Port) -ForegroundColor Green
        return [pscustomobject]@{
            Type = "symfony"
            Dir  = $BackendDir
            Port = $Port
        }
    }

    Get-Command php -ErrorAction Stop | Out-Null

    $publicDir = Join-Path $BackendDir "public"
    if (-not (Test-Path -Path $publicDir -PathType Container)) {
        throw "Impossible de trouver le dossier public du backend ($publicDir)."
    }

    Write-Host ("[Backend] php -S 127.0.0.1:{0} -t public..." -f $Port) -ForegroundColor Yellow
    $hostBinding = "127.0.0.1:{0}" -f $Port
    $phpProcess = Start-Process -FilePath "php" -ArgumentList @("-S",$hostBinding,"-t",$publicDir) -WorkingDirectory $BackendDir -WindowStyle Hidden -PassThru

    Write-Host "[Backend] Serveur PHP integre lance (PID $($phpProcess.Id))." -ForegroundColor Green
    return [pscustomobject]@{
        Type      = "php"
        ProcessId = $phpProcess.Id
        Port      = $Port
    }
}

function Stop-HociatecBackend {
    param($Handle)

    if (-not $Handle) {
        return
    }

    if ($Handle.Type -eq "symfony") {
        Write-Host "[Backend] Arret du serveur Symfony..." -ForegroundColor Yellow
        Push-Location $Handle.Dir
        try {
            symfony server:stop | Out-Null
        }
        finally {
            Pop-Location
        }
        return
    }

    if ($Handle.Type -eq "php" -and $Handle.ProcessId) {
        $proc = Get-Process -Id $Handle.ProcessId -ErrorAction SilentlyContinue
        if ($proc) {
            Write-Host "[Backend] Arret du serveur PHP (PID $($Handle.ProcessId))..." -ForegroundColor Yellow
            Stop-Process -Id $Handle.ProcessId -Force
        }
    }
}

function Wait-HociatecBackend {
    param($Handle)

    if (-not $Handle) {
        return
    }

    if ($Handle.Type -eq "php" -and $Handle.ProcessId) {
        Write-Host "[Backend] Serveur PHP actif. Ctrl+C pour quitter." -ForegroundColor Cyan
        Wait-Process -Id $Handle.ProcessId
        return
    }

    Write-Host "[Backend] Symfony CLI tourne en arriere plan. Ctrl+C pour quitter." -ForegroundColor Cyan
    while ($true) {
        Start-Sleep -Seconds 60
    }
}

function Start-HociatecFrontend {
    param(
        [string]$FrontendDir,
        [string]$Host,
        [int]$Port
    )

    Get-Command npm -ErrorAction Stop | Out-Null

    $args = @("run","dev","--","--host",$Host,"--port",$Port)
    Write-Host "[Frontend] npm $($args -join ' ')" -ForegroundColor Yellow

    $exitCode = 0
    Push-Location $FrontendDir
    try {
        & npm @args
        $exitCode = $LASTEXITCODE
    }
    finally {
        Pop-Location
    }

    if ($exitCode -ne 0) {
        throw "Le frontend s'est arrete avec le code $exitCode."
    }
}

$runBackend = -not $FrontendOnly
$runFrontend = -not $BackendOnly
$backendHandle = $null

if ($runBackend) {
    Assert-Directory -Path $backendDir -Label "backend"
}

if ($runFrontend) {
    Assert-Directory -Path $frontendDir -Label "frontend"
}

try {
    if ($runBackend) {
        $backendHandle = Start-HociatecBackend -BackendDir $backendDir -Port $BackendPort
    }

    if ($runFrontend) {
        Write-Host ("[Info] Frontend disponible sur http://{0}:{1} une fois Vite pret." -f $FrontendHost, $FrontendPort) -ForegroundColor Cyan
        Start-HociatecFrontend -FrontendDir $frontendDir -Host $FrontendHost -Port $FrontendPort
    } elseif ($runBackend -and -not $LeaveBackendRunning) {
        Wait-HociatecBackend -Handle $backendHandle
    }
}
finally {
    if ($runBackend -and -not $LeaveBackendRunning) {
        Stop-HociatecBackend -Handle $backendHandle
    } elseif ($runBackend) {
        Write-Host ("[Backend] Laisse actif sur http://127.0.0.1:{0}." -f $backendHandle.Port) -ForegroundColor Cyan
    }
}
