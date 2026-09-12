param(
    [Parameter(Mandatory=$true)]
    [string]$JobFile
)

$ErrorActionPreference = 'Stop'

function Write-Status {
    param(
        [string]$State,
        [string]$Message,
        [int]$ExitCode = -999,
        [string]$LastLine = ''
    )

    $payload = [ordered]@{
        state       = $State
        type        = $script:Job.type
        message     = $Message
        started_at  = $script:StartedAt
        finished_at = if ($State -in @('completed','error','warning')) { [int][DateTimeOffset]::UtcNow.ToUnixTimeSeconds() } else { $null }
        exit_code   = if ($ExitCode -eq -999) { $null } else { $ExitCode }
        last_line   = $LastLine
        install_dir = $script:Job.install_dir
        app_id      = $script:Job.app_id
    }

    $json = $payload | ConvertTo-Json -Depth 4
    [System.IO.File]::WriteAllText($script:Job.status_file, $json, [System.Text.UTF8Encoding]::new($false))
}

try {
    if (-not (Test-Path -LiteralPath $JobFile)) {
        throw "No existe el archivo de trabajo: $JobFile"
    }

    $script:Job = Get-Content -LiteralPath $JobFile -Raw -Encoding UTF8 | ConvertFrom-Json
    $script:StartedAt = [int][DateTimeOffset]::UtcNow.ToUnixTimeSeconds()

    if (-not (Test-Path -LiteralPath $Job.steamcmd)) {
        throw "steamcmd.exe no encontrado: $($Job.steamcmd)"
    }

    if (-not (Test-Path -LiteralPath $Job.install_dir)) {
        throw "Directorio de instalación no encontrado: $($Job.install_dir)"
    }

    $logDir = Split-Path -Parent $Job.log_file
    if ($logDir -and -not (Test-Path -LiteralPath $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }

    "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] ===== INICIO ACTUALIZACION $($Job.type) =====" | Set-Content -LiteralPath $Job.log_file -Encoding UTF8
    "SteamCMD: $($Job.steamcmd)" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8
    "Destino:  $($Job.install_dir)" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8
    "App ID:   $($Job.app_id)" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8
    "" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8

    Write-Status -State 'running' -Message 'SteamCMD está actualizando el servidor...'

    $steamArgs = @(
        '+force_install_dir', $Job.install_dir,
        '+login', 'anonymous',
        '+app_update', [string]$Job.app_id
    )

    if ($Job.type -eq 'prebeta') {
        $steamArgs += @('-beta', 'public-test', '-betapassword', 'yesimadebackups')
    }

    $steamArgs += @('validate', '+quit')

    $allLines = New-Object System.Collections.Generic.List[string]
    $lastLine = ''

    & $Job.steamcmd @steamArgs 2>&1 | ForEach-Object {
        $line = [string]$_
        $allLines.Add($line)
        $lastLine = $line
        $line | Add-Content -LiteralPath $Job.log_file -Encoding UTF8
        Write-Status -State 'running' -Message 'SteamCMD está actualizando el servidor...' -LastLine $lastLine
    }

    $exitCode = $LASTEXITCODE
    $joined = $allLines -join "`n"

    "" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8
    "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] ExitCode: $exitCode" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8

    $success = $joined -match "Success! App\s+'?$($Job.app_id)'?\s+fully installed"

    if ($success) {
        "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] ===== ACTUALIZACION COMPLETADA =====" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8
        Write-Status -State 'completed' -Message 'Actualización completada correctamente.' -ExitCode $exitCode -LastLine $lastLine
        exit 0
    }

    if ($exitCode -eq 0) {
        "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] ===== FINALIZADA SIN CONFIRMACION DE STEAMCMD =====" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8
        Write-Status -State 'warning' -Message 'SteamCMD terminó, pero no devolvió la confirmación de instalación esperada. Revisa el log.' -ExitCode $exitCode -LastLine $lastLine
        exit 2
    }

    "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] ===== ERROR DE ACTUALIZACION =====" | Add-Content -LiteralPath $Job.log_file -Encoding UTF8
    Write-Status -State 'error' -Message "SteamCMD terminó con código $exitCode." -ExitCode $exitCode -LastLine $lastLine
    exit $exitCode
}
catch {
    $msg = $_.Exception.Message

    try {
        if ($script:Job -and $script:Job.log_file) {
            "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] ERROR: $msg" | Add-Content -LiteralPath $script:Job.log_file -Encoding UTF8
        }
        if ($script:Job -and $script:Job.status_file) {
            if (-not $script:StartedAt) { $script:StartedAt = [int][DateTimeOffset]::UtcNow.ToUnixTimeSeconds() }
            Write-Status -State 'error' -Message $msg -ExitCode 1 -LastLine $msg
        }
    } catch {}

    exit 1
}
