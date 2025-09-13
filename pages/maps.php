<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>
<div class="container mt-4">
  <h2>🗺️ Gestión de Mapas (worlds_local)</h2>

  <form id="upload-form" class="mb-3">
    <input type="file" id="mapFile" name="mapFile" class="form-control mb-2" accept=".fwl,.db,.old" required>
    <button class="btn btn-success">Subir archivo</button>
    <div id="upload-msg" class="text-info mt-2"></div>
  </form>

  <div id="map-lists">
    <h4>Archivos .FWL</h4>
    <table class="table table-dark table-striped text-center align-middle" id="table-fwl">
      <thead><tr><th>Nombre</th><th>Tamaño (MB)</th><th>Eliminar</th></tr></thead>
      <tbody><tr><td colspan="3">Cargando…</td></tr></tbody>
    </table>

    <h4 class="mt-4">Archivos .DB</h4>
    <table class="table table-dark table-striped text-center align-middle" id="table-db">
      <thead><tr><th>Nombre</th><th>Tamaño (MB)</th><th>Eliminar</th></tr></thead>
      <tbody><tr><td colspan="3">Cargando…</td></tr></tbody>
    </table>

    <h4 class="mt-4">Archivos .OLD</h4>
    <table class="table table-dark table-striped text-center align-middle" id="table-old">
      <thead><tr><th>Nombre</th><th>Tamaño (MB)</th><th>Eliminar</th></tr></thead>
      <tbody><tr><td colspan="3">Cargando…</td></tr></tbody>
    </table>
  </div>
</div>

<script>
function loadMaps(){
  fetch('api.php?action=list_maps',{credentials:'same-origin'})
    .then(r=>r.json())
    .then(d=>{
      if(!d.ok) throw new Error(d.error||'Error');
      const sets = {fwl:[], db:[], old:[]};
      d.items.forEach(f => { sets[f.ext].push(f); });
      ['fwl','db','old'].forEach(ext=>{
        const tb = document.querySelector('#table-'+ext+' tbody');
        const arr = sets[ext];
        if(arr.length===0){
          tb.innerHTML='<tr><td colspan="3">📭 Sin archivos</td></tr>';
        } else {
          tb.innerHTML = arr.map(f=>`
            <tr>
              <td>${f.name}</td>
              <td>${f.size_mb.toFixed(2)}</td>
              <td><button class="btn btn-danger btn-sm" onclick="deleteMap('${encodeURIComponent(f.name)}')">🗑️ Eliminar</button></td>
            </tr>`).join('');
        }
      });
    })
    .catch(e=>{
      document.querySelectorAll('#map-lists tbody').forEach(tb=>tb.innerHTML='<tr><td colspan="3" class="text-danger">⚠️ '+e+'</td></tr>');
    });
}

function deleteMap(name){
  if(!confirm('¿Eliminar '+name+'?')) return;
  fetch('api.php?action=delete_map&file='+name,{credentials:'same-origin'})
    .then(r=>r.json())
    .then(d=>{
      if(d.ok) loadMaps();
      else alert('❌ '+(d.error||'Error'));
    });
}

document.getElementById('upload-form').addEventListener('submit',e=>{
  e.preventDefault();
  const fileInput = document.getElementById('mapFile');
  const msg = document.getElementById('upload-msg');
  if(!fileInput.files.length){ alert('Seleccione un archivo'); return; }
  const fd = new FormData();
  fd.append('mapFile', fileInput.files[0]);
  msg.textContent='⏳ Subiendo…';
  fetch('api.php?action=upload_map',{method:'POST',body:fd,credentials:'same-origin'})
    .then(r=>r.json())
    .then(d=>{
      if(d.ok){ msg.textContent='✅ Subido con éxito'; fileInput.value=''; loadMaps(); }
      else { msg.textContent='❌ '+(d.error||'Error'); }
    })
    .catch(e=>{ msg.textContent='⚠️ '+e; });
});

loadMaps();
</script>
