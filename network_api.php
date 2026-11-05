<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/lib/server_params.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);header('Content-Type: application/json');echo json_encode(['ok'=>false,'error'=>'Acceso denegado']);exit;}
function net_out(array $p,int $s=200):never{http_response_code($s);header('Content-Type: application/json; charset=utf-8');echo json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function net_csrf():void{$t=$_POST['csrf']??'';if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],(string)$t))net_out(['ok'=>false,'error'=>'CSRF inválido'],403);}
function net_firewall_info():array{
 $exe=defined('SERVER_PATH')?SERVER_PATH:'';
 $out=['windows'=>PHP_OS_FAMILY==='Windows','rule_found'=>null,'details'=>null];
 if(PHP_OS_FAMILY!=='Windows'||$exe==='')return $out;
 $safe=str_replace("'","''",$exe);
 $ps = '$filters = Get-NetFirewallApplicationFilter -ErrorAction SilentlyContinue | Where-Object { $_.Program -eq \'' . $safe . '\' }; if ($filters) { Write-Output \'FOUND\' }';
 $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($ps);
 $r=trim((string)@shell_exec($cmd));
 $out['rule_found']=$r==='FOUND';
 return $out;
}
$action=$_GET['action']??$_POST['action']??'status';
try{
 if($action==='status'){
   $port=(int)(sp_get_arg('port')??(defined('SERVER_PORT')?SERVER_PORT:2456));
   $cross=sp_has_flag('crossplay');$public=(string)(sp_get_arg('public')??'1');$instance=sp_get_arg('instanceid');
   net_out(['ok'=>true,'port'=>$port,'port_plus_one'=>$port+1,'crossplay'=>$cross,'public'=>$public==='1','instanceid'=>$instance,'backend'=>$cross?'PlayFab / Crossplay':'Steam','firewall'=>net_firewall_info()]);
 }
 if($action==='save'){
   net_csrf();$port=(int)($_POST['port']??2456);if($port<1024||$port>65534)throw new RuntimeException('Puerto inválido');
   $cross=!empty($_POST['crossplay']);$public=!empty($_POST['public']);$instance=trim((string)($_POST['instanceid']??''));
   sp_set_arg('port',(string)$port,false);sp_set_arg('public',$public?'1':'0',false);sp_set_flag('crossplay',$cross);sp_set_arg('instanceid',$instance!==''?$instance:null,true);
   net_out(['ok'=>true,'msg'=>'Configuración de red guardada','ports'=>[$port,$port+1]]);
 }
 net_out(['ok'=>false,'error'=>'Acción inválida'],400);
}catch(Throwable $e){net_out(['ok'=>false,'error'=>$e->getMessage()],500);}
