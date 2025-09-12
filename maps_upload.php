<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

if (!isset($_FILES['mapfile']) || $_FILES['mapfile']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit('❌ Error en el archivo subido.');
}

$allowed = ['fwl','db','old'];
$ext = strtolower(pathinfo($_FILES['mapfile']['name'], PATHINFO_EXTENSION));
if (!in_array($ext,$allowed)) {
    http_response_code(400);
    exit('❌ Extensión no permitida.');
}

$dest = rtrim(WORLDS_DIR,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($_FILES['mapfile']['name']);
if (move_uploaded_file($_FILES['mapfile']['tmp_name'], $dest)) {
    echo "✅ Archivo subido correctamente.";
} else {
    http_response_code(500);
    echo "❌ No se pudo guardar el archivo.";
}
