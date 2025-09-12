<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>
<div class="container mt-4">
  <h2>📊 Métricas detalladas del sistema</h2>
  <p>Actualización automática cada 5 segundos</p>

  <pre id="metricsBox" style="background:#111;color:#0f0;padding:15px;border-radius:8px;">
Cargando métricas…
  </pre>
</div>

<script>
function updateMetrics() {
  fetch('api.php?action=metrics',{credentials:'same-origin'})
    .then(r=>r.json())
    .then(data=>{
      if(!data.ok){
        document.getElementById('metricsBox').textContent = "Error al obtener métricas.";
        return;
      }
      const m = data;
      document.getElementById('metricsBox').textContent =
`Sistema operativo : ${m.os}
Hostname          : ${m.hostname}
Arquitectura      : ${m.arch}
CPU total         : ${m.cpu_model}
Núcleos lógicos   : ${m.cpu_cores}
Uso CPU           : ${m.cpu}% 
Memoria total     : ${m.ram_total} MB
Memoria usada     : ${m.ram_used} MB
Uso RAM           : ${m.ram}% 
Disco raíz total  : ${m.disk_total} GB
Disco raíz usado  : ${m.disk_used} GB (${m.disk}%)
Uptime sistema    : ${m.uptime}`;
    })
    .catch(()=>{document.getElementById('metricsBox').textContent="Error de red";});
}
updateMetrics();
setInterval(updateMetrics,5000);
</script>
