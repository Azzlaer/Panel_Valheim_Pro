<?php
require_once __DIR__ . "/../config.php";  // config.php ya se encarga de la sesión

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// ===== Helpers =====
function isRunning($exe){
    $out = shell_exec("tasklist /FI \"IMAGENAME eq $exe\"");
    return strpos($out, $exe) !== false;
}

$servers = [];
if (file_exists(SERVERS_JSON)) {
    $servers = json_decode(file_get_contents(SERVERS_JSON), true);
}
$running = isRunning('valheim_server.exe'); // estado global aproximado
?>
<div class="container mt-4">
  <div class="d-flex align-items-center gap-2 mb-3">
    <h2 class="mb-0">🖥️ Servidores</h2>
    <span class="badge <?= $running ? 'bg-success' : 'bg-danger' ?> ms-2">
        <?= $running ? '✅ En ejecución' : '🛑 Apagado' ?>
    </span>
    <button class="btn btn-sm btn-outline-light ms-auto" onclick="reloadServersSection()">↻ Refrescar</button>
    <button class="btn btn-sm btn-primary" onclick="openServersJsonEditor()">📝 Editar servers.json</button>
  </div>

  <div class="table-responsive">
    <table class="table table-dark table-striped align-middle text-center">
      <thead>
        <tr>
          <th style="min-width:200px">Nombre</th>
          <th>Ejecutable</th>
          <th>Parámetros</th>
          <th style="width:160px">Acciones</th>
        </tr>
      </thead>
      <tbody id="serversBody">
        <?php if (empty($servers)): ?>
          <tr><td colspan="4">⚠️ No hay servidores en <code>servers.json</code></td></tr>
        <?php else: foreach($servers as $s): ?>
          <tr>
            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
            <td><small><?= htmlspecialchars($s['path']) ?></small></td>
            <td class="text-start"><small><?= htmlspecialchars($s['params']) ?></small></td>
            <td>
              <button class="btn btn-success btn-sm" onclick="serverOp('start', <?= (int)$s['id'] ?>)">🚀 Iniciar</button>
              <button class="btn btn-danger btn-sm" onclick="serverOp('stop', <?= (int)$s['id'] ?>)">🛑 Detener</button>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Editor servers.json -->
<div class="modal fade" id="serversJsonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title">📝 Editar servers.json</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <textarea id="serversJsonText" style="width:100%;height:400px"></textarea>
        <div class="small text-muted mt-2">Consejo: valida el JSON antes de guardar. Se comprobará en el servidor.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="saveServersJson()">💾 Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
function reloadServersSection(){
  fetch('pages/servers.php')
    .then(r=>r.text())
    .then(html=>{ document.getElementById('main').innerHTML = html; });
}

function serverOp(op, id){
  const params = new URLSearchParams();
  params.set('op', op);
  if (id !== undefined) params.set('id', id);

  fetch('api.php?action=server', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params.toString()
  })
  .then(r=>r.json())
  .then(j=>{
    if (j && j.ok) {
      alert('Operación enviada');
      reloadServersSection();
    } else {
      alert('Error: ' + (j && j.error ? j.error : 'desconocido'));
    }
  })
  .catch(()=>alert('⚠️ Error de red'));
}

function openServersJsonEditor(){
  fetch('api.php?action=get_servers_json')
    .then(r => r.json())
    .then(j => {
      if (j.ok) {
        document.getElementById('serversJsonText').value = j.content;
        new bootstrap.Modal(document.getElementById('serversJsonModal')).show();
      } else {
        alert('⚠️ ' + (j.error || 'Error al cargar servers.json'));
      }
    })
    .catch(()=>alert('⚠️ Error de red'));
}


function saveServersJson(){
  const content = document.getElementById('serversJsonText').value;
  try { JSON.parse(content); } catch(e){ alert('JSON inválido: '+e.message); return; }

  const params = new URLSearchParams();
  params.set('op', 'savejson');
  params.set('content', content);

  fetch('api.php?action=server', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params.toString()
  })
  .then(r=>r.json())
  .then(j=>{
    if (j && j.ok) {
      alert('✅ Guardado');
      bootstrap.Modal.getInstance(document.getElementById('serversJsonModal')).hide();
      reloadServersSection();
    } else {
      alert('❌ Error: ' + (j && j.error ? j.error : 'desconocido'));
    }
  })
  .catch(()=>alert('⚠️ Error de red al guardar'));
}
</script>
