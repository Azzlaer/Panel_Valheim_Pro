<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>
<div class="container mt-4">
    <h2>📜 Visor de Logs</h2>

    <div class="mb-3 d-flex gap-2">
        <button class="btn btn-primary" onclick="loadLog('server')">📖 Log del Servidor</button>
        <button class="btn btn-warning" onclick="loadLog('steamcmd')">⚙️ Log de SteamCMD</button>
        <button class="btn btn-outline-light ms-auto" onclick="toggleAutoscroll()" id="autoBtn">🔽 Autoscroll: ON</button>
    </div>

    <pre id="logContent" style="background:#000;color:#0f0;padding:15px;height:420px;overflow-y:auto;border-radius:10px;">Selecciona un log para visualizar…</pre>
</div>

<script>
let logInterval = null;
let autoScroll = true;

function toggleAutoscroll(){
  autoScroll = !autoScroll;
  document.getElementById('autoBtn').textContent = '🔽 Autoscroll: ' + (autoScroll ? 'ON' : 'OFF');
}

function loadLog(type) {
    clearInterval(logInterval);
    const box = document.getElementById("logContent");
    box.textContent = "Cargando " + type + "...";

    function fetchLog() {
        fetch("api.php?action=view_log&file=" + encodeURIComponent(type), {
            credentials: 'same-origin'
        })
        .then(async r => {
            const txt = await r.text();
            // El API devuelve JSON; intentamos parsear para mostrar errores reales
            try {
                const j = JSON.parse(txt);
                if (!j.ok) {
                    box.textContent = "❌ " + (j.error || "Error desconocido");
                    return;
                }
                box.textContent = j.content || '';
            } catch(e) {
                // Si no es JSON, mostramos crudo (por si cambió el API)
                console.warn('Respuesta no-JSON de api.php?action=view_log:', txt);
                box.textContent = txt;
            }
            if (autoScroll) box.scrollTop = box.scrollHeight;
        })
        .catch(err => {
            console.error('Error leyendo log:', err);
            box.textContent = "⚠️ Error leyendo log.";
        });
    }

    fetchLog();
    logInterval = setInterval(fetchLog, 3000);
}
</script>
