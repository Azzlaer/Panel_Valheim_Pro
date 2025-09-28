<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Requiere sesión iniciada
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$file   = $_GET['file']   ?? '';

if (!$file || !preg_match('/\.fwl$/i', $file)) {
    echo json_encode(['ok' => false, 'error' => 'Archivo inválido']);
    exit;
}

$path = realpath(WORLDS_DIR . DIRECTORY_SEPARATOR . $file);
if (!$path || strpos($path, realpath(WORLDS_DIR)) !== 0) {
    echo json_encode(['ok' => false, 'error' => 'Ruta fuera de worlds_local']);
    exit;
}

// Ruta al ejecutable de Python y a valheim-save-tools (ajusta si instalaste en otro sitio)
$python = 'python'; // o py, python3, etc según tu instalación
$cli    = __DIR__ . '/valheim-save-tools/vstools.py'; // ajusta a la ubicación real del script

switch ($action) {
    case 'read':
        // Ejecuta valheim-save-tools en modo dump
        $cmd = escapeshellcmd($python) . ' ' . escapeshellarg($cli) . ' dump ' . escapeshellarg($path);
        $output = shell_exec($cmd);
        if ($output === null) {
            echo json_encode(['ok' => false, 'error' => 'Error ejecutando valheim-save-tools']);
            exit;
        }
        // Se asume que el dump devuelve JSON
        echo json_encode(['ok' => true, 'data' => json_decode($output, true)]);
        break;

    case 'write':
        $json = file_get_contents('php://input');
        if (!$json) {
            echo json_encode(['ok' => false, 'error' => 'Sin datos']);
            exit;
        }
        // Guardar en archivo temporal
        $tmp = tempnam(sys_get_temp_dir(), 'fwl');
        file_put_contents($tmp, $json);

        // valheim-save-tools write <archivo.fwl> <archivo.json>
        $cmd = escapeshellcmd($python) . ' ' . escapeshellarg($cli) . ' write ' .
               escapeshellarg($path) . ' ' . escapeshellarg($tmp);
        $res = shell_exec($cmd);
        unlink($tmp);

        if ($res === null) {
            echo json_encode(['ok' => false, 'error' => 'Error al escribir con valheim-save-tools']);
            exit;
        }
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Acción inválida']);
        break;
}
