<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
$csrf = $_SESSION['csrf_token'] ?? '';
?>

<style>
.worlds-wrap .panel-hero{background:linear-gradient(135deg,rgba(16,185,129,.14),rgba(59,130,246,.10),rgba(17,24,39,.72));border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:24px;box-shadow:0 18px 40px rgba(0,0,0,.28);margin-bottom:22px}
.worlds-wrap .panel-hero h2{margin:0;font-weight:800;color:#fff}.worlds-wrap .panel-hero p{margin:8px 0 0;color:#9ca3af;max-width:900px}
.worlds-wrap .stats-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:22px}
.worlds-wrap .stat-card,.worlds-wrap .module-card{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:18px;box-shadow:0 10px 24px rgba(0,0,0,.20)}
.worlds-wrap .stat-card{padding:17px}.worlds-wrap .stat-label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em;margin-bottom:7px}.worlds-wrap .stat-value{font-size:23px;font-weight:800;color:#fff}
.worlds-wrap .module-card{border-radius:22px;overflow:hidden;margin-bottom:22px}.worlds-wrap .section-header{padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}.worlds-wrap .section-header h3{margin:0;font-size:18px;font-weight:800}.worlds-wrap .section-header small{color:#9ca3af}
.worlds-wrap .table-dark{--bs-table-bg:transparent;--bs-table-striped-bg:rgba(255,255,255,.02);--bs-table-hover-bg:rgba(255,255,255,.04);--bs-table-color:#e5e7eb;--bs-table-border-color:rgba(255,255,255,.06);margin:0}
.worlds-wrap .table thead th{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;padding:13px;white-space:nowrap}.worlds-wrap .table td{padding:13px}
.worlds-wrap .world-name{font-weight:800;color:#fff}.worlds-wrap .muted{color:#9ca3af;font-size:12px}.worlds-wrap .btn-action{border-radius:10px;font-weight:700;padding:7px 10px;font-size:12px}
.worlds-wrap .notice{border:1px solid rgba(245,158,11,.25);background:rgba(245,158,11,.09);color:#fde68a;border-radius:16px;padding:14px 16px;margin-bottom:20px}
.worlds-wrap .empty{padding:36px;text-align:center;color:#9ca3af}.worlds-wrap .action-group{display:flex;justify-content:center;gap:6px;flex-wrap:wrap}
@media(max-width:1200px){.worlds-wrap .stats-grid{grid-template-columns:repeat(3,1fr)}}@media(max-width:767px){.worlds-wrap .stats-grid{grid-template-columns:1fr 1fr}}@media(max-width:480px){.worlds-wrap .stats-grid{grid-template-columns:1fr}}
</style>

<div class="container-fluid mt-4 worlds-wrap">
  <div class="panel-hero">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <h2>🌍 World Manager V2</h2>
        <p>Compatible con mundos legacy <code>.fwl/.db</code> y con el nuevo formato Valheim 1.0 basado en carpetas, chunks y marcadores de guardado. Los backups <code>*_backup_*</code> se detectan y agrupan automáticamente.</p>
      </div>
      <button class="btn btn-outline-light" onclick="reloadWorlds()">↻ Refrescar</button>
    </div>
  </div>

  <div class="notice">
    🛡️ <strong>Compatibilidad híbrida:</strong> el panel ya no asume que cada mundo son dos archivos. Un mundo 1.0 puede contener cientos de <code>.chunk</code> y archivos <code>_main.*</code>. El marcador <code>_main.N.ok</code> se muestra como señal de guardado completo detectado.
  </div>

  <div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Mundos</div><div class="stat-value" id="wm-worlds">—</div></div>
    <div class="stat-card"><div class="stat-label">Formato 1.0</div><div class="stat-value text-success" id="wm-v1">—</div></div>
    <div class="stat-card"><div class="stat-label">Legacy</div><div class="stat-value text-warning" id="wm-legacy">—</div></div>
    <div class="stat-card"><div class="stat-label">Backups detectados</div><div class="stat-value text-info" id="wm-backups">—</div></div>
    <div class="stat-card"><div class="stat-label">Espacio mundos + backups</div><div class="stat-value" id="wm-storage">—</div></div>
  </div>

  <div class="module-card">
    <div class="section-header">
      <div><h3>🗺️ Mundos disponibles</h3><small id="wm-root">Cargando WORLDS_DIR…</small></div>
      <span class="badge text-bg-secondary" id="wm-server-state">Comprobando servidor…</span>
    </div>
    <div class="table-responsive">
      <table class="table table-dark table-striped align-middle text-center">
        <thead><tr><th>Mundo</th><th>Formato</th><th>Estado</th><th>Tamaño</th><th>Contenido</th><th>Guardado</th><th>Modificado</th><th>Acciones</th></tr></thead>
        <tbody id="wm-world-list"><tr><td colspan="8" class="empty">Cargando mundos…</td></tr></tbody>
      </table>
    </div>
  </div>

  <div class="module-card">
    <div class="section-header">
      <div><h3>🕘 Backups nativos de Valheim</h3><small>Detecta automáticamente backups <code>_backup_</code> y <code>_backup_auto-</code>.</small></div>
    </div>
    <div class="table-responsive">
      <table class="table table-dark table-striped align-middle text-center">
        <thead><tr><th>Backup</th><th>Mundo</th><th>Tipo</th><th>Fecha</th><th>Tamaño</th><th>Archivos / chunks</th><th>Integridad</th><th>Acciones</th></tr></thead>
        <tbody id="wm-backup-list"><tr><td colspan="8" class="empty">Cargando backups…</td></tr></tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function(){
  var csrf = <?= json_encode($csrf, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;
  var model = null;

  function esc(s){
    return String(s == null ? '' : s).replace(/[&<>"']/g,function(c){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];
    });
  }
  function bytes(n){
    n=Number(n||0);
    if(n<1024)return n+' B';
    if(n<1048576)return (n/1024).toFixed(1)+' KB';
    if(n<1073741824)return (n/1048576).toFixed(2)+' MB';
    return (n/1073741824).toFixed(2)+' GB';
  }
  function post(action,payload){
    var fd=new FormData(); fd.append('csrf',csrf);
    Object.keys(payload||{}).forEach(function(k){fd.append(k,payload[k]);});
    return fetch('worlds_api.php?action='+encodeURIComponent(action),{method:'POST',body:fd,credentials:'same-origin'})
      .then(async function(r){var t=await r.text();var j;try{j=JSON.parse(t);}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida');}if(!r.ok||!j.ok)throw new Error(j.error||'Operación fallida');return j;});
  }
  function renderWorlds(){
    var tbody=document.getElementById('wm-world-list'), worlds=(model&&model.data&&model.data.worlds)||[];
    if(!worlds.length){tbody.innerHTML='<tr><td colspan="8" class="empty">📭 No se detectaron mundos.</td></tr>';return;}
    tbody.innerHTML=worlds.map(function(w){
      var v1=w.format==='directory_v1';
      var format=v1?'<span class="badge bg-success">Valheim 1.0</span>':'<span class="badge bg-warning text-dark">Legacy</span>';
      var state=w.active?'<span class="badge bg-primary">⭐ ACTIVO</span>':'<span class="badge bg-secondary">Disponible</span>';
      var content=v1?(w.file_count+' archivos · '+w.chunk_count+' chunks'):(w.file_count+' archivos legacy');
      var integrity=v1?(w.has_complete_marker?'<span class="text-success">✅ _main.ok</span>':'<span class="text-warning">⚠️ Sin .ok detectado</span>'):'<span class="text-muted">Legacy</span>';
      var activate=w.active?'':'<button class="btn btn-success btn-sm btn-action" onclick="wmActivate(\''+encodeURIComponent(w.name)+'\')">⭐ Activar</button>';
      var del=w.active?'':'<button class="btn btn-danger btn-sm btn-action" onclick="wmDeleteWorld(\''+encodeURIComponent(w.name)+'\')">🗑️ Eliminar</button>';
      return '<tr><td class="text-start"><div class="world-name">'+esc(w.name)+'</div><div class="muted">'+(v1?'Directorio de mundo':'Archivos .fwl/.db')+'</div></td>'+
        '<td>'+format+'</td><td>'+state+'</td><td>'+bytes(w.size_bytes)+'</td><td>'+content+'</td><td>'+integrity+'</td>'+
        '<td>'+esc(w.mtime||'—')+'</td><td><div class="action-group">'+activate+
        '<a class="btn btn-primary btn-sm btn-action" href="worlds_api.php?action=download&name='+encodeURIComponent(w.name)+'">⬇️ ZIP</a>'+del+'</div></td></tr>';
    }).join('');
  }
  function renderBackups(){
    var tbody=document.getElementById('wm-backup-list'), arr=(model&&model.data&&model.data.backups)||[];
    if(!arr.length){tbody.innerHTML='<tr><td colspan="8" class="empty">📭 No se detectaron backups de directorio.</td></tr>';return;}
    tbody.innerHTML=arr.map(function(b){
      return '<tr><td class="text-start"><span class="world-name">'+esc(b.name)+'</span></td><td>'+esc(b.world)+'</td>'+
      '<td>'+(b.backup_type==='auto'?'<span class="badge bg-info text-dark">AUTO</span>':'<span class="badge bg-secondary">MANUAL</span>')+'</td>'+
      '<td>'+esc(b.backup_at||b.mtime||'—')+'</td><td>'+bytes(b.size_bytes)+'</td><td>'+b.file_count+' / '+b.chunk_count+'</td>'+
      '<td>'+(b.has_complete_marker?'<span class="text-success">✅ Completo</span>':'<span class="text-warning">⚠️ Revisar</span>')+'</td>'+
      '<td><div class="action-group"><a class="btn btn-primary btn-sm btn-action" href="worlds_api.php?action=download&name='+encodeURIComponent(b.name)+'">⬇️ ZIP</a>'+
      '<button class="btn btn-danger btn-sm btn-action" onclick="wmDeleteBackup(\''+encodeURIComponent(b.name)+'\')">🗑️ Eliminar</button></div></td></tr>';
    }).join('');
  }

  window.reloadWorlds=function(){
    fetch('worlds_api.php?action=list',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){
      if(!j.ok)throw new Error(j.error||'Error');
      model=j; var s=j.data.stats||{};
      document.getElementById('wm-worlds').textContent=s.worlds||0;
      document.getElementById('wm-v1').textContent=s.new_format_worlds||0;
      document.getElementById('wm-legacy').textContent=s.legacy_worlds||0;
      document.getElementById('wm-backups').textContent=s.backups||0;
      document.getElementById('wm-storage').textContent=bytes(s.bytes||0);
      document.getElementById('wm-root').textContent=j.data.root||'';
      var st=document.getElementById('wm-server-state');
      st.className='badge '+(j.server_running?'bg-success':'bg-secondary');
      st.textContent=j.server_running?'🟢 Servidor ejecutándose':'⚫ Servidor detenido';
      renderWorlds();renderBackups();
    }).catch(function(e){
      document.getElementById('wm-world-list').innerHTML='<tr><td colspan="8" class="text-danger py-4">⚠️ '+esc(e.message)+'</td></tr>';
    });
  };
  window.wmActivate=function(encoded){
    var name=decodeURIComponent(encoded);
    if(!confirm('¿Usar "'+name+'" como mundo activo en el próximo arranque?'))return;
    post('set_active',{world:name}).then(function(j){alert('✅ '+j.msg);reloadWorlds();}).catch(function(e){alert('❌ '+e.message);});
  };
  window.wmDeleteBackup=function(encoded){
    var name=decodeURIComponent(encoded);
    if(!confirm('¿Eliminar definitivamente este backup?\n\n'+name))return;
    post('delete_backup',{name:name}).then(function(j){alert('✅ '+j.msg);reloadWorlds();}).catch(function(e){alert('❌ '+e.message);});
  };
  window.wmDeleteWorld=function(encoded){
    var name=decodeURIComponent(encoded);
    var typed=prompt('⚠️ Esta operación elimina el mundo completo.\n\nEscribe exactamente el nombre para confirmar:\n'+name);
    if(typed!==name)return;
    post('delete_world',{name:name,confirm:typed}).then(function(j){alert('✅ '+j.msg);reloadWorlds();}).catch(function(e){alert('❌ '+e.message);});
  };
  reloadWorlds();
})();
</script>
