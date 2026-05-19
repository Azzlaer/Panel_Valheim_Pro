<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

function getRDPPort() {
    $output = shell_exec('reg query "HKLM\\System\\CurrentControlSet\\Control\\Terminal Server\\WinStations\\RDP-Tcp" /v PortNumber 2>&1');
    if (!$output) return null;

    if (preg_match('/PortNumber\s+REG_DWORD\s+0x([0-9a-fA-F]+)/', $output, $m)) {
        return hexdec($m[1]);
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPort = intval($_POST['port'] ?? 0);
    if ($newPort < 1024 || $newPort > 65535) {
        echo json_encode(['ok' => false, 'error' => 'Puerto inválido. Usa un valor entre 1024 y 65535.']);
        exit;
    }

    // Cambia el puerto en el registro
    $cmd = 'reg add "HKLM\\System\\CurrentControlSet\\Control\\Terminal Server\\WinStations\\RDP-Tcp" /v PortNumber /t REG_DWORD /d ' . $newPort . ' /f';
    shell_exec($cmd);

    echo json_encode(['ok' => true, 'message' => "Puerto cambiado a $newPort. Reinicia el equipo para aplicar los cambios."]);
    exit;
}

$currentPort = getRDPPort();
?>
<div class="container mt-4">
  <h2>🖥️ Configuración del Puerto RDP</h2>
  <p class="text-muted">Visualiza o cambia el puerto del Escritorio Remoto (RDP).</p>

  <div class="card bg-dark text-light p-3">
    <h5>Puerto actual: <span class="text-info"><?= $currentPort ? $currentPort : 'No detectado' ?></span></h5>
    <form id="rdpForm" class="mt-3">
      <div class="input-group">
        <input type="number" name="port" id="port" min="1024" max="65535" class="form-control" placeholder="Nuevo puerto (ej. 3389)" required>
        <button class="btn btn-primary" type="submit">💾 Cambiar puerto</button>
      </div>
    </form>
    <div id="statusMsg" class="mt-3"></div>
  </div>
</div>

<script>
document.getElementById('rdpForm').addEventListener('submit', e => {
  e.preventDefault();
  const formData = new FormData(e.target);
  fetch('pages/rdp_port.php', {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(j => {
    const box = document.getElementById('statusMsg');
    if (j.ok) {
      box.innerHTML = `<div class="alert alert-success">${j.message}</div>`;
    } else {
      box.innerHTML = `<div class="alert alert-danger">❌ ${j.error}</div>`;
    }
  })
  .catch(() => {
    document.getElementById('statusMsg').innerHTML = '<div class="alert alert-warning">⚠️ Error de red</div>';
  });
});
</script>
