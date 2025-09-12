<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$action = $_POST['action'] ?? '';
$file   = basename($_POST['file'] ?? '');
$path   = rtrim(PLUGINS_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

if (!$action || !$file || !file_exists($path)) {
    http_response_code(400);
    exit('Parámetros inválidos.');
}

switch ($action) {
    case 'delete':
        if (unlink($path)) echo "✅ Archivo eliminado.";
        else http_response_code(500);
        break;
    case 'disable':
        $new = preg_replace('/\.dll$/i', '.DISABLED', $path);
        if (rename($path, $new)) echo "🛑 Plugin deshabilitado.";
        else http_response_code(500);
        break;
    case 'enable':
        $new = preg_replace('/\.DISABLED$/i', '.dll', $path);
        if (rename($path, $new)) echo "✅ Plugin habilitado.";
        else http_response_code(500);
        break;
    default:
        http_response_code(400);
        echo "Acción no válida.";
}
