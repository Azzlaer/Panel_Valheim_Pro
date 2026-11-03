<?php
require_once __DIR__.'/../config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);exit('Acceso denegado');}
$csrf=$_SESSION['csrf_token']??'';
?>
<div class="container-fluid mt-4">
  <div class="p-4 rounded-4 border bg-dark mb-4">
    <div class="d-flex justify-content-between gap-3 flex-wrap">
      <div><h2 class="mb-1">💾 Backup & Restore Center V3</h2><p class="text-secondary mb-0">Backups por mundo, restore seguro y snapshot automático antes de restaurar.</p></div>
      <button class="btn btn-outline-light" onclick="brLoad()">↻ Refrescar</button>
    </div>
  </div>
  <div id="brMsg" class="alert alert-info">Cargando…</div>
  <div class="card bg-dark border-secondary mb-4">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-8"><label class="form-label">Mundo</label><select id="brWorld" class="form-select"></select></div>
        <div class="col-md-4 d-grid"><button id="brCreate" class="btn btn-success" onclick="brCreate()">💾 Crear backup seguro</button></div>
      </div>
    </div>
  </div>
  <div class="card bg-dark border-secondary">
    <div class="card-header"><strong>Respaldos del panel</strong></div>
    <div class="table-responsive"><table class="table table-dark table-striped align-middle mb-0"><thead><tr><th>Archivo</th><th>Mundo</th><th>Formato</th><th>Fecha</th><th>Tamaño</th><th>Acciones</th></tr></thead><tbody id="brRows"><tr><td colspan="6">Cargando…</td></tr></tbody></table></div>
  </div>
</div>
<script>
(function(){
 var csrf=<?=json_encode($csrf)?>;
 function msg(t,c){var e=document.getElementById('brMsg');e.className='alert alert-'+c;e.textContent=t;}
 function fmt(n){n=Number(n||0);return n>1073741824?(n/1073741824).toFixed(2)+' GB':(n/1048576).toFixed(2)+' MB';}
 function post(a,p){var f=new FormData();f.append('csrf',csrf);Object.keys(p||{}).forEach(k=>f.append(k,p[k]));return fetch('backup_v3_api.php?action='+a,{method:'POST',body:f,credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error||'Error');return j;});}
 window.brLoad=function(){fetch('backup_v3_api.php?action=list',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);var s=document.getElementById('brWorld');s.innerHTML=(j.worlds||[]).map(w=>'<option value="'+encodeURIComponent(w.name)+'">'+w.name+(w.active?' ⭐':'')+'</option>').join('');var tb=document.getElementById('brRows');tb.innerHTML=(j.items||[]).length?(j.items||[]).map(x=>{var m=x.manifest||{};return '<tr><td>'+x.name+'</td><td>'+(m.world||'—')+'</td><td>'+(m.format||'—')+'</td><td>'+x.mtime+'</td><td>'+fmt(x.size_bytes)+'</td><td><a class="btn btn-sm btn-primary me-1" href="backup_v3_api.php?action=download&name='+encodeURIComponent(x.name)+'">⬇️</a><button class="btn btn-sm btn-warning me-1" onclick="brRestore(\''+encodeURIComponent(x.name)+'\')">♻️ Restaurar</button><button class="btn btn-sm btn-danger" onclick="brDelete(\''+encodeURIComponent(x.name)+'\')">🗑️</button></td></tr>';}).join(''):'<tr><td colspan="6">Sin backups</td></tr>';document.getElementById('brCreate').disabled=!!j.server_running;msg(j.server_running?'🛑 Detén el servidor para crear/restaurar backups consistentes.':'✅ Listo para operar.','info');}).catch(e=>msg(e.message,'danger'));};
 window.brCreate=function(){var w=decodeURIComponent(document.getElementById('brWorld').value||'');post('create',{world:w}).then(j=>{msg('✅ '+j.name,'success');brLoad();}).catch(e=>msg(e.message,'danger'));};
 window.brRestore=function(n){n=decodeURIComponent(n);if(!confirm('¿Restaurar '+n+'? Se creará un backup preventivo del mundo actual.'))return;post('restore',{name:n}).then(j=>{msg('✅ Restaurado. Snapshot: '+j.safety_backup,'success');brLoad();}).catch(e=>msg(e.message,'danger'));};
 window.brDelete=function(n){n=decodeURIComponent(n);if(!confirm('¿Eliminar '+n+'?'))return;post('delete',{name:n}).then(()=>brLoad()).catch(e=>msg(e.message,'danger'));};
 brLoad();
})();
</script>
