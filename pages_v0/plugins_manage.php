<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Acceso denegado']);
    exit;
}

$action = $_GET['action'] ?? '';
$file   = $_GET['file']   ?? '';
if ($file === '') { echo json_encode(['ok'=>false,'error'=>'Falta archivo']); exit; }

$base  = realpath(PLUGINS_DIR);
$path  = realpath($base . DIRECTORY_SEPARATOR . $file);

// Validar alcance
if (!$base || !$path || strpos($path, $base) !== 0) {
    echo json_encode(['ok'=>false,'error'=>'Ruta fuera de alcance']);
    exit;
}

// Helpers
function resp($ok, $err=null){ echo json_encode(['ok'=>$ok,'error'=>$err]); exit; }

switch ($action) {
    case 'delete':
        if (!is_file($path)) resp(false,'Archivo no existe');
        if (!@unlink($path)) resp(false,'No se pudo eliminar');
        resp(true);
        break;

    case 'disable':
        if (substr($path, -4) !== '.dll') resp(false,'Solo .dll se pueden deshabilitar');
        $new = $path . '.disable';
        if (file_exists($new)) resp(false,'Ya existe un .disable');
        if (!@rename($path, $new)) resp(false,'No se pudo renombrar');
        resp(true);
        break;

    case 'enable':
        if (substr($path, -8) !== '.disable') resp(false,'Solo .disable se pueden habilitar');
        $new = substr($path, 0, -8) . '.dll';
        if (file_exists($new)) resp(false,'Ya existe un .dll con ese nombre');
        if (!@rename($path, $new)) resp(false,'No se pudo renombrar');
        resp(true);
        break;

    default:
        resp(false,'Acción inválida');
}
