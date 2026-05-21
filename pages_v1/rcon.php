<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>

<style>
.rcon-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(59,130,246,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.rcon-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.rcon-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}

.rcon-wrap .hero-actions .btn,
.rcon-wrap .hero-actions a.btn {
    border-radius: 12px;
    font-weight: 600;
}

.rcon-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.rcon-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.rcon-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.rcon-wrap .stat-value {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    line-height: 1.4;
    word-break: break-word;
}

.rcon-wrap .module-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
    margin-bottom: 22px;
}

.rcon-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.rcon-wrap .section-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.rcon-wrap .section-header small {
    color: #9ca3af;
}

.rcon-wrap .section-body {
    padding: 20px;
}

.rcon-wrap .form-control,
.rcon-wrap .form-control:focus {
    background: #0b1220;
    color: #e5e7eb;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: none;
    border-radius: 14px 0 0 14px;
}

.rcon-wrap .input-group .btn {
    border-radius: 0 14px 14px 0;
    font-weight: 700;
}

.rcon-wrap .toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.rcon-wrap .toolbar .btn {
    border-radius: 12px;
    font-weight: 700;
}

.rcon-wrap .console-box {
    background: #020617;
    color: #22c55e;
    padding: 18px;
    height: 430px;
    overflow-y: auto;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.08);
    font-family: Consolas, Monaco, monospace;
    font-size: 13px;
    line-height: 1.55;
    white-space: pre-wrap;
    word-break: break-word;
    box-shadow: inset 0 0 20px rgba(0,0,0,.35);
    margin: 0;
}

.rcon-wrap .helper-text {
    color: #9ca3af;
    font-size: 13px;
    margin-top: 10px;
}

@media (max-width: 991px) {
    .rcon-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .rcon-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 rcon-wrap">

    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>🕹️ Consola RCON</h2>
                <p>Envía comandos remotos al servidor Valheim y visualiza la respuesta en una terminal integrada.</p>
            </div>
            <div class="hero-actions d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-light btn-sm"
                   href="https://github.com/Tristan-dvr/ValheimRcon/blob/master/commands.md"
                   target="_blank" rel="noopener noreferrer">
                   📘 Ver comandos
                </a>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Host RCON</div>
            <div class="stat-value"><?= htmlspecialchars(RCON_HOST) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Puerto RCON</div>
            <div class="stat-value"><?= htmlspecialchars((string)RCON_PORT) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Servidor</div>
            <div class="stat-value"><?= htmlspecialchars(defined('SERVER_NAME') ? SERVER_NAME : 'Valheim') ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Modo</div>
            <div class="stat-value text-info">Comandos remotos</div>
        </div>
    </div>

    <div class="module-card">
        <div class="section-header">
            <div>
                <h3>📡 Envío de comandos</h3>
                <small>Requiere que el mod ValheimRcon esté activo y correctamente configurado en el servidor.</small>
            </div>
        </div>

        <div class="section-body">
            <div class="toolbar">
                <button class="btn btn-outline-light btn-sm" onclick="clearRconOutput()">🧹 Limpiar consola</button>
            </div>

            <div class="input-group mb-2">
                <input
                    type="text"
                    id="rconCommand"
                    class="form-control"
                    placeholder="Escribe un comando RCON…"
                    autocomplete="off">
                <button class="btn btn-primary" onclick="sendRcon()">▶️ Enviar</button>
            </div>

            <div class="helper-text">
                Presiona <strong>Enter</strong> para enviar rápidamente el comando.
            </div>

            <pre id="rconOutput" class="console-box">--- Esperando comandos RCON ---</pre>
        </div>
    </div>
</div>

<script>
function appendRconLine(text) {
    const outBox = document.getElementById('rconOutput');
    outBox.textContent += text;
    outBox.scrollTop = outBox.scrollHeight;
}

function clearRconOutput() {
    document.getElementById('rconOutput').textContent = '--- Consola limpiada ---\n';
}

function sendRcon(){
    const input = document.getElementById('rconCommand');
    const cmd = input.value.trim();
    if (!cmd) {
        alert('Ingresa un comando RCON');
        return;
    }

    appendRconLine("\n> " + cmd + "\nEnviando...\n");

    fetch('api.php?action=rcon_send', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'command=' + encodeURIComponent(cmd)
    })
    .then(r => r.json())
    .then(j => {
        if (j && j.ok) {
            appendRconLine((j.response || '[Sin respuesta]') + "\n");
        } else {
            appendRconLine("❌ Error: " + (j && j.error ? j.error : 'Desconocido') + "\n");
        }
    })
    .catch(err => {
        console.error(err);
        appendRconLine("⚠️ Error de red\n");
    });

    input.value = '';
    input.focus();
}

document.getElementById('rconCommand').addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
        e.preventDefault();
        sendRcon();
    }
});
</script>