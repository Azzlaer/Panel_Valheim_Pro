<?php
require_once __DIR__ . '/config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

if (!isset($_FILES['plugin']) || $_FILES['plugin']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('❌ Error en el archivo subido.');
}

$ext = strtolower(pathinfo($_FILES['plugin']['name'], PATHINFO_EXTENSION));
if ($ext !== 'dll') {
    http_response_code(400);
    exit('❌ Solo se permiten archivos .dll');
}

$target = rtrim(PLUGINS_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($_FILES['plugin']['name']);

if (move_uploaded_file($_FILES['plugin']['tmp_name'], $target)) {
    echo "✅ Archivo subido correctamente a: " . htmlspecialchars($target);
} else {
    http_response_code(500);
    echo "❌ No se pudo guardar el archivo.";
}
