<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit;
}

function respond(bool $ok, string $msg = '', ?string $error = null, int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode([
        'ok'    => $ok,
        'msg'   => $msg,
        'error' => $error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_slashes(string $path): string {
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function path_is_inside(string $path, string $base): bool {
    $path = rtrim(strtolower(normalize_slashes($path)), DIRECTORY_SEPARATOR);
    $base = rtrim(strtolower(normalize_slashes($base)), DIRECTORY_SEPARATOR);
    return $path === $base || strpos($path, $base . DIRECTORY_SEPARATOR) === 0;
}

$action = trim($_GET['action'] ?? '');
$file   = trim($_GET['file'] ?? '');

if ($action === '') {
    respond(false, '', 'Falta acción', 400);
}

if ($file === '') {
    respond(false, '', 'Falta archivo', 400);
}

// Rechazar nombres sospechosos antes de tocar rutas
if (
    strpos($file, '..') !== false ||
    strpos($file, '/') !== false ||
    strpos($file, '\\') !== false ||
    basename($file) !== $file
) {
    respond(false, '', 'Nombre de archivo inválido', 400);
}

$base = realpath(PLUGINS_DIR);
if (!$base || !is_dir($base)) {
    respond(false, '', 'PLUGINS_DIR inválido o no existe', 500);
}

// Ruta candidata
$candidatePath = $base . DIRECTORY_SEPARATOR . $file;

// Para delete/disable normalmente debe existir.
// Para enable también debe existir el .disable real.
if (!file_exists($candidatePath)) {
    respond(false, '', 'Archivo no existe', 404);
}

$path = realpath($candidatePath);
if (!$path || !path_is_inside($path, $base)) {
    respond(false, '', 'Ruta fuera de alcance', 400);
}

if (!is_file($path)) {
    respond(false, '', 'La ruta indicada no es un archivo', 400);
}

$filename = basename($path);
$lowerName = strtolower($filename);

switch ($action) {
    case 'delete': {
        if (!@unlink($path)) {
            respond(false, '', 'No se pudo eliminar el archivo', 500);
        }
        respond(true, 'Archivo eliminado correctamente');
    }

    case 'disable': {
        if (!str_ends_with($lowerName, '.dll')) {
            respond(false, '', 'Solo archivos .dll se pueden deshabilitar', 400);
        }

        $newPath = $path . '.disable';

        if (file_exists($newPath)) {
            respond(false, '', 'Ya existe un archivo .disable con ese nombre', 409);
        }

        if (!@rename($path, $newPath)) {
            respond(false, '', 'No se pudo deshabilitar el plugin', 500);
        }

        respond(true, 'Plugin deshabilitado correctamente');
    }

    case 'enable': {
        if (!str_ends_with($lowerName, '.disable')) {
            respond(false, '', 'Solo archivos .disable se pueden habilitar', 400);
        }

        $newPath = substr($path, 0, -8) . '.dll';

        if (file_exists($newPath)) {
            respond(false, '', 'Ya existe un archivo .dll con ese nombre', 409);
        }

        if (!@rename($path, $newPath)) {
            respond(false, '', 'No se pudo habilitar el plugin', 500);
        }

        respond(true, 'Plugin habilitado correctamente');
    }

    default:
        respond(false, '', 'Acción inválida', 400);
}