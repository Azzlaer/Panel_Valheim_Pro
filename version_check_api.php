<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/version_checker.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'Acceso denegado'], JSON_UNESCAPED_UNICODE);
    exit;
}

function vc_api_out(array $payload, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'local';

try {
    if ($action === 'local') {
        vc_api_out([
            'ok'=>true,
            'local'=>vc_local_info(),
        ]);
    }

    if ($action === 'check') {
        $token = (string)($_POST['csrf'] ?? '');
        if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
            vc_api_out(['ok'=>false,'error'=>'CSRF inválido'], 403);
        }

        $local = vc_local_info();
        $steam = vc_remote_steam($local['branch'] ?? 'public');
        $iron = vc_irongate_latest();
        $compare = vc_compare($local['buildid'] ?? null, $steam['buildid'] ?? null, $steam['branch'] ?? ($local['branch'] ?? null));

        vc_api_out([
            'ok'=>true,
            'local'=>$local,
            'steam'=>$steam,
            'irongate'=>$iron,
            'comparison'=>$compare,
            'checked_at'=>date('c'),
        ]);
    }

    vc_api_out(['ok'=>false,'error'=>'Acción inválida'], 400);
} catch (Throwable $e) {
    vc_api_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
