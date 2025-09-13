<?php
require_once __DIR__ . "/../config.php";

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

$disabledDir = PLUGINS_DIR;

// Archivos habilitados (.dll)
$plugins = glob(PLUGINS_DIR . DIRECTORY_SEPARATOR . "*.dll") ?: [];
// Archivos deshabilitados (.disable)
$disabled = glob(PLUGINS_DIR . DIRECTORY_SEPARATOR . "*.disable") ?: [];
?>
<div class="container mt-4">
    <h2>📊 Archivos DB en Plugins</h2>

    <!-- Subir DLL -->
    <div class="mb-4">
        <h5>➕ Subir nuevo Plugin (.dll)</h5>
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="input-group">
                <input type="file" name="dllfile" accept=".dll" class="form-control" required>
                <button class="btn btn-primary" type="submit">Subir</button>
            </div>
        </form>
        <div class="progress mt-2" style="height:20px; display:none;">
            <div class="progress-bar" role="progressbar" style="width:0%">0%</div>
        </div>
    </div>

    <!-- Plugins habilitados -->
    <h5>✅ Plugins habilitados</h5>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tamaño (KB)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plugins)): ?>
                    <tr><td colspan="3">⚠️ No hay plugins habilitados.</td></tr>
                <?php else: ?>
                    <?php foreach ($plugins as $file): ?>
                        <tr>
                            <td><?= htmlspecialchars(basename($file)) ?></td>
                            <td><?= round(filesize($file)/1024,2) ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="togglePlugin('<?= basename($file) ?>','disable')">Deshabilitar</button>
                                <button class="btn btn-danger btn-sm" onclick="deletePlugin('<?= basename($file) ?>')">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Plugins deshabilitados -->
    <h5 class="mt-4">🚫 Plugins deshabilitados</h5>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tamaño (KB)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($disabled)): ?>
                    <tr><td colspan="3">✅ No hay plugins deshabilitados.</td></tr>
                <?php else: ?>
                    <?php foreach ($disabled as $file): ?>
                        <tr>
                            <td><?= htmlspecialchars(basename($file)) ?></td>
                            <td><?= round(filesize($file)/1024,2) ?></td>
                            <td>
                                <button class="btn btn-success btn-sm" onclick="togglePlugin('<?= basename($file) ?>','enable')">Habilitar</button>
                                <button class="btn btn-danger btn-sm" onclick="deletePlugin('<?= basename($file) ?>')">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const progressBar = document.querySelector('.progress');
const progressInner = document.querySelector('.progress-bar');

document.getElementById('uploadForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    progressBar.style.display='block';
    fetch('pages/plugins_upload.php', {
        method: 'POST',
        body: formData
    }).then(r=>r.json()).then(j=>{
        if(j.ok){
            location.reload();
        }else{
            alert('❌ '+j.error);
        }
    }).catch(()=>alert('Error al subir el archivo'));
});

function deletePlugin(file){
    if(!confirm('¿Eliminar '+file+'?')) return;
    fetch('pages/plugins_manage.php?action=delete&file='+encodeURIComponent(file), {method:'POST'})
    .then(r=>r.json()).then(j=>{
        if(j.ok) location.reload();
        else alert('❌ '+j.error);
    });
}

function togglePlugin(file, mode){
    fetch('pages/plugins_manage.php?action='+mode+'&file='+encodeURIComponent(file), {method:'POST'})
    .then(r=>r.json()).then(j=>{
        if(j.ok) location.reload();
        else alert('❌ '+j.error);
    });
}
</script>
