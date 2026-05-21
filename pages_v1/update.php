<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Ruta donde guardamos el PID
$pidFile = __DIR__ . '/../server.pid';

// Helper: lectura del PID
function getPIDFile()
{
    global $pidFile;
    return file_exists($pidFile)
        ? intval(trim(file_get_contents($pidFile)))
        : null;
}

// Helper: verificar si proceso existe
function isProcessAlive($pid)
{
    if (!$pid || $pid <= 0) return false;

    $out = @shell_exec('tasklist /FI "PID eq ' . intval($pid) . '"');
    return $out && strpos($out, (string)$pid) !== false;
}

// Detectar estado del servidor
$pid = getPIDFile();
$running = isProcessAlive($pid);
?>

<style>
.update-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(245,158,11,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.update-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.update-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}

.update-wrap .status-badge {
    border-radius: 999px;
    font-weight: 700;
    padding: 8px 12px;
    font-size: 12px;
}

.update-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.update-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.update-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.update-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}

.update-wrap .module-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
    margin-bottom: 22px;
}

.update-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.update-wrap .section-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.update-wrap .section-header small {
    color: #9ca3af;
}

.update-wrap .table-dark {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255,255,255,.02);
    --bs-table-hover-bg: rgba(255,255,255,.035);
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.06);
    margin-bottom: 0;
}

.update-wrap .table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    font-weight: 700;
    padding-top: 14px;
    padding-bottom: 14px;
}

.update-wrap .cmd-box {
    font-size: 12px;
    line-height: 1.5;
    word-break: break-word;
    color: #cbd5e1;
}

.update-wrap .btn-action {
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 12px;
}

.update-wrap #updMsg {
    min-height: 48px;
}

@media (max-width: 991px) {
    .update-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .update-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 update-wrap">

    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>🔄 Actualización del Servidor Valheim</h2>
                <p>Ejecuta actualizaciones del servidor mediante SteamCMD de forma controlada. El servidor debe estar apagado antes de continuar.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge <?= $running ? 'bg-success' : 'bg-danger' ?> status-badge">
                    <?= $running ? '🟢 Online' : '🔴 Offline' ?>
                </span>
                <button class="btn btn-outline-light btn-sm" onclick="checkStatus()">↻ Verificar estado</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Servidor</div>
            <div class="stat-value" style="font-size:18px;"><?= htmlspecialchars(SERVER_NAME) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Estado actual</div>
            <div class="stat-value <?= $running ? 'text-success' : 'text-danger' ?>">
                <?= $running ? 'Online' : 'Offline' ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">PID registrado</div>
            <div class="stat-value"><?= $pid ?: 'N/A' ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Destino SteamCMD</div>
            <div class="stat-value" style="font-size:13px; line-height:1.5; font-weight:600;">
                <?= htmlspecialchars(SERVER_BASE) ?>
            </div>
        </div>
    </div>

    <div class="module-card">
        <div class="section-header">
            <div>
                <h3>📦 Canales de actualización</h3>
                <small>Selecciona el canal apropiado según tu entorno de pruebas o producción.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th>Canal</th>
                        <th>Comando</th>
                        <th style="width:180px">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>🧪 Pre-Beta (public-test)</td>
                        <td class="text-start">
                            <div class="cmd-box">
                                <code><?= htmlspecialchars(STEAMCMD_EXE) ?> +login anonymous +force_install_dir "<?= htmlspecialchars(SERVER_BASE) ?>" +app_update 896660 -beta public-test -betapassword yesimadebackups validate +quit</code>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-warning btn-sm btn-action"
                                    onclick="doUpdate('prebeta')"
                                    <?= $running ? 'disabled' : '' ?>>
                                    🔄 Actualizar Pre-Beta
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>🎮 Normal</td>
                        <td class="text-start">
                            <div class="cmd-box">
                                <code><?= htmlspecialchars(STEAMCMD_EXE) ?> +login anonymous +force_install_dir "<?= htmlspecialchars(SERVER_BASE) ?>" +app_update 896660 validate +quit</code>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-success btn-sm btn-action"
                                    onclick="doUpdate('normal')"
                                    <?= $running ? 'disabled' : '' ?>>
                                    🔄 Actualizar Normal
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="updMsg" class="mt-3"></div>
</div>

<script>
function msg(html, cls="info") {
  const box = document.getElementById('updMsg');
  const map = {
    info:'alert-info',
    success:'alert-success',
    warn:'alert-warning',
    error:'alert-danger'
  };
  box.className = 'alert ' + (map[cls] || 'alert-info');
  box.innerHTML = html;
}

function doUpdate(type) {
  if (!confirm('¿Iniciar actualización "' + type + '"?')) return;

  const body = new URLSearchParams();
  body.set('type', type);

  fetch('api.php?action=steam_update', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString(),
    credentials: 'same-origin'
  })
  .then(r => r.json())
  .then(j => {
    if (j && j.ok) {
      msg('✅ Actualización lanzada correctamente. Revisa el log de SteamCMD para seguir el progreso.', 'success');
    } else {
      msg('❌ ' + (j && j.error ? j.error : 'Error desconocido'), 'error');
    }
  })
  .catch(() => msg('⚠️ Error de red al ejecutar la actualización.', 'error'));
}

function checkStatus() {
  fetch('api.php?action=status_servers', { credentials: 'same-origin' })
    .then(r => r.json())
    .then(j => {
      if (!j || !j.ok) {
        msg('⚠️ No se pudo consultar el estado actual del servidor.', 'warn');
        return;
      }

      const st = j.status?.current ?? 'stopped';

      msg(
        st === 'running'
          ? '🟢 Servidor activo. Debes detenerlo antes de actualizar.'
          : '🔴 Servidor detenido. Ya puedes ejecutar una actualización.',
        st === 'running' ? 'warn' : 'success'
      );
    })
    .catch(() => msg('⚠️ Error de red verificando estado.', 'warn'));
}
</script>