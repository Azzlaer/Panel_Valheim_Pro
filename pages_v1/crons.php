<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$servers = file_exists(SERVERS_JSON) ? (json_decode(file_get_contents(SERVERS_JSON), true) ?: []) : [];
?>
<div class="container mt-4">
    <h2>⏱️ Tareas programadas (Cron) – Reinicio con anuncio</h2>
    <p class="text-muted">
        Programa un reinicio con cuenta regresiva y anuncios automáticos por RCON (5 min, 1 min, 10 s).  
        Opcionalmente, se enviará <code>save</code> antes de apagar el servidor.
    </p>

    <div class="card bg-dark text-light mb-4">
        <div class="card-header">Programar reinicio</div>
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
                <div class="col-md-2">
                    <label class="form-label">Horas</label>
                    <input type="number" min="0" value="0" id="hours" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Minutos</label>
                    <input type="number" min="0" value="5" id="minutes" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Segundos</label>
                    <input type="number" min="0" value="0" id="seconds" class="form-control">
                </div>
                <div class="col-12">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="saveBefore" checked>
                        <label class="form-check-label" for="saveBefore">
                            Guardar antes de la acción seleccionada (enviar <code>save</code> vía RCON)
                        </label>
                    </div>
                </div>
                <div class="col-12"><hr></div>
                <div class="col-12">
                    <button class="btn btn-primary" onclick="scheduleRestart()">Programar reinicio</button>
                    <small class="text-muted ms-2">Se anunciará automáticamente a 5m, 1m y 10s.</small>
                </div>
            </div>

            <div id="cronMsg" class="alert mt-3 d-none"></div>
        </div>
    </div>

    <div class="card bg-dark text-light">
        <div class="card-header">Notas</div>
        <div class="card-body">
            <ul class="mb-0">
                <li>El proceso corre en segundo plano (Python). No necesitas dejar esta página abierta.</li>
                <li>Para ver el progreso, mira el log del servidor y/o el log de SteamCMD.</li>
            </ul>
        </div>
    </div>
</div>

<script>
function showMsg(html, type='info'){
    const box = document.getElementById('cronMsg');
    box.className = 'alert mt-3 alert-' + (type==='success'?'success':type==='error'?'danger':type==='warn'?'warning':'info');
    box.innerHTML = html;
    box.classList.remove('d-none');
}

function scheduleRestart(){
    const id = document.getElementById('srvId').value;
    const h  = parseInt(document.getElementById('hours').value || '0', 10);
    const m  = parseInt(document.getElementById('minutes').value || '0', 10);
    const s  = parseInt(document.getElementById('seconds').value || '0', 10);
    const save = document.getElementById('saveBefore').checked ? 1 : 0;

    const total = (h*3600) + (m*60) + s;
    if (isNaN(total) || total < 0) {
        showMsg('❌ Tiempo inválido', 'error');
        return;
    }

    const body = new URLSearchParams();
    body.set('server_id', id);
    body.set('delay', total);
    body.set('save_before', save);
    // Si quieres forzar CSRF:
    // body.set('csrf','<?= $_SESSION['csrf_token'] ?? '' ?>');

    fetch('api.php?action=schedule_restart', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: body.toString(),
        credentials: 'same-origin'
    })
    .then(async r => {
        const txt = await r.text();
        try { return JSON.parse(txt); } catch(e){ throw new Error(txt); }
    })
    .then(j => {
        if (j && j.ok) {
            showMsg('✅ Reinicio programado. Se enviarán anuncios por RCON automáticamente.', 'success');
        } else {
            showMsg('❌ ' + (j && j.error ? j.error : 'No se pudo programar'), 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showMsg('⚠️ Error de red: ' + err.message, 'error');
    });
}
</script>
