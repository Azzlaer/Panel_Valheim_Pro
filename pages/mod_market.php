<?php
require_once __DIR__.'/../config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);exit('Acceso denegado');}
$csrf=$_SESSION['csrf_token']??'';
?>
<div class="container-fluid mt-4">
 <div class="p-4 rounded-4 border bg-dark mb-4">
  <div class="d-flex justify-content-between gap-3 flex-wrap">
   <div><h2>🛒 Thunderstore Mod Marketplace</h2><p class="text-secondary mb-0">Busca, instala, actualiza y desinstala paquetes de Valheim con resolución automática de dependencias.</p></div>
   <button class="btn btn-outline-light" onclick="tmRefreshIndex()">↻ Actualizar catálogo</button>
  </div>
 </div>
 <div id="tmMsg" class="alert alert-info">Listo.</div>

 <div class="card bg-dark border-secondary mb-4"><div class="card-body">
  <div class="input-group"><input id="tmQ" class="form-control" placeholder="Buscar ValheimRcon, Jotunn, ServerInfo..."><button class="btn btn-primary" onclick="tmSearch()">🔎 Buscar</button></div>
 </div></div>

 <div class="card bg-dark border-secondary mb-4">
  <div class="card-header"><strong>Resultados</strong></div>
  <div class="table-responsive"><table class="table table-dark table-striped align-middle mb-0"><thead><tr><th>Paquete</th><th>Descripción</th><th>Versión</th><th>Dependencias</th><th>Estado</th><th>Acción</th></tr></thead><tbody id="tmResults"><tr><td colspan="6" class="text-secondary">Busca un mod.</td></tr></tbody></table></div>
 </div>

 <div class="card bg-dark border-secondary">
  <div class="card-header"><strong>Mods administrados por el panel</strong></div>
  <div class="table-responsive"><table class="table table-dark table-striped align-middle mb-0"><thead><tr><th>Paquete</th><th>Instalado</th><th>Disponible</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="tmInstalled"><tr><td colspan="5">Cargando…</td></tr></tbody></table></div>
 </div>
</div>
<script>
(function(){
 var csrf=<?=json_encode($csrf)?>;
 function msg(t,c){var e=document.getElementById('tmMsg');e.className='alert alert-'+(c||'info');e.textContent=t;}
 function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
 function post(a,p){var f=new FormData();f.append('csrf',csrf);Object.keys(p||{}).forEach(k=>f.append(k,p[k]));return fetch('mods_api.php?action='+a,{method:'POST',body:f,credentials:'same-origin'}).then(async r=>{var t=await r.text(),j;try{j=JSON.parse(t)}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida')}if(!r.ok||!j.ok)throw new Error(j.error||'Error');return j;});}
 window.tmSearch=function(){var q=document.getElementById('tmQ').value.trim();if(!q)return;msg('Buscando en Thunderstore…','info');fetch('mods_api.php?action=search&q='+encodeURIComponent(q),{credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);var tb=document.getElementById('tmResults');tb.innerHTML=(j.items||[]).length?j.items.map(x=>'<tr><td><b>'+esc(x.namespace)+'</b>/<br>'+esc(x.name)+'</td><td>'+esc(x.description).slice(0,180)+'</td><td>'+esc(x.version)+'</td><td>'+(x.dependencies||[]).length+'</td><td>'+(x.installed_version?'<span class="badge bg-success">v'+esc(x.installed_version)+'</span>':'<span class="badge bg-secondary">No instalado</span>')+'</td><td><button class="btn btn-sm btn-primary" onclick="tmInstall(\''+esc(x.namespace)+'\',\''+esc(x.name)+'\',\''+esc(x.version)+'\')">'+(x.installed_version?'⬆️ Actualizar':'📥 Instalar')+'</button></td></tr>').join(''):'<tr><td colspan="6">Sin resultados</td></tr>';msg('✅ '+(j.items||[]).length+' resultados','success');}).catch(e=>msg('❌ '+e.message,'danger'));};
 window.tmInstall=function(ns,name,ver){if(!confirm('¿Instalar '+ns+'/'+name+' '+ver+' y sus dependencias?'))return;msg('Instalando '+ns+'/'+name+'…','info');post('install',{namespace:ns,name:name,version:ver}).then(()=>{msg('✅ Instalación completada','success');tmLoadInstalled();}).catch(e=>msg('❌ '+e.message,'danger'));};
 window.tmUninstall=function(ns,name){if(!confirm('¿Desinstalar '+ns+'/'+name+'? Las dependencias compartidas no se eliminan automáticamente.'))return;post('uninstall',{namespace:ns,name:name}).then(()=>{msg('✅ Desinstalado','success');tmLoadInstalled();}).catch(e=>msg('❌ '+e.message,'danger'));};
 window.tmLoadInstalled=function(){fetch('mods_api.php?action=installed',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);var vals=Object.values(j.items||{}),tb=document.getElementById('tmInstalled');tb.innerHTML=vals.length?vals.map(x=>'<tr><td>'+esc(x.namespace)+'/'+esc(x.name)+'</td><td>'+esc(x.version)+'</td><td>'+esc(x.latest_version||'—')+'</td><td>'+(x.status==='outdated'?'<span class="badge bg-warning text-dark">Update</span>':'<span class="badge bg-success">OK</span>')+'</td><td><button class="btn btn-sm btn-primary me-1" onclick="tmInstall(\''+esc(x.namespace)+'\',\''+esc(x.name)+'\',\''+(x.latest_version||x.version)+'\')">🔄</button><button class="btn btn-sm btn-danger" onclick="tmUninstall(\''+esc(x.namespace)+'\',\''+esc(x.name)+'\')">🗑️</button></td></tr>').join(''):'<tr><td colspan="5">No hay mods administrados por el panel.</td></tr>';if(j.server_running)msg('🛑 Detén Valheim antes de instalar/desinstalar mods.','warning');}).catch(e=>msg('❌ '+e.message,'danger'));};
 window.tmRefreshIndex=function(){msg('Actualizando catálogo Thunderstore…','info');post('refresh_index',{}).then(j=>{msg('✅ Catálogo actualizado: '+j.packages+' paquetes','success');}).catch(e=>msg('❌ '+e.message,'danger'));};
 document.getElementById('tmQ').addEventListener('keydown',e=>{if(e.key==='Enter')tmSearch();});
 tmLoadInstalled();
})();
</script>
