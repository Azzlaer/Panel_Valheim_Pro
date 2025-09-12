<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// --- funciones auxiliares ---
function listFiles($dir, $ext) {
    return glob($dir . DIRECTORY_SEPARATOR . "*.$ext") ?: [];
}
$dllFiles      = listFiles(PLUGINS_DIR, "dll");
$disabledFiles = listFiles(PLUGINS_DIR, "DISABLED");
?>
<div class="container mt-4">
    <h2>📊 Archivos DB en Plugins</h2>
    <p>Total encontrados: <b><?= count($dllFiles) ?></b></p>

    <!-- ==== Subir plugin ==== -->
    <div class="card bg-dark text-light mb-4">
        <div class="card-header">⬆️ Subir nuevo plugin (.dll)</div>
        <div class="card-body">
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="file" name="plugin" accept=".dll" class="form-control mb-2" required>
                <button class="btn btn-primary" type="submit">Subir</button>
            </form>
            <div class="progress mt-2" style="height:20px; display:none;" id="uploadProgressBox">
                <div class="progress-bar" id="uploadProgress" role="progressbar" style="width:0%">0%</div>
            </div>
            <div id="uploadResult" class="mt-2 text-info"></div>
        </div>
    </div>

    <!-- ==== Tabla de plugins habilitados ==== -->
    <div class="table-responsive">
        <table class="table table-dark table-striped text-center align-middle">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Ruta Completa</th>
                    <th>Tamaño</th>
                    <th>Estado</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dllFiles)): ?>
                    <tr><td colspan="5">⚠️ No se encontraron archivos .dll</td></tr>
                <?php else: ?>
                    <?php foreach ($dllFiles as $file): $base=basename($file); ?>
                        <tr>
                            <td><?= htmlspecialchars($base) ?></td>
                            <td class="text-start"><?= htmlspecialchars($file) ?></td>
                            <td><?= filesize($file) ?> bytes</td>
                            <td>
                                <button class="btn btn-warning btn-sm"
                                    onclick="togglePlugin('disable','<?= htmlspecialchars($base,ENT_QUOTES) ?>')">
                                    Deshabilitar
                                </button>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm"
                                    onclick="deletePlugin('<?= htmlspecialchars($base,ENT_QUOTES) ?>')">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ==== Plugins deshabilitados ==== -->
    <h4 class="mt-5">🛑 Plugins Deshabilitados</h4>
    <div class="table-responsive">
        <table class="table table-dark table-bordered text-center align-middle">
            <thead>
                <tr><th>Archivo</th><th>Ruta</th><th>Habilitar</th></tr>
            </thead>
            <tbody>
            <?php if (empty($disabledFiles)): ?>
                <tr><td colspan="3">Ningún plugin deshabilitado</td></tr>
            <?php else: ?>
                <?php foreach ($disabledFiles as $file): $base=basename($file); ?>
                    <tr>
                        <td><?= htmlspecialchars($base) ?></td>
                        <td class="text-start"><?= htmlspecialchars($file) ?></td>
                        <td>
                            <button class="btn btn-success btn-sm"
                                onclick="togglePlugin('enable','<?= htmlspecialchars($base,ENT_QUOTES) ?>')">
                                Habilitar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function ajaxPost(action, file){
  fetch('plugin_actions.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'action='+action+'&file='+encodeURIComponent(file)
  }).then(r=>r.text())
   .then(msg=>{ alert(msg); location.reload(); })
   .catch(()=>alert('Error en la operación'));
}
function deletePlugin(file){ if(confirm('Eliminar '+file+'?')) ajaxPost('delete',file); }
function togglePlugin(action,file){ ajaxPost(action,file); }

// ---- Upload con barra ----
document.getElementById('uploadForm').addEventListener('submit',function(e){
  e.preventDefault();
  const fd = new FormData(this);
  const xhr = new XMLHttpRequest();
  const box=document.getElementById('uploadProgressBox');
  const bar=document.getElementById('uploadProgress');
  const res=document.getElementById('uploadResult');
  box.style.display='block'; res.innerText='';
  xhr.upload.addEventListener('progress',e=>{
    if(e.lengthComputable){
      const p=Math.round((e.loaded/e.total)*100);
      bar.style.width=p+'%'; bar.innerText=p+'%';
    }
  });
  xhr.onload=function(){
    res.innerText=xhr.responseText;
    if(xhr.status===200) setTimeout(()=>location.reload(),1500);
  };
  xhr.open('POST','upload_plugin.php');
  xhr.send(fd);
});
</script>
