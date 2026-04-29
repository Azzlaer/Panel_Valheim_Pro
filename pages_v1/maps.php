<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>

<style>
.maps-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(59,130,246,.12), rgba(17,24,39,.6));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.maps-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.maps-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}

.maps-wrap .hero-actions .btn {
    border-radius: 12px;
    font-weight: 600;
}

.maps-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.maps-wrap .stat-card {
    background: rgba(17,24,39,.92);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.maps-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.maps-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}

.maps-wrap .upload-card,
.maps-wrap .maps-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
    margin-bottom: 22px;
}

.maps-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.maps-wrap .section-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.maps-wrap .section-header small {
    color: #9ca3af;
}

.maps-wrap .upload-body {
    padding: 20px;
}

.maps-wrap .form-control,
.maps-wrap .form-control:focus {
    background: #0b1220;
    color: #e5e7eb;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: none;
    border-radius: 14px;
}

.maps-wrap .msg-box {
    display: none;
    border-radius: 14px;
    padding: 14px 16px;
    margin-top: 14px;
    border: 1px solid rgba(255,255,255,.08);
    font-weight: 600;
}

.maps-wrap .msg-info    { display:block; background: rgba(59,130,246,.12); border-color: rgba(59,130,246,.2); color: #bfdbfe; }
.maps-wrap .msg-success { display:block; background: rgba(16,185,129,.12); border-color: rgba(16,185,129,.2); color: #a7f3d0; }
.maps-wrap .msg-error   { display:block; background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.2); color: #fecaca; }

.maps-wrap .table-dark {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255,255,255,.02);
    --bs-table-hover-bg: rgba(255,255,255,.035);
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.06);
    margin-bottom: 0;
}

.maps-wrap .table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    font-weight: 700;
    border-bottom-width: 1px;
    padding-top: 14px;
    padding-bottom: 14px;
}

.maps-wrap .file-name {
    font-weight: 700;
    color: #fff;
}

.maps-wrap .btn-action {
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 12px;
}

.maps-wrap .maps-block {
    margin-bottom: 22px;
}

.maps-wrap .maps-block:last-child {
    margin-bottom: 0;
}

@media (max-width: 991px) {
    .maps-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .maps-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 maps-wrap">
    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>🗺️ Gestión de Mapas</h2>
                <p>Administra los archivos del directorio <code>worlds_local</code>, sube mapas manualmente y elimina archivos desde una consola centralizada.</p>
            </div>

            <div class="hero-actions d-flex gap-2">
                <button class="btn btn-outline-light" onclick="loadMaps()">↻ Refrescar</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Estado del módulo</div>
            <div class="stat-value text-info">Operativo</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Archivos FWL</div>
            <div class="stat-value" id="count-fwl">0</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Archivos DB</div>
            <div class="stat-value" id="count-db">0</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Archivos OLD</div>
            <div class="stat-value" id="count-old">0</div>
        </div>
    </div>

    <div class="upload-card">
        <div class="section-header">
            <h3>📤 Carga de archivos</h3>
            <small>Formatos permitidos: <code>.fwl</code>, <code>.db</code>, <code>.old</code></small>
        </div>

        <div class="upload-body">
            <form id="upload-form">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-9">
                        <label class="form-label text-light">Seleccionar archivo</label>
                        <input type="file" id="mapFile" name="mapFile" class="form-control" accept=".fwl,.db,.old" required>
                    </div>
                    <div class="col-lg-3 d-grid">
                        <button class="btn btn-success btn-lg">⬆️ Subir archivo</button>
                    </div>
                </div>
            </form>

            <div id="upload-msg" class="msg-box"></div>
        </div>
    </div>

    <div class="maps-card">
        <div class="section-header">
            <h3>📁 Archivos de mundos</h3>
            <small>Listado segmentado por extensión para facilitar mantenimiento y limpieza.</small>
        </div>

        <div class="p-3">

            <div class="maps-block">
                <h5 class="text-light mb-3">🧭 Archivos .FWL</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-striped text-center align-middle" id="table-fwl">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tamaño (MB)</th>
                                <th style="width:160px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="3">Cargando…</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="maps-block">
                <h5 class="text-light mb-3">🗄️ Archivos .DB</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-striped text-center align-middle" id="table-db">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tamaño (MB)</th>
                                <th style="width:160px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="3">Cargando…</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div class="maps-block">
                <h5 class="text-light mb-3">🕓 Archivos .OLD</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-striped text-center align-middle" id="table-old">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tamaño (MB)</th>
                                <th style="width:160px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="3">Cargando…</td></tr></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function setUploadMsg(text, type='info'){
  const box = document.getElementById('upload-msg');
  box.className = 'msg-box';
  if(type === 'success') box.classList.add('msg-success');
  else if(type === 'error') box.classList.add('msg-error');
  else box.classList.add('msg-info');
  box.textContent = text;
}

