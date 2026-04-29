<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

// ✅ Verificar sesión
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

// ✅ Validar archivo
if (
    !isset($_FILES['plugin']) ||
    $_FILES['plugin']['error'] !== UPLOAD_ERR_OK
) {
    echo json_encode(['ok' => false, 'error' => 'No se recibió archivo o hubo error en la subida']);
    exit;
}

// ✅ Extensiones permitidas
$ext = strtolower(pathinfo($_FILES['plugin']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['dll', 'db'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Extensión no permitida. Solo .dll o .db']);
    exit;
}

// ✅ Verificar carpeta destino
if (!is_dir(PLUGINS_DIR)) {
    echo json_encode(['ok' => false, 'error' => 'La carpeta de plugins no existe']);
    exit;
}

// ✅ Mover archivo
$dest = PLUGINS_DIR . DIRECTORY_SEPARATOR . basename($_FILES['plugin']['name']);
if (!move_uploaded_file($_FILES['plugin']['tmp_name'], $dest)) {
    echo json_encode(['ok' => false, 'error' => 'No se pudo mover el archivo subido']);
    exit;
}

echo json_encode(['ok' => true, 'file' => basename($dest)]);
