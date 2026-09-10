param(
    [string]$ExePath,
    [string]$Arguments,
    [string]$PidFile
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $ExePath)) {
    Write-Output '{"ok":false,"error":"Ejecutable no encontrado"}'
    exit 1
}

$workDir = Split-Path -Parent $ExePath

$p = Start-Process -FilePath $ExePath `
                   -ArgumentList $Arguments `
                   -WorkingDirectory $workDir `
                   -WindowStyle Hidden `
                   -PassThru

Start-Sleep -Seconds 2

if (-not $p -or -not $p.Id) {
    Write-Output '{"ok":false,"error":"No se pudo obtener PID"}'
    exit 1
}

Set-Content -Path $PidFile -Value $p.Id -Encoding ASCII
Write-Output ('{"ok":true,"pid":' + $p.Id + '}')
