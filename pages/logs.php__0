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
        <div class="mb-3 d-flex gap-2">
    <button class="btn btn-primary" onclick="loadLog('server')">📖 Log del Servidor</button>
    <button class="btn btn-warning" onclick="loadLog('steamcmd')">⚙️ Log de SteamCMD</button>
    <button class="btn btn-danger ms-auto" onclick="clearLog()" id="clearBtn" disabled>🧹 Limpiar Log</button>
</div>

</div>

        <button class="btn btn-outline-light ms-auto" onclick="toggleAutoscroll()" id="autoBtn">🔽 Autoscroll: ON</button>
    </div>

    <pre id="logContent" style="background:#000;color:#0f0;padding:15px;height:420px;overflow-y:auto;border-radius:10px;">Selecciona un log para visualizar…</pre>
</div>

<script>
let currentLog = null;
let logInterval = null;
let autoScroll = true;

function toggleAutoscroll(){
  autoScroll = !autoScroll;
  document.getElementById('autoBtn').textContent =
    '🔽 Autoscroll: ' + (autoScroll ? 'ON' : 'OFF');
}

function loadLog(type){
  currentLog = type;
  document.getElementById('clearBtn').disabled = false;
  clearInterval(logInterval);
  const box = document.getElementById('logContent');
  box.textContent = 'Cargando ' + type + '...';

  function fetchLog(){
    fetch('api.php?action=view_log&file=' + encodeURIComponent(type))
      .then(r=>r.json())
      .then(j=>{
        if(!j.ok) { box.textContent = '❌ ' + (j.error||'Error'); return; }
        box.textContent = j.content;
        if(autoScroll) box.scrollTop = box.scrollHeight;
      })
      .catch(()=> box.textContent = '⚠️ Error leyendo log');
  }

  fetchLog();
  logInterval = setInterval(fetchLog, 3000);
}

function clearLog(){
  if(!currentLog) return;
  if(!confirm('¿Vaciar el log '+currentLog+'?')) return;

  fetch('api.php?action=clear_log', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: 'file=' + encodeURIComponent(currentLog)
  })
  .then(r=>r.json())
  .then(j=>{
      if(j.ok){
          alert('✅ Log limpiado');
          document.getElementById('logContent').textContent = '';
      }else{
          alert('❌ ' + (j.error||'Error'));
      }
  })
  .catch(()=>alert('⚠️ Error de red al limpiar'));
}


</script>


