<?php
require_once __DIR__ . '/../config.php'; // ✅ subir un nivel a la raíz

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

// Helper local para mostrar el estado (aproximado: proceso global)
function isRunning($exe){
    $out = @shell_exec('tasklist /FI "IMAGENAME eq ' . $exe . '"');
    return $out && strpos($out, $exe) !== false;
}

$running = isRunning(VALHEIM_EXE);
?>
<div class="container mt-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <h2 class="mb-0">🔄 Actualización del Servidor Valheim</h2>
        <span class="badge <?= $running ? 'bg-success' : 'bg-danger' ?>">
            <?= $running ? '✅ En ejecución' : '🛑 Apagado' ?>
        </span>
        <button class="btn btn-sm btn-outline-light ms-auto" onclick="checkStatus()">↻ Verificar estado</button>
    </div>

    <p class="text-muted">El servidor debe estar <strong>apagado</strong> para actualizar con SteamCMD.</p>

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
                        <small><code><?= htmlspecialchars(STEAMCMD_EXE) ?> +login anonymous +app_update 896660 -beta public-test -betapassword yesimadebackups validate +quit</code></small>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="doUpdate('prebeta')" <?= $running ? 'disabled' : '' ?>>🔄 Actualizar Pre-Beta</button>
                    </td>
                </tr>
                <tr>
                    <td>🎮 Normal</td>
                    <td class="text-start">
                        <small><code><?= htmlspecialchars(STEAMCMD_EXE) ?> +login anonymous +app_update 896660 validate +quit</code></small>
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
function msg(html, cls="info"){
    const box = document.getElementById('updMsg');
    const map = {info:'alert-info', success:'alert-success', warn:'alert-warning', error:'alert-danger'};
    box.className = 'alert ' + (map[cls] || 'alert-info');
    box.innerHTML = html;
}

function doUpdate(type){
    if (!confirm('¿Iniciar actualización "'+type+'" con SteamCMD?')) return;

    const body = new URLSearchParams();
    body.set('type', type);
    // Si quieres forzar CSRF en api.php, descomenta:
    // body.set('csrf', '<?= $_SESSION['csrf_token'] ?? '' ?>');

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
    .catch(err => {
        console.error(err);
        msg('⚠️ Error de red al iniciar la actualización.', 'error');
    });
}

function checkStatus(){
    fetch('api.php?action=status_servers', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(j => {
        if (!j || !j.ok) { msg('⚠️ No se pudo consultar el estado.', 'warn'); return; }
        // Como el estado es global (aprox), solo informamos si hay alguno en running
        const anyRunning = Object.values(j.status || {}).some(v => v === 'running');
        msg(anyRunning ? '✅ Al menos un servidor está en ejecución.' : '🛑 No hay servidores en ejecución.', anyRunning ? 'success' : 'info');
      })
      .catch(()=>msg('⚠️ Error de red al consultar estado.', 'warn'));
}
</script>