function updateMapStats(sets){
  document.getElementById('count-fwl').textContent = sets.fwl.length;
  document.getElementById('count-db').textContent  = sets.db.length;
  document.getElementById('count-old').textContent = sets.old.length;
}

function loadMaps(){
  fetch('api.php?action=list_maps', {credentials:'same-origin'})
    .then(r => r.json())
    .then(d => {
      if(!d.ok) throw new Error(d.error || 'Error');

      const sets = {fwl:[], db:[], old:[]};
      (d.items || []).forEach(f => {
        if (sets[f.ext]) sets[f.ext].push(f);
      });

      updateMapStats(sets);

      ['fwl','db','old'].forEach(ext => {
        const tb = document.querySelector('#table-' + ext + ' tbody');
        const arr = sets[ext];

        if(arr.length === 0){
          tb.innerHTML = '<tr><td colspan="3" class="py-4">📭 Sin archivos</td></tr>';
        } else {
          tb.innerHTML = arr.map(f => `
            <tr>
              <td><span class="file-name">${f.name}</span></td>
              <td>${Number(f.size_mb).toFixed(2)}</td>
              <td>
                <button class="btn btn-danger btn-sm btn-action" onclick="deleteMap('${encodeURIComponent(f.name)}')">🗑️ Eliminar</button>
              </td>
            </tr>
          `).join('');
        }
      });
    })
    .catch(e => {
      document.querySelectorAll('#table-fwl tbody, #table-db tbody, #table-old tbody')
        .forEach(tb => tb.innerHTML = `<tr><td colspan="3" class="text-danger py-4">⚠️ ${e.message || e}</td></tr>`);
      setUploadMsg('No se pudo cargar la lista de mapas.', 'error');
    });
}

function deleteMap(name){
  if(!confirm('¿Eliminar el archivo seleccionado?\n\n' + decodeURIComponent(name))) return;

  fetch('api.php?action=delete_map&file=' + name, {credentials:'same-origin'})
    .then(r => r.json())
    .then(d => {
      if(d.ok){
        setUploadMsg('🗑️ Archivo eliminado correctamente.', 'success');
        loadMaps();
      } else {
        setUploadMsg('❌ ' + (d.error || 'Error desconocido'), 'error');
      }
    });
}

document.getElementById('upload-form').addEventListener('submit', e => {
  e.preventDefault();

  const fileInput = document.getElementById('mapFile');
  if(!fileInput.files.length){
    setUploadMsg('Selecciona un archivo antes de continuar.', 'error');
    return;
  }

  const fd = new FormData();
  fd.append('mapFile', fileInput.files[0]);

  setUploadMsg('⏳ Subiendo archivo al servidor...', 'info');

  fetch('api.php?action=upload_map', {
    method:'POST',
    body: fd,
    credentials:'same-origin'
  })
    .then(r => r.json())
    .then(d => {
      if(d.ok){
        setUploadMsg('✅ Archivo subido correctamente.', 'success');
        fileInput.value = '';
        loadMaps();
      } else {
        setUploadMsg('❌ ' + (d.error || 'Error desconocido'), 'error');
      }
    })
    .catch(e => {
      setUploadMsg('⚠️ Error al subir archivo: ' + (e.message || e), 'error');
    });
});

loadMaps();
</script>