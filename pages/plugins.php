<?php
require_once __DIR__ . "/../config.php";

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// Listas
$pluginsEnabled  = glob(PLUGINS_DIR . DIRECTORY_SEPARATOR . "*.dll") ?: [];
$pluginsDisabled = glob(PLUGINS_DIR . DIRECTORY_SEPARATOR . "*.disable") ?: [];
$pluginDBs       = glob(PLUGINS_DIR . DIRECTORY_SEPARATOR . "*.db") ?: [];
?>
<div class="container mt-4">
    <h2>📊 Archivos en Plugins</h2>
    <p class="text-muted small mb-4">
        Carpeta: <code><?= htmlspecialchars(PLUGINS_DIR) ?></code>
    </p>

    <!-- Subida -->
    <div class="card bg-dark text-light mb-4">
        <div class="card-header">➕ Subir archivo</div>
        <div class="card-body">
            <form id="pluginUploadForm" enctype="multipart/form-data" onsubmit="return false;">
                <div class="row g-2">
                    <div class="col-md-8">
                        <input type="file" name="plugin" id="pluginFile" class="form-control"
                               accept=".dll,.db" required>
                        <div class="form-text text-muted"><span style="color: #ffffff;">Se permiten <code>.dll</code> (mods) </span></div>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button class="btn btn-primary" type="button" id="btnUploadPlugin">Subir</button>
                    </div>
                </div>
            </form>

            <div class="progress mt-3" id="uploadProgressWrapper" style="height:22px; display:none;">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     id="uploadProgressBar" style="width:0%">0%</div>
            </div>
            <div id="uploadResult" class="mt-2 small"></div>
        </div>
    </div>

    <!-- DLL habilitados -->
    <h5>✅ Plugins habilitados (.dll)</h5>
    <div class="table-responsive mb-4">
        <table class="table table-dark table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tamaño (KB)</th>
                    <th style="width:220px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pluginsEnabled)): ?>
                <tr><td colspan="3">⚠️ No hay plugins habilitados.</td></tr>
            <?php else: foreach ($pluginsEnabled as $file): ?>
                <tr>
                    <td class="text-start"><?= htmlspecialchars(basename($file)) ?></td>
                    <td><?= number_format(filesize($file)/1024, 2) ?></td>
                    <td>
                        <button class="btn btn-warning btn-sm"
                                onclick="togglePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>','disable')">
                            Deshabilitar
                        </button>
                        <button class="btn btn-danger btn-sm ms-1"
                                onclick="deletePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>')">
                            Eliminar
                        </button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- DLL deshabilitados -->
    <h5>🚫 Plugins deshabilitados (.disable)</h5>
    <div class="table-responsive mb-4">
        <table class="table table-dark table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tamaño (KB)</th>
                    <th style="width:220px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pluginsDisabled)): ?>
                <tr><td colspan="3">✅ No hay plugins deshabilitados.</td></tr>
            <?php else: foreach ($pluginsDisabled as $file): ?>
                <tr>
                    <td class="text-start"><?= htmlspecialchars(basename($file)) ?></td>
                    <td><?= number_format(filesize($file)/1024, 2) ?></td>
                    <td>
                        <button class="btn btn-success btn-sm"
                                onclick="togglePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>','enable')">
                            Habilitar
                        </button>
                        <button class="btn btn-danger btn-sm ms-1"
                                onclick="deletePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>')">
                            Eliminar
                        </button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>


<script>
// ------- helpers -------
function reloadSection() {
  // recarga solo esta sección dentro del dashboard
  if (window.jQuery) {
    $('#main').load('pages/plugins.php');
  } else {
    // fallback
    fetch('pages/plugins.php',{credentials:'same-origin'})
      .then(r=>r.text()).then(html=>{ document.getElementById('main').innerHTML = html; });
  }
}

// ------- subir con progreso (XHR) -------
(function(){
  const form   = document.getElementById('pluginUploadForm');
  const fileIn = document.getElementById('pluginFile');
  const btn    = document.getElementById('btnUploadPlugin');
  const wrap   = document.getElementById('uploadProgressWrapper');
  const bar    = document.getElementById('uploadProgressBar');
  const out    = document.getElementById('uploadResult');

  btn.addEventListener('click', function(){
    if (!fileIn.files.length) { alert('Selecciona un archivo .dll o .db'); return; }

    const fd = new FormData();
    fd.append('plugin', fileIn.files[0]);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'pages/plugins_upload.php', true);

    xhr.upload.addEventListener('progress', (e)=>{
      if (e.lengthComputable) {
        const pct = Math.round(e.loaded * 100 / e.total);
        wrap.style.display = 'block';
        bar.style.width = pct+'%';
        bar.textContent = pct+'%';
      }
    });

    xhr.onloadstart = ()=> {
      wrap.style.display = 'block';
      bar.style.width = '0%';
      bar.textContent = '0%';
      out.textContent = '';
      btn.disabled = true;
    };

    xhr.onload = ()=> {
      btn.disabled = false;
      if (xhr.status === 200) {
        let res = null;
        try { res = JSON.parse(xhr.responseText); } catch {}
        if (res && res.ok) {
          bar.textContent = '✅ Completado';
          setTimeout(reloadSection, 700);
        } else {
          out.textContent = '❌ ' + (res && res.error ? res.error : 'Error al subir');
        }
      } else {
        out.textContent = '❌ Error ('+xhr.status+')';
      }
    };

    xhr.onerror = ()=> { btn.disabled = false; out.textContent = '⚠️ Error de red'; };
    xhr.send(fd);
  });
})();

// ------- acciones (borrar / toggle) -------
function deletePlugin(file) {
  if (!confirm('¿Eliminar "'+file+'"?')) return;
  fetch('pages/plugins_manage.php?action=delete&file='+encodeURIComponent(file), {method:'POST', credentials:'same-origin'})
    .then(r=>r.json()).then(j=>{
      if (j && j.ok) reloadSection();
      else alert('❌ '+ (j && j.error ? j.error : 'Error'));
    }).catch(()=>alert('⚠️ Error de red'));
}

function togglePlugin(file, mode) {
  fetch('pages/plugins_manage.php?action='+mode+'&file='+encodeURIComponent(file), {method:'POST', credentials:'same-origin'})
    .then(r=>r.json()).then(j=>{
      if (j && j.ok) reloadSection();
      else alert('❌ '+ (j && j.error ? j.error : 'Error'));
    }).catch(()=>alert('⚠️ Error de red'));
}
</script>
