<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/bepinex_manager.php';
if (is_file(__DIR__ . '/lib/event_center.php')) {
    require_once __DIR__ . '/lib/event_center.php';
}

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'Acceso denegado'], JSON_UNESCAPED_UNICODE);
    exit;
}

function ba_out(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ba_csrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        ba_out(['ok'=>false,'error'=>'Token CSRF inválido'], 403);
    }
}

function ba_event(string $message, string $level='info', array $meta=[]): void {
    if (function_exists('ec_log')) {
        ec_log('bepinex', $message, $level, $meta);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

try {
    switch ($action) {
        case 'status':
            $installed = bm_installed_info();
            $latest = null;
            $remoteError = null;
            try {
                $latest = bm_latest_package();
            } catch (Throwable $e) {
                $remoteError = $e->getMessage();
            }

            $comparison = 'unknown';
            if ($latest) {
                $comparison = bm_version_compare($installed['package_version'], $latest['version']);
            }

            ba_out([
                'ok'=>true,
                'installed'=>$installed,
                'latest'=>$latest,
                'comparison'=>$comparison,
                'remote_error'=>$remoteError,
                'server_running'=>bm_server_running(),
                'requirements'=>[
                    'ziparchive'=>class_exists('ZipArchive'),
                    'curl'=>function_exists('curl_init'),
                    'allow_url_fopen'=>(bool)ini_get('allow_url_fopen'),
                ],
            ]);

        case 'install':
            ba_csrf();
            $force = !empty($_POST['force']);
            $result = bm_install_latest($force);
            $version = $result['latest']['version'] ?? null;
            ba_event(
                ($result['changed'] ? 'BepInExPack instalado/actualizado' : 'BepInExPack verificado') . ($version ? ' v' . $version : ''),
                $result['changed'] ? 'success' : 'info',
                ['version'=>$version,'backup'=>$result['backup'] ?? null]
            );
            ba_out(['ok'=>true] + $result);

        default:
            ba_out(['ok'=>false,'error'=>'Acción inválida'], 400);
    }
} catch (Throwable $e) {
    ba_event('Error BepInEx Manager: ' . $e->getMessage(), 'error');
    ba_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
