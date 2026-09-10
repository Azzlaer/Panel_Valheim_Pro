<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/event_center.php';
header('Content-Type: application/json; charset=utf-8');
if (PHP_SAPI !== 'cli' && (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

function aw_backup(){
    if(!defined('WORLDS_DIR') || !is_dir(WORLDS_DIR)) return [false,'WORLDS_DIR no disponible'];
    if(!class_exists('ZipArchive')) return [false,'ZipArchive no disponible'];
    $dir=__DIR__.'/backups'; if(!is_dir($dir) && !@mkdir($dir,0777,true)) return [false,'No se pudo crear backups'];
    $file='auto_worlds_'.date('Ymd_His').'.zip'; $path=$dir.'/'.$file; $zip=new ZipArchive();
    if($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) return [false,'No se pudo crear ZIP'];
    $src=realpath(WORLDS_DIR); $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src,FilesystemIterator::SKIP_DOTS)); $count=0;
    foreach($it as $f){ if($f->isFile()){ $rp=$f->getRealPath(); $rel=substr($rp,strlen($src)+1); if($zip->addFile($rp,$rel)) $count++; }}
    $zip->close(); return [true,"Backup $file creado ($count archivos)"];
}
function aw_steam_update($beta=false){
    if(!defined('STEAMCMD_EXE') || !is_file(STEAMCMD_EXE)) return [false,'SteamCMD no encontrado'];
    $cmd='"'.STEAMCMD_EXE.'" +login anonymous +app_update 896660 '.($beta?'-beta public-test -betapassword yesimadebackups ':'').'validate +quit';
    @pclose(@popen('start "ValheimAutoUpdate" cmd /c '.$cmd,'r')); return [true,'Actualización SteamCMD lanzada'];
}
function aw_restart($serverId=1,$save=true){
    if(!defined('RCON_HOST')||!defined('RCON_PORT')||!defined('RCON_PASS')) return [false,'RCON no configurado'];
    $py=PHP_OS_FAMILY==='Windows'?'python':'python3'; $script=__DIR__.'/rcon_restart.py'; if(!is_file($script)) return [false,'rcon_restart.py no encontrado'];
    $exe=defined('SERVER_PATH')?basename(SERVER_PATH):'valheim_server.exe';
    $args=['--host',RCON_HOST,'--port',(string)RCON_PORT,'--password',RCON_PASS,'--delay','0','--servers-json',SERVERS_JSON,'--server-id',(string)$serverId,'--exe-name',$exe,'--save-before',$save?'1':'0'];
    $parts=[]; foreach($args as $a)$parts[]=escapeshellarg($a); @pclose(@popen('start "VH Auto Restart" cmd /c '.escapeshellarg($py).' '.escapeshellarg($script).' '.implode(' ',$parts),'r'));
    return [true,'Reinicio programado lanzado'];
}
function aw_rcon($cmd){
    $script=__DIR__.'/rcon_alert.py'; if(!is_file($script)) return [false,'rcon_alert.py no encontrado'];
    $py=PHP_OS_FAMILY==='Windows'?'python':'python3';
    $args=['--host',RCON_HOST,'--port',(string)RCON_PORT,'--password',RCON_PASS,'--command',$cmd,'--interval','1','--repeats','1'];
    $parts=[]; foreach($args as $a)$parts[]=escapeshellarg($a); @pclose(@popen('start "VH Auto RCON" cmd /c '.escapeshellarg($py).' '.escapeshellarg($script).' '.implode(' ',$parts),'r'));
    return [true,'Mensaje RCON lanzado'];
}

$tasks=ec_tasks(); $now=time(); $executed=[]; $changed=false;
foreach($tasks as &$t){
    if(empty($t['enabled']) || intval($t['next_run_ts']??0)>$now) continue;
    $action=$t['action']??''; $ok=false; $msg='Acción no soportada';
    if($action==='backup') [$ok,$msg]=aw_backup();
    elseif($action==='steam_update') [$ok,$msg]=aw_steam_update(!empty($t['payload']['beta']));
    elseif($action==='restart') [$ok,$msg]=aw_restart(intval($t['payload']['server_id']??1),!empty($t['payload']['save_before']));
    elseif($action==='rcon_message') [$ok,$msg]=aw_rcon((string)($t['payload']['command']??''));
    $t['last_run_ts']=$now; $t['last_result']=$msg; $t['last_ok']=$ok; $t['next_run_ts']=ec_next_run($t['schedule']??[],$now); $changed=true;
    ec_log('automation',$msg,$ok?'success':'error',['task_id'=>$t['id']??'','action'=>$action]); $executed[]=['id'=>$t['id']??'','ok'=>$ok,'msg'=>$msg];
}
unset($t); if($changed) ec_save_tasks($tasks);
echo json_encode(['ok'=>true,'executed'=>$executed,'count'=>count($executed)],JSON_UNESCAPED_UNICODE);
