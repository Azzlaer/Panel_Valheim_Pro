<?php
require_once __DIR__ . "/../config.php";

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// PID file que usa el panel
define("PID_FILE", __DIR__ . "/../server.pid");

// ==== Detectar si el servidor está online basado en PID guardado ====
function isServerOnlineByPID() {
    return file_exists(PID_FILE) && intval(trim(file_get_contents(PID_FILE))) > 0;
}

// ==== Cargar lista de servidores desde JSON ====
$servers = [];
if (file_exists(SERVERS_JSON)) {
    $servers = json_decode(file_get_contents(SERVERS_JSON), true) ?: [];
}
?>

<div class="container mt-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <h2 class="mb-0">🖥️ Servidor asignado</h2>
        <button class="btn btn-sm btn-outline-light ms-auto" onclick="reloadServersSection()">↻ Refrescar</button>
        <button class="btn btn-sm btn-primary" onclick="openServersJsonEditor()">📝 Editar servers.json</button>
    </div>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Ejecutable</th>
                    <th>Puerto</th>
                    <th>Estado</th>
                    <th>Parámetros</th>
                    <th style="width:160px">Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($servers)): ?>
                    <tr><td colspan="6">⚠️ No existen servidores configurados.</td></tr>

                <?php else: ?>
                    <?php foreach ($servers as $s): ?>

                        <?php
                            // Extraer puerto desde params
                            $port = '';
                            if (!empty($s['params']) && preg_match('/-port\s+(\d+)/', $s['params'], $m)) {
                                $port = $m[1];
                            }

                            $online = isServerOnlineByPID();
                        ?>

                        <tr>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>

                            <td><small><?= htmlspecialchars($s['path']) ?></small></td>

                            <td><?= $port ?: '-' ?></td>

                            <td>
                                <?php if ($online): ?>
                                    <span class="badge bg-success">🟢 ONLINE</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">🔴 OFFLINE</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-start"><small><?= htmlspecialchars($s['params']) ?></small></td>

                            <td>
                                <button class="btn btn-success btn-sm" onclick="serverOp('start', <?= (int)$s['id'] ?>)">🚀 Iniciar</button>
                                <button class="btn btn-danger btn-sm" onclick="serverOp('stop', <?= (int)$s['id'] ?>)">🛑 Detener</button>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function reloadServersSection(){
    fetch('pages/servers.php')
        .then(r=>r.text())
        .then(html=>{ document.getElementById('main').innerHTML = html; });
}

// Editor JSON
function openServersJsonEditor(){
    fetch('api.php?action=get_servers_json')
        .then(r=>r.json())
        .then(j=>{
            if(!j.ok) return alert('❌ ' + j.error);

            const content = prompt("Editar servers.json:", j.content);
            if(content === null) return;

            const form = new FormData();
            form.append('op','savejson');
            form.append('content',content);

            fetch('api.php?action=server',{ method:'POST', body:form })
            .then(r=>r.json())
            .then(x=>{
                alert(x.ok?'✔ Guardado':'❌ '+x.error);
                if(x.ok) reloadServersSection();
            });
        });
}

// Start/Stop
function serverOp(op,id){
    const params=new URLSearchParams({ op,id });
    fetch('api.php?action=server',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:params.toString()
    })
    .then(r=>r.json())
    .then(j=>{
        if(j.ok){
            alert(j.msg);
            setTimeout(reloadServersSection,1000);
        } else {
            alert("❌ "+(j.error||"Error desconocido"));
        }
    })
    .catch(()=>alert("⚠ Error de red"));
}
</script>
