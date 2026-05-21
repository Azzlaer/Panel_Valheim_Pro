<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado'], JSON_UNESCAPED_UNICODE);
    exit;
}

function respond(bool $ok, string $msg = '', ?string $error = null, int $httpCode = 200, array $extra = []): void {
    http_response_code($httpCode);
    echo json_encode(array_merge([
        'ok'    => $ok,
        'msg'   => $msg,
        'error' => $error
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function uploadErrorMessage(int $code): string {
    return match ($code) {
        UPLOAD_ERR_INI_SIZE   => 'El archivo excede el tamaño máximo permitido por el servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el tamaño máximo permitido por el formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
        UPLOAD_ERR_EXTENSION  => 'La subida fue detenida por una extensión de PHP.',
        default               => 'Ocurrió un error desconocido durante la subida.'
    };
}

if (!isset($_FILES['plugin'])) {
    respond(false, '', 'No se recibió ningún archivo.', 400);
}

$file = $_FILES['plugin'];

if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    respond(false, '', uploadErrorMessage((int)($file['error'] ?? -1)), 400);
}

if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
    respond(false, '', 'El archivo subido no es válido.', 400);
}

if (!defined('PLUGINS_DIR') || !is_dir(PLUGINS_DIR)) {
    respond(false, '', 'La carpeta de plugins no existe.', 500);
}

if (!is_writable(PLUGINS_DIR)) {
    respond(false, '', 'La carpeta de plugins no tiene permisos de escritura.', 500);
}

// Tamaño máximo recomendado: 100 MB
$maxSize = 100 * 1024 * 1024;
if (!isset($file['size']) || (int)$file['size'] <= 0) {
    respond(false, '', 'El archivo recibido está vacío o no tiene tamaño válido.', 400);
}
if ((int)$file['size'] > $maxSize) {
    respond(false, '', 'El archivo excede el límite permitido de 100 MB.', 400);
}

$originalName = $file['name'] ?? '';
$baseName = basename($originalName);

// Bloquear nombres vacíos o raros
if ($baseName === '' || $baseName === '.' || $baseName === '..') {
    respond(false, '', 'Nombre de archivo inválido.', 400);
}

// Permitir letras, números, puntos, guiones, espacios y guion bajo
$sanitizedName = preg_replace('/[^A-Za-z0-9._\-\s]/', '_', $baseName);
$sanitizedName = trim($sanitizedName);

if ($sanitizedName === '') {
    respond(false, '', 'El nombre del archivo quedó inválido tras sanearlo.', 400);
}

$ext = strtolower(pathinfo($sanitizedName, PATHINFO_EXTENSION));
$allowed = ['dll', 'db'];

if (!in_array($ext, $allowed, true)) {
    respond(false, '', 'Extensión no permitida. Solo se aceptan archivos .dll o .db.', 400);
}

$destDir = realpath(PLUGINS_DIR);
if ($destDir === false) {
    respond(false, '', 'No se pudo resolver la carpeta de plugins.', 500);
}

$dest = $destDir . DIRECTORY_SEPARATOR . $sanitizedName;

// No sobreescribir silenciosamente
if (file_exists($dest)) {
    respond(false, '', 'Ya existe un archivo con ese nombre en la carpeta de plugins.', 409);
}

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    respond(false, '', 'No se pudo mover el archivo subido al directorio de destino.', 500);
}

respond(
    true,
    'Archivo subido correctamente.',
    null,
    200,
    [
        'file'      => basename($dest),
        'size_bytes'=> (int)$file['size'],
        'ext'       => $ext
    ]
);