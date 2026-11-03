<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/backup_restore.php';
if (is_file(__DIR__.'/lib/event_center.php')) require_once __DIR__.'/lib/event_center.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'Acceso denegado'], JSON_UNESCAPED_UNICODE);
    exit;
}
function bv3_out(array $p,int $s=200): never {http_response_code($s);header('Content-Type: application/json; charset=utf-8');echo json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function bv3_csrf():void{$t=$_POST['csrf']??'';if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],(string)$t))bv3_out(['ok'=>false,'error'=>'CSRF inválido'],403);}
function bv3_log(string $m,string $l='info',array $meta=[]):void{if(function_exists('ec_log'))ec_log('backup',$m,$l,$meta);}
$action=$_GET['action']??$_POST['action']??'list';
try{
 switch($action){
  case 'list':
   bv3_out(['ok'=>true,'items'=>br_list(),'worlds'=>wm_scan()['worlds'],'server_running'=>wm_server_running()]);
  case 'create':
   bv3_csrf();$world=trim((string)($_POST['world']??''));$r=br_create_world_backup($world,'manual');bv3_log('Backup creado: '.$r['name'],'success',['world'=>$world]);bv3_out(['ok'=>true]+$r);
  case 'restore':
   bv3_csrf();$name=basename((string)($_POST['name']??''));$r=br_restore($name);bv3_log('Mundo restaurado desde '.$name,'warning',$r);bv3_out(['ok'=>true]+$r);
  case 'delete':
   bv3_csrf();$name=basename((string)($_POST['name']??''));$path=br_backup_root().DIRECTORY_SEPARATOR.$name;if(!is_file($path))throw new RuntimeException('Backup no encontrado');@unlink(br_manifest_path($path));if(!@unlink($path))throw new RuntimeException('No se pudo eliminar');bv3_log('Backup eliminado: '.$name,'warning');bv3_out(['ok'=>true]);
  case 'download':
   $name=basename((string)($_GET['name']??''));$path=br_backup_root().DIRECTORY_SEPARATOR.$name;if(!is_file($path))throw new RuntimeException('Backup no encontrado');header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="'.$name.'"');header('Content-Length: '.filesize($path));readfile($path);exit;
  default:bv3_out(['ok'=>false,'error'=>'Acción inválida'],400);
 }
}catch(Throwable $e){bv3_out(['ok'=>false,'error'=>$e->getMessage()],500);}
