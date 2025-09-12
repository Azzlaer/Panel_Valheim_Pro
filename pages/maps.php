<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// --- helpers ---
function listWorldFiles($ext) {
    return glob(WORLDS_DIR . DIRECTORY_SEPARATOR . "*.$ext") ?: [];
}
function formatMB($bytes) {
    return number_format($bytes / 1048576, 2) . ' MB';
}

// Archivos por tipo
$exts = ['fwl' => 'Archivos FWL', 'db' => 'Archivos DB', 'old' => 'Archivos OLD'];
$filesByExt = [];
foreach ($exts as $e => $lbl) {
    $filesByExt[$e] = listWorldFiles($e);
}
?>
<div class="container mt-4">
    <h2>🗺️ Mapas (worlds_local)</h2>

    <!-- ==== Upload ==== -->
    <div class="card bg-dark text-light mb-4">
        <div class="card-header">⬆️ Subir archivos de mundo (.fwl, .db, .old)</div>
        <div class="card-body">
            <form id="mapUploadForm" enctype="multipart/form-data">
                <input type="file" name="mapfile" accept=".fwl,.db,.old" class="form-control mb-2" required>
                <button class="btn btn-primary" type="submit">Subir</button>
            </form>
            <div class="progress mt-2" style="height:20px; display:none;" id="mapUploadBox">
                <div class="progress-bar" id="mapUploadProgress" role="progressbar" style="width:0%">0%</div>
            </div>
            <div id="mapUploadResult" class="mt-2 text-info"></div>
        </div>
    </div>

    <!-- ==== Tablas por tipo ==== -->
    <?php foreach ($exts as $ext => $label): ?>
        <h4 class="mt-4"><?= $label ?></h4>
        <div class="table-responsive">
            <table class="table table-dark table-striped text-center align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tamaño (MB)</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($filesByExt[$ext])): ?>
                    <tr><td colspan="3">📭 No hay archivos .<?= strtoupper($ext) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($filesByExt[$ext] as $f): $base = basename($f); ?>
                        <tr>
                            <td><?= htmlspecialchars($base) ?></td>
                            <td><?= formatMB(filesize($f)) ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm"
                                        onclick="deleteWorldFile('<?= htmlspecialchars($base, ENT_QUOTES) ?>')">
                                    🗑️ Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<script>
function deleteWorldFile(file){
    if(!confirm('¿Eliminar '+file+'?')) return;
    fetch('maps_actions.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=delete&file='+encodeURIComponent(file)
    })
    .then(r=>r.text())
    .then(msg=>{ alert(msg); location.reload(); })
    .catch(()=>alert('Error al eliminar.'));
}

// --- upload ---
document.getElementById('mapUploadForm').addEventListener('submit',function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const box = document.getElementById('mapUploadBox');
    const bar = document.getElementById('mapUploadProgress');
    const res = document.getElementById('mapUploadResult');
    box.style.display='block'; res.innerText='';
    const xhr = new XMLHttpRequest();
    xhr.upload.addEventListener('progress',e=>{
        if(e.lengthComputable){
            const p=Math.round((e.loaded/e.total)*100);
            bar.style.width=p+'%'; bar.innerText=p+'%';
        }
    });
    xhr.onload = function(){
        res.innerText = xhr.responseText;
        if(xhr.status===200) setTimeout(()=>location.reload(),1500);
    };
    xhr.open('POST','maps_upload.php');
    xhr.send(fd);
});
</script>
