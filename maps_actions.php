<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$file = basename($_POST['file'] ?? '');
$path = rtrim(WORLDS_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

if (!file_exists($path)) {
    http_response_code(404);
    exit('❌ Archivo no encontrado.');
}

if (@unlink($path)) {
    echo "✅ Archivo eliminado.";
} else {
    http_response_code(500);
    echo "❌ No se pudo eliminar.";
}
