<?php
require_once __DIR__.'/config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);header('Content-Type: application/json');echo json_encode(['ok'=>false,'error'=>'Acceso denegado']);exit;}
function pv_out(array $p,int $s=200):never{http_response_code($s);header('Content-Type: application/json; charset=utf-8');echo json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function pv_csrf():void{$t=$_POST['csrf']??'';if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],(string)$t))pv_out(['ok'=>false,'error'=>'CSRF inválido'],403);}
function pv_dir():string{if(!defined('SERVER_DIR'))throw new RuntimeException('SERVER_DIR no definido');if(!is_dir(SERVER_DIR)&&!@mkdir(SERVER_DIR,0775,true))throw new RuntimeException('SERVER_DIR inválido');return rtrim(SERVER_DIR,'\\/');}
function pv_files():array{return ['adminlist.txt'=>'Admins','bannedlist.txt'=>'Baneados','permittedlist.txt'=>'Permitidos'];}
function pv_path(string $f):string{if(!isset(pv_files()[$f]))throw new RuntimeException('Lista inválida');return pv_dir().DIRECTORY_SEPARATOR.$f;}
function pv_read(string $f):array{$p=pv_path($f);if(!is_file($p))@file_put_contents($p,'');$rows=file($p,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[];return array_values(array_filter(array_map('trim',$rows),fn($x)=>$x!==''));}
function pv_write(string $f,array $rows):void{$p=pv_path($f);$txt=$rows?implode(PHP_EOL,$rows).PHP_EOL:'';if(@file_put_contents($p,$txt,LOCK_EX)===false)throw new RuntimeException('No se pudo guardar la lista');}
function pv_valid_id(string $id):bool{return (bool)preg_match('/^[A-Za-z0-9]+_[A-Za-z0-9._:-]+$/',$id);}
$action=$_GET['action']??$_POST['action']??'status';
try{
 if($action==='status'){
   $d=[];foreach(pv_files() as $f=>$label)$d[$f]=pv_read($f);
   pv_out(['ok'=>true,'lists'=>$d,'whitelist_active'=>count($d['permittedlist.txt'])>0]);
 }
 if($action==='add'){
   pv_csrf();$f=(string)($_POST['file']??'');$id=trim((string)($_POST['id']??''));if(!pv_valid_id($id))throw new RuntimeException('Platform User ID inválido. Usa formato [Platform]_[User ID].');
   $rows=pv_read($f);if(!in_array($id,$rows,true))$rows[]=$id;pv_write($f,$rows);pv_out(['ok'=>true]);
 }
 if($action==='delete'){
   pv_csrf();$f=(string)($_POST['file']??'');$id=trim((string)($_POST['id']??''));$rows=array_values(array_filter(pv_read($f),fn($x)=>$x!==$id));pv_write($f,$rows);pv_out(['ok'=>true]);
 }
 pv_out(['ok'=>false,'error'=>'Acción inválida'],400);
}catch(Throwable $e){pv_out(['ok'=>false,'error'=>$e->getMessage()],500);}
