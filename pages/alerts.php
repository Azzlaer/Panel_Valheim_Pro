<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$servers = file_exists(SERVERS_JSON) ? (json_decode(file_get_contents(SERVERS_JSON), true) ?: []) : [];
$serverMap = [];
foreach ($servers as $s) { $serverMap[intval($s['id'])] = $s['name'] ?? ('ID ' . intval($s['id'])); }
?>
<div class="container mt-4">
    <h2>🔔 Alertas – Mensajes RCON periódicos</h2>
    <p class="text-muted">Programa mensajes personalizados y administra alertas activas.</p>

    <div class="card bg-dark text-light mb-4">
        <div class="card-header">Programar nueva alerta</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Servidor</label>
                    <select id="srvId" class="form-select">
                        <?php foreach ($servers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name'] ?? ('ID ' . (int)$s['id'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Comando RCON</label>
                    <input type="text" id="rconCmd" class="form-control" placeholder='Ej: showMessage "Recuerden guardar su progreso"'>
                </div>

                <div class="col-12"><hr></div>

                <div class="col-md-2">
                    <label class="form-label">Cada (horas)</label>
                    <input type="number" min="0" value="0" id="hours" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cada (min)</label>
                    <input type="number" min="0" value="10" id="minutes" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cada (seg)</label>
                    <input type="number" min="0" value="0" id="seconds" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Repeticiones</label>
                    <input type="number" min="1" value="5" id="repeats" class="form-control">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" onclick="createAlert()">➕ Crear alerta</button>
                    <small class="text-muted ms-2">El worker ejecutará las alertas según su programación.</small>
                </div>

                <div id="alertMsg" class="alert mt-3 d-none"></div>
            </div>
        </div>
    </div>

    <div class="card bg-dark text-light">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Alertas activas</span>
            <button class="btn btn-sm btn-outline-light" onclick="loadAlerts()">↻ Actualizar</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Servidor</th>
                            <th>Comando</th>
                            <th>Intervalo</th>
                            <th>Restantes</th>
                            <th>Próxima ejecución</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="alertsBody">
                        <tr><td colspan="8" class="text-center">Cargando…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function showMsg(html, type='info'){
  const box = document.getElementById('alertMsg');
  const map = {info:'alert-info', success:'alert-success', warn:'alert-warning', error:'alert-danger'};
  box.className = 'alert mt-3 ' + (map[type] || 'alert-info');
  box.innerHTML = html;
  box.classList.remove('d-none');
}

function secsToStr(s){
  s = parseInt(s||0,10);
  const h=Math.floor(s/3600), m=Math.floor((s%3600)/60), ss=s%60;
  const parts=[];
  if(h) parts.push(h+'h'); if(m) parts.push(m+'m'); if(ss||!parts.length) parts.push(ss+'s');
  return parts.join(' ');
}
function tsToLocal(ts){
  if(!ts) return '-';
  const d = new Date(ts*1000);
  return d.toLocaleString();
}

function createAlert(){
  const id = document.getElementById('srvId').value;
  const cmd = (document.getElementById('rconCmd').value||'').trim();
  const h = parseInt(document.getElementById('hours').value||'0',10);
  const m = parseInt(document.getElementById('minutes').value||'0',10);
  const s = parseInt(document.getElementById('seconds').value||'0',10);
  const r = parseInt(document.getElementById('repeats').value||'1',10);

  const interval = h*3600 + m*60 + s;
  if(!cmd) return showMsg('❌ Debes indicar un comando RCON', 'error');
  if(interval<=0) return showMsg('❌ Intervalo inválido', 'error');
  if(r<1) return showMsg('❌ Repeticiones inválidas', 'error');

  const body = new URLSearchParams();
  body.set('server_id', id);
  body.set('command', cmd);
  body.set('interval', interval);
  body.set('repeats', r);
  // body.set('csrf', '<?= $_SESSION['csrf_token'] ?? '' ?>');

  fetch('api.php?action=alert_create', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString(),
    credentials: 'same-origin'
  })
  .then(r=>r.json())
  .then(j=>{
    if(j && j.ok){ showMsg('✅ Alerta creada: '+j.id, 'success'); loadAlerts(); }
    else showMsg('❌ '+(j?.error || 'No se pudo crear'), 'error');
  })
  .catch(e=>showMsg('⚠️ Error de red: '+e.message, 'error'));
}

function loadAlerts(){
  fetch('api.php?action=alerts_list', { credentials:'same-origin' })
  .then(r=>r.json())
  .then(j=>{
    const body = document.getElementById('alertsBody');
    if(!j || !j.ok) { body.innerHTML = `<tr><td colspan="8" class="text-center text-danger">❌ Error</td></tr>`; return; }
    const items = j.items || [];
    if(items.length===0){ body.innerHTML = `<tr><td colspan="8" class="text-center">📭 Sin alertas</td></tr>`; return; }
    body.innerHTML = items.map(it=>{
      const st = it.status;
      const badge = st==='active' ? 'success' : st==='paused' ? 'warning' : st==='done' ? 'secondary' : st==='canceled' ? 'danger' : 'light';
      return `
        <tr>
          <td><code>${it.id}</code></td>
          <td>${<?= json_encode($serverMap) ?>[it.server_id] ?? ('ID '+it.server_id)}</td>
          <td><code>${(it.command||'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#039;"}[c]))}</code></td>
          <td>${secsToStr(it.interval)}</td>
          <td>${it.repeats_left}/${it.repeats_total}</td>
          <td>${tsToLocal(it.next_run_ts)}</td>
          <td><span class="badge bg-${badge}">${st}</span></td>
          <td>
            ${(st==='active') ? `<button class="btn btn-sm btn-danger" onclick="cancelAlert('${it.id}')">🛑 Cancelar</button>` : ''}
          </td>
        </tr>`;
    }).join('');
  })
  .catch(()=>{ document.getElementById('alertsBody').innerHTML = `<tr><td colspan="8" class="text-center text-warning">⚠️ Error de red</td></tr>`; });
}

function cancelAlert(id){
  if(!confirm('¿Cancelar esta alerta?')) return;
  const body = new URLSearchParams();
  body.set('id', id);
  // body.set('csrf', '<?= $_SESSION['csrf_token'] ?? '' ?>');
  fetch('api.php?action=alert_cancel', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString(), credentials:'same-origin'
  })
  .then(r=>r.json())
  .then(j=>{ if(j && j.ok) loadAlerts(); else alert('❌ '+(j?.error || 'No se pudo cancelar')); })
  .catch(()=>alert('⚠️ Error de red'));
}

// Auto-cargar
loadAlerts();
</script>
