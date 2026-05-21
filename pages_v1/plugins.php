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

<style>
.plugins-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(245,158,11,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}
.plugins-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}
.plugins-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}
.plugins-wrap .hero-actions .btn {
    border-radius: 12px;
    font-weight: 600;
}
.plugins-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}
.plugins-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}
.plugins-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}
.plugins-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}
.plugins-wrap .module-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
    margin-bottom: 22px;
}
.plugins-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.plugins-wrap .section-header h3,
.plugins-wrap .section-header h5 {
    margin: 0;
    color: #fff;
    font-weight: 700;
}
.plugins-wrap .section-header small {
    color: #9ca3af;
}
.plugins-wrap .section-body {
    padding: 20px;
}
.plugins-wrap .form-control,
.plugins-wrap .form-control:focus {
    background: #0b1220;
    color: #e5e7eb;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: none;
    border-radius: 14px;
}
.plugins-wrap .table-dark {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255,255,255,.02);
    --bs-table-hover-bg: rgba(255,255,255,.035);
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.06);
    margin-bottom: 0;
}
.plugins-wrap .table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    font-weight: 700;
    padding-top: 14px;
    padding-bottom: 14px;
}
.plugins-wrap .plugin-name {
    font-weight: 700;
    color: #fff;
}
.plugins-wrap .btn-action {
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 12px;
}
.plugins-wrap #uploadResult {
    min-height: 24px;
    font-weight: 600;
}
.plugins-wrap .progress {
    background: rgba(255,255,255,.06);
    border-radius: 999px;
    overflow: hidden;
}
@media (max-width: 991px) {
    .plugins-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .plugins-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 plugins-wrap">

    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>📊 Gestión de Plugins</h2>
                <p>Administra los archivos ubicados en <code><?= htmlspecialchars(PLUGINS_DIR) ?></code>, sube nuevos plugins y controla su estado operativo.</p>
            </div>
            <div class="hero-actions d-flex gap-2">
                <button class="btn btn-outline-light" onclick="reloadSection()">↻ Refrescar</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Plugins habilitados</div>
            <div class="stat-value"><?= count($pluginsEnabled) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Plugins deshabilitados</div>
            <div class="stat-value"><?= count($pluginsDisabled) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Archivos DB</div>
            <div class="stat-value"><?= count($pluginDBs) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Ruta activa</div>
            <div class="stat-value" style="font-size:13px; line-height:1.5; font-weight:600;">
                <?= htmlspecialchars(PLUGINS_DIR) ?>
            </div>
        </div>
    </div>

    <!-- Subida -->
    <div class="module-card">
        <div class="section-header">
            <h3>➕ Subir archivo</h3>
            <small>Se permiten archivos <code>.dll</code> y <code>.db</code>.</small>
        </div>
        <div class="section-body">
            <form id="pluginUploadForm" enctype="multipart/form-data" onsubmit="return false;">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label text-light">Seleccionar archivo</label>
                        <input type="file" name="plugin" id="pluginFile" class="form-control" accept=".dll,.db" required>
                        <div class="form-text text-light">Sube plugins activos o archivos de soporte para el directorio de mods.</div>
                    </div>
                    <div class="col-md-4 d-grid">
                        <button class="btn btn-primary btn-lg" type="button" id="btnUploadPlugin">⬆️ Subir</button>
                    </div>
                </div>
            </form>

            <div class="progress mt-3" id="uploadProgressWrapper" style="height:22px; display:none;">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     id="uploadProgressBar" style="width:0%">0%</div>
            </div>

            <div id="uploadResult" class="mt-3 small text-info"></div>
        </div>
    </div>

    <!-- DLL habilitados -->
    <div class="module-card">
        <div class="section-header">
            <h5>✅ Plugins habilitados (.dll)</h5>
        </div>
        <div class="table-responsive">
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
                    <tr><td colspan="3" class="py-4">⚠️ No hay plugins habilitados.</td></tr>
                <?php else: foreach ($pluginsEnabled as $file): ?>
                    <tr>
                        <td class="text-start"><span class="plugin-name"><?= htmlspecialchars(basename($file)) ?></span></td>
                        <td><?= number_format(filesize($file)/1024, 2) ?></td>
                        <td>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <button class="btn btn-warning btn-sm btn-action"
                                        onclick="togglePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>','disable')">
                                    Deshabilitar
                                </button>
                                <button class="btn btn-danger btn-sm btn-action"
                                        onclick="deletePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>')">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DLL deshabilitados -->
    <div class="module-card">
        <div class="section-header">
            <h5>🚫 Plugins deshabilitados (.disable)</h5>
        </div>
        <div class="table-responsive">
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
                    <tr><td colspan="3" class="py-4">✅ No hay plugins deshabilitados.</td></tr>
                <?php else: foreach ($pluginsDisabled as $file): ?>
                    <tr>
                        <td class="text-start"><span class="plugin-name"><?= htmlspecialchars(basename($file)) ?></span></td>
                        <td><?= number_format(filesize($file)/1024, 2) ?></td>
                        <td>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <button class="btn btn-success btn-sm btn-action"
                                        onclick="togglePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>','enable')">
                                    Habilitar
                                </button>
                                <button class="btn btn-danger btn-sm btn-action"
                                        onclick="deletePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>')">
                                    Eliminar
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DB -->
    <div class="module-card">
        <div class="section-header">
            <h5>🗄️ Archivos DB (.db)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle text-center">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Tamaño (KB)</th>
                        <th style="width:220px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($pluginDBs)): ?>
                    <tr><td colspan="3" class="py-4">📭 No hay archivos DB.</td></tr>
                <?php else: foreach ($pluginDBs as $file): ?>
                    <tr>
                        <td class="text-start"><span class="plugin-name"><?= htmlspecialchars(basename($file)) ?></span></td>
                        <td><?= number_format(filesize($file)/1024, 2) ?></td>
                        <td>
                            <button class="btn btn-danger btn-sm btn-action"
                                    onclick="deletePlugin('<?= htmlspecialchars(basename($file), ENT_QUOTES) ?>')">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function reloadSection() {
  if (window.jQuery) {
    $('#main').load('pages/plugins.php');
  } else {
    fetch('pages/plugins.php',{credentials:'same-origin'})
      .then(r=>r.text())
      .then(html=>{ document.getElementById('main').innerHTML = html; });
  }
}

(function(){
  const fileIn = document.getElementById('pluginFile');
  const btn    = document.getElementById('btnUploadPlugin');
  const wrap   = document.getElementById('uploadProgressWrapper');
  const bar    = document.getElementById('uploadProgressBar');
  const out    = document.getElementById('uploadResult');

  btn.addEventListener('click', function(){
    if (!fileIn.files.length) {
      out.className = 'mt-3 small text-danger';
      out.textContent = '❌ Selecciona un archivo .dll o .db';
      return;
    }

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
      out.className = 'mt-3 small text-info';
      out.textContent = '⏳ Subiendo archivo...';
      btn.disabled = true;
    };

    xhr.onload = ()=> {
      btn.disabled = false;
      if (xhr.status === 200) {
        let res = null;
        try { res = JSON.parse(xhr.responseText); } catch(e) {}
        if (res && res.ok) {
          bar.textContent = '✅ Completado';
          out.className = 'mt-3 small text-success';
          out.textContent = '✅ Archivo subido correctamente';
          setTimeout(reloadSection, 700);
        } else {
          out.className = 'mt-3 small text-danger';
          out.textContent = '❌ ' + (res && res.error ? res.error : 'Error al subir');
        }
      } else {
        out.className = 'mt-3 small text-danger';
        out.textContent = '❌ Error ('+xhr.status+')';
      }
    };

    xhr.onerror = ()=> {
      btn.disabled = false;
      out.className = 'mt-3 small text-danger';
      out.textContent = '⚠️ Error de red';
    };

    xhr.send(fd);
  });
})();

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