<?php
require_once __DIR__ . "/../config.php";
header('Content-Type: application/json');

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['ok'=>false,'error'=>'Acceso denegado']); exit;
}
$action = $_GET['action'] ?? '';
$file   = basename($_GET['file'] ?? '');
$path   = PLUGINS_DIR . DIRECTORY_SEPARATOR . $file;

switch($action){
    case 'delete':
        if(is_file($path) && unlink($path)){
            echo json_encode(['ok'=>true]); exit;
        }
        echo json_encode(['ok'=>false,'error'=>'No se pudo eliminar']); exit;
    case 'disable':
        if(is_file($path)){
            $new = $path . '.disable';
            if(rename($path, $new)){ echo json_encode(['ok'=>true]); exit; }
        }
        echo json_encode(['ok'=>false,'error'=>'No se pudo deshabilitar']); exit;
    case 'enable':
        if(str_ends_with($file,'.disable')){
            $new = PLUGINS_DIR . DIRECTORY_SEPARATOR . substr($file,0,-8);
            if(rename($path, $new)){ echo json_encode(['ok'=>true]); exit; }
        }
        echo json_encode(['ok'=>false,'error'=>'No se pudo habilitar']); exit;
    default:
        echo json_encode(['ok'=>false,'error'=>'Acción inválida']); exit;
}
