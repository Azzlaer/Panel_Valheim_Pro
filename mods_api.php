<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/lib/thunderstore_manager.php';
if(is_file(__DIR__.'/lib/event_center.php'))require_once __DIR__.'/lib/event_center.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);header('Content-Type: application/json');echo json_encode(['ok'=>false,'error'=>'Acceso denegado']);exit;}
function tm_out(array $p,int $s=200):never{http_response_code($s);header('Content-Type: application/json; charset=utf-8');echo json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function tm_csrf():void{$t=$_POST['csrf']??'';if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],(string)$t))tm_out(['ok'=>false,'error'=>'CSRF inválido'],403);}
function tm_evt(string $m,string $l='info',array $x=[]):void{if(function_exists('ec_log'))ec_log('mods',$m,$l,$x);}
$a=$_GET['action']??$_POST['action']??'installed';
try{
 if($a==='search'){tm_out(['ok'=>true,'items'=>tm_search((string)($_GET['q']??''),40)]);}
 if($a==='installed'){tm_out(['ok'=>true,'items'=>tm_installed_with_updates(),'bepinex'=>bm_installed_info(),'server_running'=>bm_server_running()]);}
 if($a==='refresh_index'){tm_csrf();$d=tm_package_index(true);tm_out(['ok'=>true,'packages'=>count($d)]);}
 if($a==='install'){
   tm_csrf();$ns=trim((string)($_POST['namespace']??''));$name=trim((string)($_POST['name']??''));$version=trim((string)($_POST['version']??''));$seen=[];$r=tm_install($ns,$name,$version!==''?$version:null,$seen);tm_evt('Mod instalado/actualizado: '.$ns.'/'.$name,'success',['version'=>$r['version']??$version]);tm_out(['ok'=>true,'item'=>$r]);
 }
 if($a==='uninstall'){
   tm_csrf();$ns=trim((string)($_POST['namespace']??''));$name=trim((string)($_POST['name']??''));$r=tm_uninstall($ns,$name);tm_evt('Mod desinstalado: '.$ns.'/'.$name,'warning',$r);tm_out(['ok'=>true]+$r);
 }
 tm_out(['ok'=>false,'error'=>'Acción inválida'],400);
}catch(Throwable $e){tm_evt('Thunderstore error: '.$e->getMessage(),'error');tm_out(['ok'=>false,'error'=>$e->getMessage()],500);}
