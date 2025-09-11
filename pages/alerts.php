<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$servers = file_exists(SERVERS_JSON) ? (json_decode(file_get_contents(SERVERS_JSON), true) ?: []) : [];
?>
<div class="container mt-4">
    <h2>🔔 Alertas – Mensajes RCON periódicos</h2>
    <p class="text-muted">
        Programa mensajes personalizados que se enviarán por RCON en intervalos fijos, un número de veces que definas.
    </p>

    <div class="card bg-dark text-light">
        <div class="card-header">Programar alerta</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Servidor</label>
                    <select id="srvId" class="form-select">
                        <?php foreach ($servers as $s): ?>
                            <option value="<?= (int)$s['id'] ?>">
                                <?= htmlspecialchars($s['name'] ?? ('ID ' . (int)$s['id'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12"><hr></div>

                <div class="col-md-8">
                    <label class="form-label">Comando RCON a enviar</label>
                    <input type="text" id="rconCmd" class="form-control" placeholder='Ej: say "Recordatorio: guarda tu progreso"'>
                    <div class="form-text">Escribe el comando completo tal como lo entiende tu RCON (por ejemplo <code>showMessage Texto</code> o <code>say "Texto"</code>).</div>
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
                    <div class="form-text">¿Cuántas veces enviar?</div>
                </div>

                <div class="col-12"><hr></div>

                <div class="col-12">
                    <button class="btn btn-primary" onclick="scheduleAlert()">Programar alerta</button>
                    <small class="text-muted ms-2">Se ejecutará en background (Python).</small>
                </div>
            </div>

            <div id="alertMsg" class="alert mt-3 d-none"></div>
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

function scheduleAlert(){
  const id = document.getElementById('srvId').value;
  const cmd = (document.getElementById('rconCmd').value || '').trim();
  const h  = parseInt(document.getElementById('hours').value || '0', 10);
  const m  = parseInt(document.getElementById('minutes').value || '0', 10);
  const s  = parseInt(document.getElementById('seconds').value || '0', 10);
  const r  = parseInt(document.getElementById('repeats').value || '1', 10);

  if (!cmd) { showMsg('❌ Debes indicar un comando RCON', 'error'); return; }
  const interval = (h*3600) + (m*60) + s;
  if (interval <= 0) { showMsg('❌ El intervalo debe ser mayor a 0 segundos', 'error'); return; }
  if (r < 1) { showMsg('❌ Repeticiones debe ser al menos 1', 'error'); return; }

  const body = new URLSearchParams();
  body.set('server_id', id);
  body.set('command', cmd);
  body.set('interval', interval);
  body.set('repeats', r);
  // body.set('csrf', '<?= $_SESSION['csrf_token'] ?? '' ?>');

  fetch('api.php?action=schedule_alert', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: body.toString(),
    credentials: 'same-origin'
  })
  .then(async r => { const t = await r.text(); try { return JSON.parse(t); } catch(e){ throw new Error(t); } })
  .then(j => {
    if (j && j.ok) showMsg('✅ Alerta programada correctamente.', 'success');
    else showMsg('❌ ' + (j && j.error ? j.error : 'No se pudo programar'), 'error');
  })
  .catch(err => { console.error(err); showMsg('⚠️ Error de red: ' + err.message, 'error'); });
}
</script>
