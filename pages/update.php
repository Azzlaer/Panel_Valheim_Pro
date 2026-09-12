<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$pidFile = __DIR__ . '/../server.pid';

function updGetPIDFile() {
    global $pidFile;
    return file_exists($pidFile) ? intval(trim(file_get_contents($pidFile))) : null;
}

function updIsProcessAlive($pid) {
    if (!$pid || $pid <= 0) return false;
    $out = @shell_exec('tasklist /FI "PID eq ' . intval($pid) . '" /FO CSV /NH');
    if (!$out) return false;
    $line = trim($out);
    if ($line === '' || stripos($line, 'No tasks are running') !== false) return false;
    return strpos($line, '"' . intval($pid) . '"') !== false;
}

$pid = updGetPIDFile();
$running = updIsProcessAlive($pid);
$destination = defined('SERVER_BASE') ? rtrim(SERVER_BASE, '\\/') : '';
?>

<style>
.update-wrap .panel-hero{background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(17,24,39,.68));border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:24px;box-shadow:0 18px 40px rgba(0,0,0,.28);margin-bottom:22px}
.update-wrap .panel-hero h2{margin:0;font-weight:800;color:#fff}.update-wrap .panel-hero p{margin:8px 0 0;color:#9ca3af}
.update-wrap .status-badge{border-radius:999px;font-weight:700;padding:8px 12px;font-size:12px}
.update-wrap .stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:22px}
.update-wrap .stat-card,.update-wrap .module-card{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);box-shadow:0 10px 24px rgba(0,0,0,.20)}
.update-wrap .stat-card{border-radius:18px;padding:18px}.update-wrap .module-card{border-radius:22px;overflow:hidden;margin-bottom:22px}
.update-wrap .stat-label{font-size:12px;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px}.update-wrap .stat-value{font-size:24px;font-weight:800;color:#fff}
.update-wrap .section-header{padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.update-wrap .section-header h3{margin:0;font-size:18px;font-weight:700;color:#fff}.update-wrap .section-header small{color:#9ca3af}
.update-wrap .table-dark{--bs-table-bg:transparent;--bs-table-striped-bg:rgba(255,255,255,.02);--bs-table-hover-bg:rgba(255,255,255,.035);--bs-table-color:#e5e7eb;--bs-table-border-color:rgba(255,255,255,.06);margin-bottom:0}
.update-wrap .table thead th{font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;font-weight:700;padding-top:14px;padding-bottom:14px}
.update-wrap .cmd-box{font-size:12px;line-height:1.5;word-break:break-word;color:#cbd5e1}.update-wrap .btn-action{border-radius:12px;font-weight:700;padding:8px 12px}
.update-wrap .progress{height:14px;background:#0b1220;border:1px solid rgba(255,255,255,.06);border-radius:999px;overflow:hidden}.update-wrap .progress-bar{transition:width .35s ease}
.update-wrap .steam-console{background:#030712;color:#cbd5e1;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:14px;min-height:180px;max-height:320px;overflow:auto;font:12px/1.5 Consolas,monospace;white-space:pre-wrap;word-break:break-word}
.update-wrap .update-state{font-weight:800}.update-wrap #updMsg{min-height:48px}
@media(max-width:991px){.update-wrap .stats-grid{grid-template-columns:1fr 1fr}}@media(max-width:575px){.update-wrap .stats-grid{grid-template-columns:1fr}}
</style>

<div class="container-fluid mt-4 update-wrap">
    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>🔄 Actualización del Servidor Valheim</h2>
                <p>SteamCMD actualiza directamente la instalación configurada y el panel supervisa el proceso hasta obtener un resultado final.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge <?= $running ? 'bg-success' : 'bg-danger' ?> status-badge"><?= $running ? '🟢 Online' : '🔴 Offline' ?></span>
                <button class="btn btn-outline-light btn-sm" onclick="window.valheimUpdCheckServerStatus()">↻ Verificar servidor</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-label">Servidor</div><div class="stat-value" style="font-size:18px"><?= htmlspecialchars(defined('SERVER_NAME') ? SERVER_NAME : 'Valheim') ?></div></div>
        <div class="stat-card"><div class="stat-label">Estado actual</div><div class="stat-value <?= $running ? 'text-success' : 'text-danger' ?>"><?= $running ? 'Online' : 'Offline' ?></div></div>
        <div class="stat-card"><div class="stat-label">PID registrado</div><div class="stat-value"><?= $pid ?: 'N/A' ?></div></div>
        <div class="stat-card"><div class="stat-label">Destino SteamCMD</div><div class="stat-value" style="font-size:13px;line-height:1.5;font-weight:600"><?= htmlspecialchars($destination ?: 'No configurado') ?></div></div>
    </div>

    <div class="module-card">
        <div class="section-header"><div><h3>📦 Canales de actualización</h3><small>El servidor debe estar apagado. Se utiliza <code>+force_install_dir</code> para actualizar exactamente la carpeta configurada.</small></div></div>
        <div class="table-responsive">
            <table class="table table-dark table-hover text-center align-middle">
                <thead><tr><th>Canal</th><th>Comando efectivo</th><th style="width:190px">Acción</th></tr></thead>
                <tbody>
                    <tr>
                        <td>🧪 Pre-Beta (public-test)</td>
                        <td class="text-start"><div class="cmd-box"><code><?= htmlspecialchars(STEAMCMD_EXE) ?> +force_install_dir "<?= htmlspecialchars($destination) ?>" +login anonymous +app_update 896660 -beta public-test -betapassword yesimadebackups validate +quit</code></div></td>
                        <td><button id="btnUpdPrebeta" class="btn btn-warning btn-sm btn-action" onclick="window.valheimUpdStart('prebeta')" <?= $running ? 'disabled' : '' ?>>🔄 Actualizar Pre-Beta</button></td>
                    </tr>
                    <tr>
                        <td>🎮 Normal</td>
                        <td class="text-start"><div class="cmd-box"><code><?= htmlspecialchars(STEAMCMD_EXE) ?> +force_install_dir "<?= htmlspecialchars($destination) ?>" +login anonymous +app_update 896660 validate +quit</code></div></td>
                        <td><button id="btnUpdNormal" class="btn btn-success btn-sm btn-action" onclick="window.valheimUpdStart('normal')" <?= $running ? 'disabled' : '' ?>>🔄 Actualizar Normal</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="module-card">
        <div class="section-header">
            <div><h3>📡 Estado de SteamCMD</h3><small>Seguimiento real del worker de actualización.</small></div>
            <span id="steamUpdateState" class="badge bg-secondary status-badge">IDLE</span>
        </div>
        <div class="p-3">
            <div class="d-flex justify-content-between gap-3 flex-wrap mb-2">
                <div id="steamUpdateMessage" class="text-light update-state">Sin actualización activa.</div>
                <div id="steamUpdateTime" class="text-secondary small"></div>
            </div>
            <div class="progress mb-3"><div id="steamUpdateProgress" class="progress-bar progress-bar-striped" role="progressbar" style="width:0%"></div></div>
            <div id="updMsg" class="mb-3"></div>
            <div class="text-secondary small mb-2">Últimas líneas del log:</div>
            <div id="steamUpdateConsole" class="steam-console">Sin actividad todavía.</div>
        </div>
    </div>
</div>

<script>
(function(){
    if (window.valheimUpdatePollTimer) {
        clearInterval(window.valheimUpdatePollTimer);
        window.valheimUpdatePollTimer = null;
    }

    function msg(html, cls='info') {
        const box = document.getElementById('updMsg');
        if (!box) return;
        const map = {info:'alert-info',success:'alert-success',warn:'alert-warning',error:'alert-danger'};
        box.className = 'alert ' + (map[cls] || 'alert-info');
        box.innerHTML = html;
    }

    function setButtonsBusy(busy) {
        ['btnUpdNormal','btnUpdPrebeta'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = !!busy;
        });
    }

    function stateUi(st) {
        const badge = document.getElementById('steamUpdateState');
        const text = document.getElementById('steamUpdateMessage');
        const progress = document.getElementById('steamUpdateProgress');
        const time = document.getElementById('steamUpdateTime');
        if (!badge || !text || !progress) return;

        const state = String(st.state || 'idle').toLowerCase();
        badge.textContent = state.toUpperCase();
        badge.className = 'badge status-badge ' + ({starting:'bg-info',running:'bg-warning text-dark',completed:'bg-success',warning:'bg-warning text-dark',error:'bg-danger',idle:'bg-secondary'}[state] || 'bg-secondary');
        text.textContent = st.message || 'Sin actualización activa.';

        const widths = {idle:0,starting:12,running:70,completed:100,warning:100,error:100};
        progress.style.width = (widths[state] ?? 0) + '%';
        progress.className = 'progress-bar progress-bar-striped ' + ((state === 'starting' || state === 'running') ? 'progress-bar-animated ' : '') + ({completed:'bg-success',warning:'bg-warning',error:'bg-danger',running:'bg-warning',starting:'bg-info'}[state] || '');

        setButtonsBusy(state === 'starting' || state === 'running');

        let parts = [];
        if (st.started_at) parts.push('Inicio: ' + new Date(st.started_at * 1000).toLocaleString());
        if (st.finished_at) parts.push('Fin: ' + new Date(st.finished_at * 1000).toLocaleString());
        if (st.exit_code !== null && st.exit_code !== undefined) parts.push('ExitCode: ' + st.exit_code);
        if (time) time.textContent = parts.join(' · ');

        if (state === 'completed') msg('✅ SteamCMD confirmó que Valheim Dedicated Server quedó completamente instalado/actualizado.', 'success');
        if (state === 'warning') msg('⚠️ SteamCMD terminó, pero no entregó la confirmación esperada. Revisa el log inferior.', 'warn');
        if (state === 'error') msg('❌ La actualización terminó con error. Revisa el log inferior.', 'error');
    }

    window.valheimUpdStatus = function() {
        return fetch('api.php?action=steam_update_status', {credentials:'same-origin', cache:'no-store'})
            .then(async r => {
                const text = await r.text();
                try { return JSON.parse(text); }
                catch(e) { throw new Error('Respuesta inválida del API: ' + text.substring(0,300)); }
            })
            .then(j => {
                if (!j || !j.ok) throw new Error((j && j.error) || 'No se pudo consultar SteamCMD');
                stateUi(j.status || {});
                const con = document.getElementById('steamUpdateConsole');
                if (con) {
                    const lines = Array.isArray(j.log_tail) ? j.log_tail : [];
                    con.textContent = lines.length ? lines.join('\n') : 'Sin actividad todavía.';
                    con.scrollTop = con.scrollHeight;
                }
                return j;
            })
            .catch(err => msg('⚠️ ' + err.message, 'error'));
    };

    window.valheimUpdStart = function(type) {
        if (!confirm('¿Iniciar actualización "' + type + '"?\n\nEl servidor debe estar apagado.')) return;
        setButtonsBusy(true);
        msg('⏳ Preparando actualización con SteamCMD...', 'info');

        const body = new URLSearchParams();
        body.set('type', type);

        fetch('api.php?action=steam_update', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:body.toString(),
            credentials:'same-origin'
        })
        .then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); }
            catch(e) { throw new Error('Respuesta inválida del API: ' + text.substring(0,300)); }
        })
        .then(j => {
            if (!j || !j.ok) throw new Error((j && j.error) || 'No se pudo iniciar la actualización');
            msg('🚀 Actualización iniciada. Esperando a que SteamCMD termine...', 'info');
            window.valheimUpdStatus();
        })
        .catch(err => {
            setButtonsBusy(false);
            msg('❌ ' + err.message, 'error');
        });
    };

    window.valheimUpdCheckServerStatus = function() {
        fetch('api.php?action=status_servers', {credentials:'same-origin', cache:'no-store'})
            .then(r => r.json())
            .then(j => {
                const st = j?.status?.current ?? 'stopped';
                msg(st === 'running' ? '🟢 Servidor activo. Debes detenerlo antes de actualizar.' : '🔴 Servidor detenido. Puedes actualizar.', st === 'running' ? 'warn' : 'success');
            })
            .catch(() => msg('⚠️ No se pudo verificar el estado del servidor.', 'warn'));
    };

    window.valheimUpdStatus();
    window.valheimUpdatePollTimer = setInterval(window.valheimUpdStatus, 2000);
})();
</script>
