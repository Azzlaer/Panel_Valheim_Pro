<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}
?>
<div class="container mt-4">
    <h2>🗂️ Respaldo / Restauración</h2>

    <button id="create-backup" class="btn btn-primary mb-3">📦 Crear Respaldo Manual</button>
    <div class="progress mb-3" style="height:25px; display:none;" id="backup-progress">
        <div class="progress-bar progress-bar-striped progress-bar-animated"
             role="progressbar" style="width: 0%">0%</div>
    </div>

    <div id="backup-msg"></div>

    <h4>Respaldos disponibles</h4>
    <table class="table table-dark table-striped" id="backup-table">
        <thead>
            <tr><th>Archivo</th><th>Tamaño</th><th>Descargar</th><th>Eliminar</th></tr>
        </thead>
        <tbody id="backup-list"><tr><td colspan="4">Cargando…</td></tr></tbody>
    </table>
</div>

<script>
function loadBackups() {
  fetch("api.php?action=list_backups",{credentials:"same-origin"})
    .then(r=>r.json())
    .then(list=>{
      const body = document.getElementById("backup-list");
      if (!Array.isArray(list) || list.length===0) {
        body.innerHTML = "<tr><td colspan='4'>📭 No hay respaldos.</td></tr>";
        return;
      }
      body.innerHTML = list.map(b=>`
        <tr>
          <td>${b.name}</td>
          <td>${b.size_mb} MB</td>
          <td><a class="btn btn-success btn-sm" href="download.php?file=${encodeURIComponent(b.name)}">⬇️ Descargar</a></td>
          <td><button class="btn btn-danger btn-sm" onclick="deleteBackup('${b.name}')">🗑️ Eliminar</button></td>
        </tr>`).join('');
    });
}
function deleteBackup(name){
  fetch("api.php?action=delete_backup",{method:"POST",credentials:"same-origin",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"file="+encodeURIComponent(name)})
    .then(()=>loadBackups());
}
document.getElementById("create-backup").addEventListener("click",()=>{
  const barContainer = document.getElementById("backup-progress");
  const bar = barContainer.querySelector(".progress-bar");
  barContainer.style.display = "block";
  bar.style.width = "0%"; bar.textContent = "0%";

  // Usamos XMLHttpRequest para mostrar progreso
  const xhr = new XMLHttpRequest();
  xhr.open("POST","api.php?action=create_backup");
  xhr.upload.onprogress = e=>{
     if(e.lengthComputable){
       let p = Math.round((e.loaded/e.total)*100);
       bar.style.width = p+"%";
       bar.textContent = p+"%";
     }
  };
  xhr.onload = ()=>{
    bar.style.width = "100%";
    bar.textContent = "100%";
    setTimeout(()=>{barContainer.style.display="none";},1000);
    loadBackups();
    document.getElementById("backup-msg").innerHTML =
       `<div class="alert alert-info mt-2">✅ Respaldo completado</div>`;
  };
  xhr.send(new FormData()); // no data, solo trigger
});

loadBackups();
</script>
