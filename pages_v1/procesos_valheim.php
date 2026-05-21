<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

/**
 * Obtiene todos los procesos valheim_server.exe activos en Windows 11.
 */
function getValheimProcesses() {
    $psCommand = 'Get-CimInstance Win32_Process | Where-Object { $_.Name -eq \"valheim_server.exe\" } | Select-Object ProcessId, Name, ExecutablePath, HandleCount, WorkingSetSize, VirtualSize, CreationDate, CommandLine | ConvertTo-Json -Compress';
    $output = @shell_exec('C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psCommand . '"');

    if (!isset($output) || !is_string($output) || trim($output) === '') {
        return [];
    }

    $data = @json_decode(trim($output), true);
    if (!is_array($data)) {
        return [];
    }

    if (isset($data['ProcessId'])) {
        $data = [$data];
    }

    return $data;
}

// Terminar proceso
if (isset($_POST['kill_pid'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pid = intval($_POST['kill_pid']);
    if ($pid > 0) {
        $res = shell_exec("taskkill /PID $pid /F 2>&1");
        echo json_encode(['ok' => true, 'msg' => "Proceso $pid terminado.", 'output' => $res], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['ok' => false, 'error' => 'PID inválido.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$processList = getValheimProcesses();
$totalProcesses = count($processList);
$currentCount = 0;
$totalRam = 0;

foreach ($processList as $p) {
    $path = $p['ExecutablePath'] ?? '';
    $ram  = isset($p['WorkingSetSize']) ? round($p['WorkingSetSize'] / 1048576, 2) : 0;
    $totalRam += $ram;

    if (defined('SERVER_DIR') && $path && stripos($path, str_replace('/', '\\', SERVER_DIR)) !== false) {
        $currentCount++;
    }
}
?>

<style>
.proc-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(239,68,68,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}
.proc-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}
.proc-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}
.proc-wrap .hero-actions .btn {
    border-radius: 12px;
    font-weight: 600;
}
.proc-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}
.proc-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}
.proc-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}
.proc-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}
.proc-wrap .module-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
}
.proc-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.proc-wrap .section-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}
.proc-wrap .section-header small {
    color: #9ca3af;
}
.proc-wrap .table-dark {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255,255,255,.02);
    --bs-table-hover-bg: rgba(255,255,255,.035);
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.06);
    margin-bottom: 0;
}
.proc-wrap .table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    font-weight: 700;
    padding-top: 14px;
    padding-bottom: 14px;
}
.proc-wrap .proc-path,
.proc-wrap .proc-created {
    color: #cbd5e1;
    font-size: 13px;
    line-height: 1.45;
    word-break: break-word;
}
.proc-wrap .pid-code {
    color: #fff;
    font-weight: 700;
}
.proc-wrap .btn-action {
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 12px;
}
@media (max-width: 991px) {
    .proc-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .proc-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 proc-wrap">

    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>⚙️ Procesos activos de Valheim</h2>
                <p>Inspecciona procesos <code>valheim_server.exe</code> detectados en Windows y administra su finalización manual desde el panel.</p>
            </div>
            <div class="hero-actions d-flex gap-2">
                <button class="btn btn-outline-light" onclick="reloadProc()">🔄 Actualizar</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Procesos detectados</div>
            <div class="stat-value"><?= (int)$totalProcesses ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Servidor actual</div>
            <div class="stat-value"><?= (int)$currentCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">RAM total (MB)</div>
            <div class="stat-value"><?= number_format($totalRam, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Ruta monitoreada</div>
            <div class="stat-value" style="font-size:13px; line-height:1.5; font-weight:600;">
                <?= htmlspecialchars(SERVER_DIR) ?>
            </div>
        </div>
    </div>

    <div class="module-card">
        <div class="section-header">
            <div>
                <h3>📋 Lista de procesos</h3>
                <small>Se resaltan los procesos que coinciden con el servidor actual configurado.</small>
            </div>
        </div>

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
                            <td colspan="6" class="text-center text-warning py-4">
                                ⚠️ No se encontró ningún proceso <code>valheim_server.exe</code>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($processList as $p):
                            $pid  = htmlspecialchars($p['ProcessId'] ?? '');
                            $path = htmlspecialchars($p['ExecutablePath'] ?? '');
                            $ram  = isset($p['WorkingSetSize']) ? round($p['WorkingSetSize'] / 1048576, 2) : 'N/A';
                            $created = htmlspecialchars($p['CreationDate'] ?? 'N/A');
                            $isCurrent = false;

                            if (defined('SERVER_DIR') && $path && stripos($path, str_replace('/', '\\', SERVER_DIR)) !== false) {
                                $isCurrent = true;
                            }
                        ?>
                            <tr class="<?= $isCurrent ? 'table-success' : '' ?>">
                                <td><code class="pid-code"><?= $pid ?></code></td>
                                <td class="text-start"><span class="proc-path"><?= $path ?: 'N/A' ?></span></td>
                                <td><?= $ram ?></td>
                                <td>
                                    <?= $isCurrent
                                        ? '<span class="badge bg-success">Servidor actual</span>'
                                        : '<span class="badge bg-secondary">Otro servidor</span>' ?>
                                </td>
                                <td><span class="proc-created"><?= $created ?></span></td>
                                <td>
                                    <button class="btn btn-danger btn-sm btn-action" onclick="killProcess(<?= $pid ?>)">🛑 Terminar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function reloadProc() {
  fetch('pages/procesos_valheim.php', { credentials: 'same-origin' })
    .then(r => r.text())
    .then(html => document.getElementById('main').innerHTML = html)
    .catch(() => alert('Error al recargar la lista de procesos.'));
}

function killProcess(pid) {
  if (!confirm('⚠️ ¿Seguro que deseas terminar el proceso PID ' + pid + '?')) return;

  const formData = new FormData();
  formData.append('kill_pid', pid);

  fetch('pages/procesos_valheim.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
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