<?php
require_once __DIR__.'/../config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);exit('Acceso denegado');}
?>
<style>
.auto-wrap .hero,.auto-wrap .cardx{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:22px;box-shadow:0 18px 40px rgba(0,0,0,.24)}
.auto-wrap .hero{padding:24px;margin-bottom:22px;background:linear-gradient(135deg,rgba(139,92,246,.16),rgba(17,24,39,.75))}.auto-wrap h2,.auto-wrap h3{color:#fff;font-weight:800}.auto-wrap .muted{color:#9ca3af}.auto-wrap .cardx{padding:20px;margin-bottom:22px}.auto-wrap .form-control,.auto-wrap .form-select{background:#0b1220;color:#e5e7eb;border-color:rgba(255,255,255,.08);border-radius:12px}.auto-wrap .table{--bs-table-bg:transparent;--bs-table-color:#e5e7eb;--bs-table-border-color:rgba(255,255,255,.07)}.auto-wrap .btn{border-radius:11px;font-weight:700}
</style>
<div class="container-fluid mt-4 auto-wrap">
 <div class="hero d-flex justify-content-between align-items-start flex-wrap gap-3"><div><h2>⚙️ Automation Center</h2><div class="muted">Programa backups, reinicios, mensajes RCON y actualizaciones SteamCMD desde Windows/XAMPP.</div></div><div class="d-flex gap-2"><button class="btn btn-outline-light" onclick="runWorker()">▶ Ejecutar pendientes</button><button class="btn btn-outline-info" onclick="loadTasks()">↻ Refrescar</button></div></div>
 <div class="cardx"><h3 class="h5 mb-3">➕ Nueva tarea</h3><div class="row g-3">
  <div class="col-md-3"><label class="form-label">Nombre</label><input id="aName" class="form-control" placeholder="Backup nocturno"></div>
  <div class="col-md-3"><label class="form-label">Acción</label><select id="aAction" class="form-select"><option value="backup">💾 Backup</option><option value="restart">🔄 Reiniciar servidor</option><option value="steam_update">⬇️ Steam Update</option><option value="rcon_message">📢 Mensaje RCON</option></select></div>
  <div class="col-md-2"><label class="form-label">Frecuencia</label><select id="aType" class="form-select" onchange="toggleSchedule()"><option value="interval">Intervalo</option><option value="daily">Diaria</option></select></div>
  <div class="col-md-2" id="intervalBox"><label class="form-label">Cada (min)</label><input id="aMinutes" type="number" min="1" value="60" class="form-control"></div>
  <div class="col-md-2 d-none" id="dailyBox"><label class="form-label">Hora</label><input id="aTime" type="time" value="03:00" class="form-control"></div>
  <div class="col-md-8"><label class="form-label">Payload opcional</label><input id="aPayload" class="form-control" placeholder='Para RCON: showMessage "Backup pronto"'></div>
  <div class="col-md-4 d-grid align-items-end"><button class="btn btn-success" onclick="createTask()">💾 Crear tarea</button></div>
 </div><div id="aMsg" class="mt-3"></div></div>
 <div class="cardx"><div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="h5 mb-1">📋 Tareas</h3><div class="muted small">El worker puede ejecutarse desde esta página o desde el Programador de tareas de Windows.</div></div></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nombre</th><th>Acción</th><th>Programación</th><th>Próxima</th><th>Último resultado</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="tasksBody"><tr><td colspan="7">Cargando…</td></tr></tbody></table></div></div>
</div>
<script>
(function(){
 const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 window.toggleSchedule=function(){const d=document.getElementById('aType').value==='daily';document.getElementById('intervalBox').classList.toggle('d-none',d);document.getElementById('dailyBox').classList.toggle('d-none',!d)};
 window.loadTasks=function(){fetch('api.php?action=automation_list',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{const b=document.getElementById('tasksBody');if(!j.ok){b.innerHTML='<tr><td colspan="7" class="text-danger">Error</td></tr>';return;}if(!j.items.length){b.innerHTML='<tr><td colspan="7">📭 Sin tareas</td></tr>';return;}b.innerHTML=j.items.map(t=>`<tr><td><strong>${esc(t.name)}</strong></td><td>${esc(t.action)}</td><td>${esc(t.schedule_label)}</td><td>${t.next_run_ts?new Date(t.next_run_ts*1000).toLocaleString():'-'}</td><td>${esc(t.last_result||'-')}</td><td><span class="badge bg-${t.enabled?'success':'secondary'}">${t.enabled?'Activa':'Pausada'}</span></td><td><button class="btn btn-sm btn-warning" onclick="toggleTask('${t.id}')">${t.enabled?'⏸':'▶'}</button> <button class="btn btn-sm btn-danger" onclick="deleteTask('${t.id}')">🗑</button></td></tr>`).join('');}).catch(()=>{});};
 window.createTask=function(){const type=document.getElementById('aType').value;const body=new URLSearchParams();body.set('name',document.getElementById('aName').value||'Nueva tarea');body.set('action',document.getElementById('aAction').value);body.set('schedule_type',type);body.set('minutes',document.getElementById('aMinutes').value||'60');body.set('time',document.getElementById('aTime').value||'03:00');body.set('payload',document.getElementById('aPayload').value||'');fetch('api.php?action=automation_create',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString(),credentials:'same-origin'}).then(r=>r.json()).then(j=>{document.getElementById('aMsg').innerHTML=j.ok?'<span class="text-success">✅ Tarea creada.</span>':'<span class="text-danger">❌ '+esc(j.error)+'</span>';loadTasks();});};
 window.toggleTask=id=>fetch('api.php?action=automation_toggle',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id),credentials:'same-origin'}).then(r=>r.json()).then(()=>loadTasks());
 window.deleteTask=id=>{if(!confirm('¿Eliminar tarea?'))return;fetch('api.php?action=automation_delete',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+encodeURIComponent(id),credentials:'same-origin'}).then(r=>r.json()).then(()=>loadTasks());};
 window.runWorker=()=>fetch('automation_worker.php',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{alert('✅ Worker ejecutado. Tareas procesadas: '+(j.count||0));loadTasks();});
 loadTasks();
})();
</script>
