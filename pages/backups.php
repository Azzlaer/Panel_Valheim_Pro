<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>
<div class="container mt-4">
  <h2>🗂️ Respaldos de mundos (worlds_local)</h2>

  <button id="create-backup" class="btn btn-primary mb-3">Crear respaldo manual</button>

  <div id="backup-msg" class="text-info mb-2"></div>

  <div class="table-responsive">
    <table class="table table-dark table-striped text-center align-middle">
      <thead>
        <tr>
          <th>Archivo</th>
          <th>Tamaño</th>
          <th>Creado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="backups-table">
        <tr><td colspan="4">Cargando…</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
function loadBackups(){
  fetch('api.php?action=list_backups',{credentials:'same-origin'})
    .then(r=>r.json())
    .then(data=>{
      if(!data.ok){ throw new Error(data.error||'Error'); }
      const tbody = document.getElementById('backups-table');
      if(data.items.length===0){
        tbody.innerHTML='<tr><td colspan="4">📭 Sin respaldos</td></tr>';
        return;
      }
      tbody.innerHTML = data.items.map(f=>`
        <tr>
          <td>${f.name}</td>
          <td>${(f.size_mb).toFixed(2)} MB</td>
          <td>${f.mtime}</td>
          <td>
            <a class="btn btn-success btn-sm" href="backups/${encodeURIComponent(f.name)}" download>⬇️ Descargar</a>
            <button class="btn btn-danger btn-sm" onclick="deleteBackup('${encodeURIComponent(f.name)}')">🗑️ Eliminar</button>
          </td>
        </tr>`).join('');
    })
    .catch(e=>{
      document.getElementById('backups-table').innerHTML =
        `<tr><td colspan="4" class="text-danger">⚠️ ${e}</td></tr>`;
    });
}

function createBackup(){
  const msg = document.getElementById('backup-msg');
  msg.textContent='⏳ Creando respaldo...';
  fetch('api.php?action=create_backup',{credentials:'same-origin'})
    .then(r=>r.json())
    .then(d=>{
      if(d.ok){
        msg.textContent='✅ Respaldo creado: '+d.file;
        loadBackups();
      }else{
        msg.textContent='❌ '+(d.error||'Error');
      }
    })
    .catch(e=>{msg.textContent='⚠️ '+e});
}

function deleteBackup(name){
  if(!confirm('¿Eliminar ' + name + '?')) return;
  fetch('api.php?action=delete_backup&file='+name,{credentials:'same-origin'})
    .then(r=>r.json())
    .then(d=>{
      if(d.ok){ loadBackups(); }
      else { alert('❌ '+(d.error||'Error')); }
    });
}

document.getElementById('create-backup').addEventListener('click', createBackup);
loadBackups();
</script>
