<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/lib/server_params.php';
require_once __DIR__.'/lib/world_manager.php';
if (is_file(__DIR__.'/lib/event_center.php')) require_once __DIR__.'/lib/event_center.php';

if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'error'=>'Acceso denegado']);exit;}
function wc_out(array $p,int $s=200):never{http_response_code($s);header('Content-Type: application/json; charset=utf-8');echo json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function wc_csrf():void{$t=$_POST['csrf']??'';if(empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],(string)$t))wc_out(['ok'=>false,'error'=>'CSRF inválido'],403);}
$action=$_GET['action']??$_POST['action']??'status';
try{
 if($action==='status'){
   $scan=wm_scan();wc_out(['ok'=>true,'worlds'=>$scan['worlds'],'active'=>wm_active_world(),'server_running'=>wm_server_running()]);
 }
 if($action==='prepare'){
   wc_csrf();if(wm_server_running())throw new RuntimeException('Detén el servidor antes de preparar un mundo nuevo');
   $name=trim((string)($_POST['name']??''));if(!wm_safe_name($name))throw new RuntimeException('Nombre de mundo inválido');
   foreach(wm_scan()['worlds'] as $w){if(strcasecmp($w['name'],$name)===0)throw new RuntimeException('Ya existe un mundo con ese nombre');}
   $preset=strtolower(trim((string)($_POST['preset']??'normal')));
   $presets=['normal','casual','easy','hard','hardcore','immersive','hammer'];if(!in_array($preset,$presets,true))throw new RuntimeException('Preset inválido');
   sp_set_arg('world',$name,true);
   sp_set_arg('preset',$preset,false);

   // Un mundo nuevo no debe heredar modifiers/setkeys del mundo anterior.
   $servers=sp_servers();$params=(string)($servers[0]['params']??'');
   foreach(['combat','deathpenalty','resources','raids','portals'] as $m){
     $params=preg_replace('/(?:^|\s)-modifier\s+'.preg_quote($m,'/').'\s+\S+/i','',$params);
   }
   foreach(['nobuildcost','playerevents','passivemobs','nomap'] as $key){
     $params=preg_replace('/(?:^|\s)-setkey\s+'.preg_quote($key,'/').'(?=\s|$)/i','',$params);
   }
   $servers[0]['params']=trim(preg_replace('/\s+/',' ',$params));sp_save($servers);

   foreach(['combat','deathpenalty','resources','raids','portals'] as $m){
     $v=trim((string)($_POST[$m]??'')); if($v!==''){
       $servers=sp_servers();$params=(string)($servers[0]['params']??'');
       $params=trim($params).' -modifier '.$m.' '.$v;
       $servers[0]['params']=trim(preg_replace('/\s+/',' ',$params));sp_save($servers);
     }
   }
   foreach(['nobuildcost','playerevents','passivemobs','nomap'] as $key){
     if(!empty($_POST['key_'.$key])){
       $servers=sp_servers();$params=(string)($servers[0]['params']??'');
       $params=trim($params).' -setkey '.$key;
       $servers[0]['params']=trim(preg_replace('/\s+/',' ',$params));sp_save($servers);
     }
   }
   if(function_exists('ec_log'))ec_log('world','Mundo preparado: '.$name,'success',['preset'=>$preset]);
   wc_out(['ok'=>true,'msg'=>'Mundo preparado. Inicia el servidor para que Valheim genere su estructura.','world'=>$name]);
 }
 wc_out(['ok'=>false,'error'=>'Acción inválida'],400);
}catch(Throwable $e){wc_out(['ok'=>false,'error'=>$e->getMessage()],500);}
