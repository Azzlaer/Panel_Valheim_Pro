<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>

<style>
.logs-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(168,85,247,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.logs-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.logs-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}

.logs-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.logs-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.logs-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.logs-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}

.logs-wrap .module-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
}

.logs-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.logs-wrap .section-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.logs-wrap .section-header small {
    color: #9ca3af;
}

.logs-wrap .toolbar {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

.logs-wrap .toolbar .btn {
    border-radius: 12px;
    font-weight: 700;
}

.logs-wrap .console-wrap {
    padding: 20px;
}

.logs-wrap .console-box {
    background: #020617;
    color: #22c55e;
    padding: 18px;
    height: 460px;
    overflow-y: auto;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.08);
    font-family: Consolas, Monaco, monospace;
    font-size: 13px;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
    box-shadow: inset 0 0 20px rgba(0,0,0,.35);
}

.logs-wrap .status-badge {
    border-radius: 999px;
    font-weight: 700;
    padding: 8px 12px;
    font-size: 12px;
}

@media (max-width: 991px) {
    .logs-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .logs-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 logs-wrap">

    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>📜 Visor de Logs</h2>
                <p>Consulta en tiempo real los logs del servidor y de SteamCMD desde una consola centralizada.</p>
            </div>
            <div>
                <span class="badge bg-info text-dark status-badge" id="currentLogBadge">Sin log seleccionado</span>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Estado del módulo</div>
            <div class="stat-value text-info">Operativo</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Log activo</div>
            <div class="stat-value" id="activeLogName">N/A</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Autoscroll</div>
            <div class="stat-value" id="autoScrollStatus">ON</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Refresco</div>
            <div class="stat-value">3s</div>
        </div>
    </div>

    <div class="module-card">
        <div class="section-header">
            <div>
                <h3>🖥️ Consola de eventos</h3>
                <small>Selecciona el origen del log y consulta su contenido actualizado automáticamente.</small>
            </div>
        </div>

        <div class="toolbar">
            <button class="btn btn-primary" onclick="loadLog('server')">📖 Log del Servidor</button>
            <button class="btn btn-warning" onclick="loadLog('steamcmd')">⚙️ Log de SteamCMD</button>
            <button class="btn btn-outline-light ms-auto" onclick="toggleAutoscroll()" id="autoBtn">🔽 Autoscroll: ON</button>
            <button class="btn btn-danger" onclick="clearLog()" id="clearBtn" disabled>🧹 Limpiar Log</button>
        </div>

        <div class="console-wrap">
            <pre id="logContent" class="console-box">Selecciona un log para visualizar…</pre>
        </div>
    </div>
</div>

<script>
let currentLog = null;
let logInterval = null;
let autoScroll = true;

function updateLogUI() {
  document.getElementById('autoBtn').textContent = '🔽 Autoscroll: ' + (autoScroll ? 'ON' : 'OFF');
  document.getElementById('autoScrollStatus').textContent = autoScroll ? 'ON' : 'OFF';
  document.getElementById('activeLogName').textContent = currentLog ? currentLog.toUpperCase() : 'N/A';

  const badge = document.getElementById('currentLogBadge');
  if (!currentLog) {
    badge.className = 'badge bg-info text-dark status-badge';
    badge.textContent = 'Sin log seleccionado';
  } else if (currentLog === 'server') {
    badge.className = 'badge bg-primary status-badge';
    badge.textContent = 'Log del Servidor';
  } else if (currentLog === 'steamcmd') {
    badge.className = 'badge bg-warning text-dark status-badge';
    badge.textContent = 'Log de SteamCMD';
  }
}

function toggleAutoscroll(){
  autoScroll = !autoScroll;
  updateLogUI();
}

function loadLog(type){
  currentLog = type;
  document.getElementById('clearBtn').disabled = false;
  clearInterval(logInterval);

  const box = document.getElementById('logContent');
  box.textContent = 'Cargando ' + type + '...';

  updateLogUI();

  function fetchLog(){
    fetch('api.php?action=view_log&file=' + encodeURIComponent(type), {
      credentials:'same-origin'
    })
      .then(r => r.json())
      .then(j => {
        if(!j.ok) {
          box.textContent = '❌ ' + (j.error || 'Error');
          return;
        }
        box.textContent = j.content || '';
        if(autoScroll) box.scrollTop = box.scrollHeight;
      })
      .catch(() => {
        box.textContent = '⚠️ Error leyendo log';
      });
  }

  fetchLog();
  logInterval = setInterval(fetchLog, 3000);
}

function clearLog(){
  if(!currentLog) return;
  if(!confirm('¿Vaciar el log ' + currentLog + '?')) return;

  fetch('api.php?action=clear_log', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: 'file=' + encodeURIComponent(currentLog),
      credentials:'same-origin'
  })
  .then(r => r.json())
  .then(j => {
      if(j.ok){
          alert('✅ Log limpiado');
          document.getElementById('logContent').textContent = '';
      } else {
          alert('❌ ' + (j.error || 'Error'));
      }
  })
  .catch(() => alert('⚠️ Error de red al limpiar'));
}

updateLogUI();
</script>