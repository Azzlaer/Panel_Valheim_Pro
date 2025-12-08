<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$alertsFile = __DIR__ . '/alerts.json';
if (!file_exists($alertsFile)) {
    echo json_encode(['ok' => false, 'error' => 'No existe alerts.json']);
    exit;
}

$data = json_decode(file_get_contents($alertsFile), true);
if (!is_array($data)) $data = [];

$now = time();
$executed = [];
$changed = false;

foreach ($data as &$a) {
    if ($a['status'] !== 'active') continue;
    if (empty($a['next_run_ts'])) continue;

    if ($now >= $a['next_run_ts']) {
        // --- Ejecutar comando ---
        $cmd = $a['command'] ?? '';
        $srvId = intval($a['server_id'] ?? 0);

        if ($cmd && $srvId) {
            $servers = json_decode(file_get_contents(SERVERS_JSON), true) ?: [];
            $srv = array_values(array_filter($servers, fn($s) => intval($s['id']) === $srvId))[0] ?? null;

            if ($srv) {
                // Ejecutar comando RCON (aquí puedes adaptar tu sistema RCON real)
                $rconExe = RCON_SEND_CMD ?? null; // define esto en config.php si no existe
                if ($rconExe && file_exists($rconExe)) {
                    $fullCmd = $rconExe . ' ' . escapeshellarg($cmd);
                    shell_exec($fullCmd);
                }
                $executed[] = [
                    'id' => $a['id'],
                    'server' => $srv['name'] ?? 'Desconocido',
                    'cmd' => $cmd,
                    'time' => date('Y-m-d H:i:s')
                ];
            }
        }

        // --- Reprogramar o finalizar ---
        $a['repeats_left'] = max(0, intval($a['repeats_left']) - 1);
        if ($a['repeats_left'] > 0) {
            $a['next_run_ts'] = $now + intval($a['interval']);
        } else {
            $a['status'] = 'done';
            $a['next_run_ts'] = null;
        }
        $changed = true;
    }
}

if ($changed) {
    file_put_contents($alertsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

echo json_encode(['ok' => true, 'executed' => $executed]);
