<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/world_manager.php';
if (is_file(__DIR__ . '/lib/event_center.php')) {
    require_once __DIR__ . '/lib/event_center.php';
}

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'Acceso denegado'], JSON_UNESCAPED_UNICODE);
    exit;
}

function wa_out(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function wa_csrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        wa_out(['ok'=>false,'error'=>'Token CSRF inválido'], 403);
    }
}

function wa_log(string $type, string $message, string $level = 'info', array $meta = []): void {
    if (function_exists('ec_log')) {
        ec_log($type, $message, $level, $meta);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            wa_out(['ok'=>true,'data'=>wm_scan(),'server_running'=>wm_server_running()]);

        case 'set_active':
            wa_csrf();
            if (wm_server_running()) {
                wa_out(['ok'=>false,'error'=>'Detén el servidor antes de cambiar el mundo activo'], 409);
            }
            $world = trim((string)($_POST['world'] ?? ''));
            if (!wm_set_active_world($world)) {
                wa_out(['ok'=>false,'error'=>'No se pudo actualizar servers.json'], 500);
            }
            wa_log('world', 'Mundo activo cambiado a ' . $world, 'success', ['world'=>$world]);
            wa_out(['ok'=>true,'msg'=>'Mundo activo actualizado','world'=>$world]);

        case 'delete_backup':
            wa_csrf();
            $name = trim((string)($_POST['name'] ?? ''));
            $parsed = wm_parse_backup_name($name);
            if (!$parsed) wa_out(['ok'=>false,'error'=>'La carpeta no parece un backup válido'], 400);
            $path = wm_resolve_entry($name);
            if (!is_dir($path)) wa_out(['ok'=>false,'error'=>'Backup no encontrado'], 404);
            wm_delete_tree($path);
            wa_log('world_backup', 'Backup de mundo eliminado: ' . $name, 'warning', ['backup'=>$name,'world'=>$parsed['world']]);
            wa_out(['ok'=>true,'msg'=>'Backup eliminado']);

        case 'delete_world':
            wa_csrf();
            $name = trim((string)($_POST['name'] ?? ''));
            $confirm = trim((string)($_POST['confirm'] ?? ''));
            if ($confirm !== $name) {
                wa_out(['ok'=>false,'error'=>'La confirmación no coincide con el nombre del mundo'], 400);
            }
            wm_delete_world($name);
            wa_log('world', 'Mundo eliminado: ' . $name, 'warning', ['world'=>$name]);
            wa_out(['ok'=>true,'msg'=>'Mundo eliminado']);

        case 'download':
            $name = trim((string)($_GET['name'] ?? ''));
            $bundle = wm_zip_entry($name);
            $file = $bundle['path'];
            $downloadName = preg_replace('/[^A-Za-z0-9_.\-]/', '_', $bundle['name']);
            if (!is_file($file)) throw new RuntimeException('No se pudo preparar la descarga');

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $downloadName . '"');
            header('Content-Length: ' . filesize($file));
            header('Cache-Control: no-store');
            readfile($file);
            @unlink($file);
            exit;

        default:
            wa_out(['ok'=>false,'error'=>'Acción inválida'], 400);
    }
} catch (Throwable $e) {
    wa_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
