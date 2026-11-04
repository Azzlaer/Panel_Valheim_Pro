<?php
require_once __DIR__.'/../config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);exit('Acceso denegado');}
$csrf=$_SESSION['csrf_token']??'';
?>
<div class="container-fluid mt-4">
 <div class="p-4 rounded-4 border bg-dark mb-4">
  <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start">
   <div>
    <h2>🛒 Thunderstore Mod Marketplace</h2>
    <p class="text-secondary mb-0">Busca por nombre, instala directamente y revisa rankings ligeros sin descargar el catálogo completo.</p>
   </div>
   <div class="d-flex gap-2 flex-wrap">
    <button class="btn btn-outline-light" onclick="tmDiscover('most-downloaded')">🔥 Top descargados</button>
    <button class="btn btn-outline-light" onclick="tmDiscover('top-rated')">⭐ Mejor valorados</button>
    <button class="btn btn-outline-light" onclick="tmDiscover('last-updated')">🆕 Recientes</button>
   </div>
  </div>
 </div>

 <div id="tmMsg" class="alert alert-info">Escribe el nombre de un mod o usa uno de los rankings opcionales.</div>

 <div class="card bg-dark border-secondary mb-4"><div class="card-body">
  <div class="row g-2">
   <div class="col-lg-9">
    <div class="input-group">
     <input id="tmQ" class="form-control" placeholder="Ej: ValheimRcon, Jotunn, ServerInfo...">
     <button class="btn btn-primary" onclick="tmSearch()">🔎 Buscar</button>
    </div>
   </div>
   <div class="col-lg-3">
    <select id="tmOrdering" class="form-select">
     <option value="last-updated">Más recientes</option>
     <option value="most-downloaded">Más descargados</option>
     <option value="top-rated">Mejor valorados</option>
    </select>
   </div>
  </div>
  <div class="small text-secondary mt-2">La búsqueda consulta una sola página de Thunderstore y devuelve hasta 15 resultados. No carga los miles de paquetes del catálogo.</div>
 </div></div>

 <div class="card bg-dark border-secondary mb-4">
  <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
   <strong id="tmResultsTitle">Resultados</strong>
   <span class="text-secondary small" id="tmResultsMeta"></span>
  </div>
  <div class="table-responsive">
   <table class="table table-dark table-striped align-middle mb-0">
    <thead><tr><th>Paquete</th><th>Descripción</th><th>Versión</th><th>Descargas</th><th>Dependencias</th><th>Estado</th><th>Acción</th></tr></thead>
    <tbody id="tmResults"><tr><td colspan="7" class="text-secondary">Busca un mod.</td></tr></tbody>
   </table>
  </div>
 </div>

 <div class="card bg-dark border-secondary">
  <div class="card-header"><strong>Mods administrados por el panel</strong></div>
  <div class="table-responsive">
   <table class="table table-dark table-striped align-middle mb-0">
    <thead><tr><th>Paquete</th><th>Instalado</th><th>Disponible</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody id="tmInstalled"><tr><td colspan="5">Cargando…</td></tr></tbody>
   </table>
  </div>
 </div>
</div>

