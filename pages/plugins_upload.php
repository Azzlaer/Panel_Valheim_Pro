<?php
require_once __DIR__ . "/../config.php";
header('Content-Type: application/json');

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['ok'=>false,'error'=>'Acceso denegado']); exit;
}
if (!isset($_FILES['dllfile'])) { echo json_encode(['ok'=>false,'error'=>'Archivo no enviado']); exit; }

$f = $_FILES['dllfile'];
if ($f['error'] !== UPLOAD_ERR_OK) { echo json_encode(['ok'=>false,'error'=>'Error de subida']); exit; }
if (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'dll') {
    echo json_encode(['ok'=>false,'error'=>'Solo archivos .dll permitidos']); exit;
}

$dest = PLUGINS_DIR . DIRECTORY_SEPARATOR . basename($f['name']);
if (!move_uploaded_file($f['tmp_name'], $dest)) {
    echo json_encode(['ok'=>false,'error'=>'No se pudo mover archivo']); exit;
}
echo json_encode(['ok'=>true]);
