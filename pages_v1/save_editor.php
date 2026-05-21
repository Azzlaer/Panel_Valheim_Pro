<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in'])) { http_response_code(403); exit("Acceso denegado"); }
?>
<div class="container mt-4 text-light">
  <h2>📝 Editor de archivos .FWL</h2>
  <p>Sube un archivo FWL o selecciona uno de la carpeta <code>worlds_local</code>.</p>
  <input type="file" id="fwlFile" accept=".fwl" class="form-control mb-3">
  <button class="btn btn-primary" onclick="processFWL()">Procesar</button>
  <pre id="fwlOutput" class="bg-dark p-3 mt-3 rounded"></pre>
</div>

<script>
function processFWL(){
  const f = document.getElementById('fwlFile').files[0];
  if(!f){ alert('Selecciona un archivo FWL'); return; }
  const fd = new FormData();
  fd.append('file', f);

  fetch('tools_fwl_api.php?action=read', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(j=>document.getElementById('fwlOutput').textContent = JSON.stringify(j,null,2))
    .catch(()=>alert('Error al procesar el archivo.'));
}
</script>