<script>
(function(){
 var csrf=<?=json_encode($csrf)?>;

 function msg(t,c){
   var e=document.getElementById('tmMsg');
   e.className='alert alert-'+(c||'info');
   e.textContent=t;
 }

 function esc(s){
   return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 }

 function fmtDownloads(n){
   n=Number(n||0);
   if(n>=1000000)return (n/1000000).toFixed(1)+'M';
   if(n>=1000)return (n/1000).toFixed(1)+'K';
   return String(n);
 }

 function post(a,p){
   var f=new FormData();f.append('csrf',csrf);
   Object.keys(p||{}).forEach(k=>f.append(k,p[k]));
   return fetch('mods_api.php?action='+a,{method:'POST',body:f,credentials:'same-origin'})
    .then(async r=>{
      var t=await r.text(),j;
      try{j=JSON.parse(t)}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida')}
      if(!r.ok||!j.ok)throw new Error(j.error||'Error');
      return j;
    });
 }

 function renderResults(items,title){
   var tb=document.getElementById('tmResults');
   document.getElementById('tmResultsTitle').textContent=title||'Resultados';
   document.getElementById('tmResultsMeta').textContent=(items||[]).length+' resultados';

   if(!items||!items.length){
     tb.innerHTML='<tr><td colspan="7" class="text-secondary">Sin resultados.</td></tr>';
     return;
   }

   tb.innerHTML=items.map(x=>{
     var state=x.installed_version
       ? '<span class="badge bg-success">v'+esc(x.installed_version)+'</span>'
       : '<span class="badge bg-secondary">No instalado</span>';

     return '<tr>'+
      '<td><b>'+esc(x.namespace)+'</b><br>'+esc(x.name)+'</td>'+
      '<td>'+esc(x.description).slice(0,180)+'</td>'+
      '<td>'+esc(x.version)+'</td>'+
      '<td>'+fmtDownloads(x.downloads)+'</td>'+
      '<td>'+((x.dependencies||[]).length)+'</td>'+
      '<td>'+state+'</td>'+
      '<td class="text-nowrap">'+
        '<button class="btn btn-sm btn-primary me-1" onclick="tmInstall(\''+esc(x.namespace)+'\',\''+esc(x.name)+'\',\''+esc(x.version)+'\')">'+(x.installed_version?'⬆️ Actualizar':'📥 Instalar')+'</button>'+
        (x.page_url?'<a class="btn btn-sm btn-outline-light" target="_blank" rel="noopener" href="'+esc(x.page_url)+'">🌐</a>':'')+
      '</td>'+
     '</tr>';
   }).join('');
 }

 window.tmSearch=function(){
   var q=document.getElementById('tmQ').value.trim();
   var ordering=document.getElementById('tmOrdering').value;
   if(!q){msg('Escribe el nombre de un mod.','warning');return;}

   msg('Buscando "'+q+'" en Thunderstore…','info');
   document.getElementById('tmResults').innerHTML='<tr><td colspan="7">Buscando…</td></tr>';

   fetch('mods_api.php?action=search&q='+encodeURIComponent(q)+'&ordering='+encodeURIComponent(ordering),{credentials:'same-origin'})
    .then(async r=>{
      var t=await r.text(),j;
      try{j=JSON.parse(t)}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida')}
      if(!r.ok||!j.ok)throw new Error(j.error||'Error');
      return j;
    })
    .then(j=>{
      renderResults(j.items||[],'Resultados para: '+q);
      msg('✅ Búsqueda completada.','success');
    })
    .catch(e=>msg('❌ '+e.message,'danger'));
 };

 window.tmDiscover=function(ordering){
   var labels={
     'most-downloaded':'🔥 Top 10 más descargados',
     'top-rated':'⭐ Top 10 mejor valorados',
     'last-updated':'🆕 10 actualizados recientemente'
   };
   msg('Consultando '+labels[ordering]+'…','info');
   document.getElementById('tmResults').innerHTML='<tr><td colspan="7">Cargando…</td></tr>';

   fetch('mods_api.php?action=discover&ordering='+encodeURIComponent(ordering),{credentials:'same-origin'})
    .then(async r=>{
      var t=await r.text(),j;
      try{j=JSON.parse(t)}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida')}
      if(!r.ok||!j.ok)throw new Error(j.error||'Error');
      return j;
    })
    .then(j=>{
      renderResults(j.items||[],labels[ordering]);
      msg('✅ Lista cargada sin descargar el catálogo completo.','success');
    })
    .catch(e=>msg('❌ '+e.message,'danger'));
 };

 window.tmInstall=function(ns,name,ver){
   if(!confirm('¿Instalar '+ns+'/'+name+' '+ver+' y sus dependencias?'))return;
   msg('Instalando '+ns+'/'+name+'…','info');
   post('install',{namespace:ns,name:name,version:ver})
    .then(()=>{
      msg('✅ Instalación completada','success');
      tmLoadInstalled();
    })
    .catch(e=>msg('❌ '+e.message,'danger'));
 };

 window.tmUninstall=function(ns,name){
   if(!confirm('¿Desinstalar '+ns+'/'+name+'? Las dependencias compartidas no se eliminan automáticamente.'))return;
   post('uninstall',{namespace:ns,name:name})
    .then(()=>{
      msg('✅ Desinstalado','success');
      tmLoadInstalled();
    })
    .catch(e=>msg('❌ '+e.message,'danger'));
 };

 window.tmLoadInstalled=function(){
   fetch('mods_api.php?action=installed',{credentials:'same-origin'})
    .then(async r=>{
      var t=await r.text(),j;
      try{j=JSON.parse(t)}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida')}
      if(!r.ok||!j.ok)throw new Error(j.error||'Error');
      return j;
    })
    .then(j=>{
      var vals=Object.values(j.items||{}),tb=document.getElementById('tmInstalled');
      tb.innerHTML=vals.length
       ? vals.map(x=>'<tr><td>'+esc(x.namespace)+'/'+esc(x.name)+'</td><td>'+esc(x.version)+'</td><td>'+esc(x.latest_version||'—')+'</td><td>'+(x.status==='outdated'?'<span class="badge bg-warning text-dark">Update</span>':'<span class="badge bg-success">OK</span>')+'</td><td><button class="btn btn-sm btn-primary me-1" onclick="tmInstall(\''+esc(x.namespace)+'\',\''+esc(x.name)+'\',\''+(x.latest_version||x.version)+'\')">🔄</button><button class="btn btn-sm btn-danger" onclick="tmUninstall(\''+esc(x.namespace)+'\',\''+esc(x.name)+'\')">🗑️</button></td></tr>').join('')
       : '<tr><td colspan="5">No hay mods administrados por el panel.</td></tr>';

      if(j.server_running)msg('🛑 Detén Valheim antes de instalar/desinstalar mods.','warning');
    })
    .catch(e=>msg('❌ '+e.message,'danger'));
 };

 document.getElementById('tmQ').addEventListener('keydown',e=>{if(e.key==='Enter')tmSearch();});
 tmLoadInstalled();
})();
</script>
