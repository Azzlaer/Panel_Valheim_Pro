<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

/**
 * Obtiene el proceso valheim_server.exe activo que pertenece al directorio actual (SERVER_DIR).
 */
function getValheimProcessForThisServer() {
    if (!defined('SERVER_DIR')) return [];

    // Escapamos el path para PowerShell
    $dirEscaped = str_replace('\\', '\\\\', SERVER_DIR);

    // Obtenemos SOLO procesos valheim_server.exe cuyo ExecutablePath coincide con SERVER_DIR
    $psCommand = "
        \$procs = Get-CimInstance Win32_Process | Where-Object { \$_.Name -eq 'valheim_server.exe' -and \$_.ExecutablePath -like '${dirEscaped}*' };
        if (\$procs) {
            \$procs | Select-Object ProcessId, Name, ExecutablePath, HandleCount, WorkingSetSize, VirtualSize, CreationDate, CommandLine | ConvertTo-Json -Compress
        }
    ";

    $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($psCommand);
    $output = @shell_exec($cmd);

    if (empty($output)) return [];

    $data = @json_decode(trim($output), true);
    if (!is_array($data)) return [];

    // Si devuelve un solo objeto, normalizamos a array
    if (isset($data['ProcessId'])) {
        $data = [$data];
    }

    return $data;
}

// Matar proceso
if (isset($_POST['kill_pid'])) {
    $pid = intval($_POST['kill_pid']);
    if ($pid > 0) {
        // Validamos primero que el proceso pertenece a este servidor
        $procs = getValheimProcessForThisServer();
        $found = false;
        foreach ($procs as $p) {
            if ((int)$p['ProcessId'] === $pid) {
                $found = true;
                break;
            }
        }

        if ($found) {
            $res = shell_exec("taskkill /PID $pid /F 2>&1");
            echo json_encode(['ok' => true, 'msg' => "Proceso $pid terminado.", 'output' => $res]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'El proceso no pertenece a este servidor.']);
        }
    } else {
        echo json_encode(['ok' => false, 'error' => 'PID inválido.']);
    }
    exit;
}

$processList = getValheimProcessForThisServer();
?>
<div class="container mt-4">
    <h2>⚙️ Proceso del Servidor Actual</h2>
    <p class="text-muted">
        Mostrando detalles de <code>valheim_server.exe</code> correspondientes a este panel.<br>
        <small>Directorio del servidor: <code><?= htmlspecialchars(SERVER_DIR) ?></code></small>
    </p>

    <div class="table-responsive">
        <table class="table table-dark table-bordered table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>PID</th>
                    <th>Ruta ejecutable</th>
                    <th>Memoria (MB)</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($processList)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-warning">
                            ⚠️ No se encontró ningún proceso de este servidor.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($processList as $p):
                        $pid  = htmlspecialchars($p['ProcessId'] ?? '');
        $path = htmlspecialchars($p['ExecutablePath'] ?? '');
        $ram  = isset($p['WorkingSetSize']) ? round($p['WorkingSetSize'] / 1048576, 2) : 'N/A';
        $created = htmlspecialchars($p['CreationDate'] ?? 'N/A');
        ?>
                        <tr class="table-success">
                            <td><code><?= $pid ?></code></td>
                            <td class="text-start small"><?= $path ?: 'N/A' ?></td>
                            <td><?= $ram ?></td>
                            <td><span class="badge bg-success">Servidor actual</span></td>
                            <td><?= $created ?></td>
                            <td>
                                <button class="btn btn-danger btn-sm" onclick="killProcess(<?= $pid ?>)">🛑 Terminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-3 text-end">
        <button class="btn btn-secondary" onclick="reloadProc()">🔄 Actualizar</button>
    </div>
</div>

<script>
function reloadProc() {
  fetch('pages/procesos_valheim.php')
    .then(r => r.text())
    .then(html => document.getElementById('main').innerHTML = html)
    .catch(() => alert('Error al recargar la lista de procesos.'));
}

function killProcess(pid) {
  if (!confirm('⚠️ ¿Seguro que deseas terminar el proceso PID ' + pid + '?')) return;
  const formData = new FormData();
  formData.append('kill_pid', pid);

  fetch('pages/procesos_valheim.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(j => {
      if (j.ok) {
        alert('✅ ' + j.msg);
        reloadProc();
      } else {
        alert('❌ ' + (j.error || 'Error desconocido'));
      }
    })
    .catch(() => alert('⚠️ Error al intentar terminar el proceso.'));
}
</script>
