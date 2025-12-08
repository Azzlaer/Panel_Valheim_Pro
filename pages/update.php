<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Helper local: comprobar si el servidor está ejecutándose
function isRunning($exeName) {
    $out = @shell_exec('tasklist /FI "IMAGENAME eq ' . $exeName . '"');
    return $out && stripos($out, $exeName) !== false;
}

$running = isRunning(basename(VALHEIM_EXE));
?>

<div class="container mt-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <h2 class="mb-0">🔄 Actualización del Servidor Valheim</h2>
        <span class="badge <?= $running ? 'bg-success' : 'bg-danger' ?>">
            <?= $running ? '✅ En ejecución' : '🛑 Apagado' ?>
        </span>
        <small class="text-muted ms-2">(<?= htmlspecialchars(SERVER_NAME) ?>)</small>
        <button class="btn btn-sm btn-outline-light ms-auto" onclick="checkStatus()">↻ Verificar estado</button>
    </div>

    <p class="text-muted">
        El servidor debe estar <strong>apagado</strong> para actualizarlo con SteamCMD.<br>
        <code><?= htmlspecialchars(STEAMCMD_EXE) ?></code> → <code><?= htmlspecialchars(SERVER_DIR) ?></code>
    </p>

    <div class="table-responsive">
        <table class="table table-dark table-hover text-center align-middle">
            <thead>
                <tr>
                    <th>Canal</th>
                    <th>Comando</th>
                    <th style="width:160px">Acción</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>🧪 Pre-Beta (public-test)</td>
                    <td class="text-start">
                        <small><code><?= htmlspecialchars(STEAMCMD_EXE) ?> +login anonymous +force_install_dir "<?= htmlspecialchars(SERVER_DIR) ?>" +app_update 896660 -beta public-test -betapassword yesimadebackups validate +quit</code></small>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="doUpdate('prebeta')" <?= $running ? 'disabled' : '' ?>>🔄 Actualizar Pre-Beta</button>
                    </td>
                </tr>
                <tr>
                    <td>🎮 Normal</td>
                    <td class="text-start">
                        <small><code><?= htmlspecialchars(STEAMCMD_EXE) ?> +login anonymous +force_install_dir "<?= htmlspecialchars(SERVER_DIR) ?>" +app_update 896660 validate +quit</code></small>
                    </td>
                    <td>
                        <button class="btn btn-success btn-sm" onclick="doUpdate('normal')" <?= $running ? 'disabled' : '' ?>>🔄 Actualizar Normal</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="updMsg" class="mt-3"></div>
</div>

<script>
function msg(html, cls="info") {
  const box = document.getElementById('updMsg');
  const map = {info:'alert-info', success:'alert-success', warn:'alert-warning', error:'alert-danger'};
  box.className = 'alert ' + (map[cls] || 'alert-info');
  box.innerHTML = html;
}

function doUpdate(type) {
  if (!confirm('¿Iniciar actualización "'+type+'"?')) return;

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
      msg('✅ Actualización lanzada. Revisa el log de SteamCMD para ver el progreso.', 'success');
    } else {
      msg('❌ ' + (j && j.error ? j.error : 'Error desconocido'), 'error');
    }
  })
  .catch(() => msg('⚠️ Error de red al iniciar la actualización.', 'error'));
}

function checkStatus() {
  fetch('api.php?action=status_servers', { credentials: 'same-origin' })
    .then(r => r.json())
    .then(j => {
      if (!j || !j.ok) {
        msg('⚠️ No se pudo consultar el estado.', 'warn');
        return;
      }
      const statusMap = j.status || {};
      const currentServer = "<?= htmlspecialchars(SERVER_NAME) ?>";
      const currentStatus = Object.values(statusMap).some(v => v === 'running')
        ? '✅ Servidor activo.'
        : '🛑 Servidor detenido.';
      msg(currentStatus, Object.values(statusMap).some(v => v === 'running') ? 'success' : 'info');
    })
    .catch(() => msg('⚠️ Error de red al consultar estado.', 'warn'));
}
</script>
