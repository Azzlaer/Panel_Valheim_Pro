<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { http_response_code(403); exit('Acceso denegado'); }

function hcheck($label,$ok,$detail='',$level='critical'){ return ['label'=>$label,'ok'=>(bool)$ok,'detail'=>$detail,'level'=>$level]; }
$checks=[];
$checks[]=hcheck('PHP 8.1 o superior',version_compare(PHP_VERSION,'8.1.0','>='),'PHP '.PHP_VERSION);
$checks[]=hcheck('shell_exec disponible',function_exists('shell_exec') && stripos((string)ini_get('disable_functions'),'shell_exec')===false,'Necesario para PowerShell/SteamCMD');
$checks[]=hcheck('ZipArchive',class_exists('ZipArchive'),class_exists('ZipArchive')?'Disponible':'Activa extension=zip en php.ini');
$checks[]=hcheck('PowerShell',is_file('C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe'),'Windows PowerShell');
$checks[]=hcheck('Servidor Valheim',defined('SERVER_PATH') && is_file(SERVER_PATH),defined('SERVER_PATH')?SERVER_PATH:'SERVER_PATH no definido');
$checks[]=hcheck('SteamCMD',defined('STEAMCMD_EXE') && is_file(STEAMCMD_EXE),defined('STEAMCMD_EXE')?STEAMCMD_EXE:'STEAMCMD_EXE no definido','warning');
$checks[]=hcheck('worlds_local',defined('WORLDS_DIR') && is_dir(WORLDS_DIR),defined('WORLDS_DIR')?WORLDS_DIR:'WORLDS_DIR no definido');
$checks[]=hcheck('Backups escribible',is_dir(__DIR__.'/../backups') ? is_writable(__DIR__.'/../backups') : is_writable(__DIR__.'/..'),__DIR__.'/../backups','warning');
$checks[]=hcheck('BepInEx plugins',defined('PLUGINS_DIR') && is_dir(PLUGINS_DIR),defined('PLUGINS_DIR')?PLUGINS_DIR:'PLUGINS_DIR no definido','warning');
$checks[]=hcheck('BepInEx config',defined('CFG_DIR') && is_dir(CFG_DIR),defined('CFG_DIR')?CFG_DIR:'CFG_DIR no definido','warning');
$rconCfg = defined('CFG_DIR') ? rtrim(CFG_DIR,'\\/').DIRECTORY_SEPARATOR.'org.tristan.rcon.cfg' : '';
$checks[]=hcheck('ValheimRcon config',$rconCfg && is_file($rconCfg),$rconCfg?:'CFG_DIR no definido','warning');
$rconOnline=false;
if(defined('RCON_HOST')&&defined('RCON_PORT')){$fp=@fsockopen(RCON_HOST,(int)RCON_PORT,$errno,$errstr,0.7);if($fp){$rconOnline=true;fclose($fp);}}
$checks[]=hcheck('Puerto RCON',$rconOnline,defined('RCON_HOST')&&defined('RCON_PORT')?RCON_HOST.':'.RCON_PORT:'RCON no configurado','warning');
$pidFile=__DIR__.'/../server.pid';
$pid=is_file($pidFile)?intval(trim((string)@file_get_contents($pidFile))):0;
$pidOk=false;
if($pid>0){$o=@shell_exec('tasklist /FI "PID eq '.$pid.'" /FO CSV /NH');$pidOk=is_string($o)&&stripos($o,'valheim_server.exe')!==false;}
$checks[]=hcheck('PID registrado',$pidOk,$pid>0?'PID '.$pid:'server.pid vacío/no existe','warning');
$okCount=count(array_filter($checks,fn($x)=>$x['ok']));$score=(int)round(($okCount/max(1,count($checks)))*100);
?>
<style>
.health-wrap .hero{background:linear-gradient(135deg,rgba(16,185,129,.14),rgba(17,24,39,.7));border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:24px;margin-bottom:22px}.health-wrap h2{font-weight:800;color:#fff}.health-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.health-item{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:16px}.health-label{font-weight:800;color:#fff}.health-detail{font-size:12px;color:#9ca3af;margin-top:5px;word-break:break-word}.health-score{font-size:42px;font-weight:900;color:#fff}@media(max-width:900px){.health-grid{grid-template-columns:1fr}}
</style>
<div class="container-fluid mt-4 health-wrap">
 <div class="hero d-flex justify-content-between align-items-center gap-3 flex-wrap"><div><h2>🩺 Health Center</h2><p class="text-secondary mb-0">Diagnóstico rápido del host, PHP y servicios necesarios para administrar Valheim.</p></div><div class="text-end"><div class="health-score"><?= $score ?>%</div><small class="text-secondary"><?= $okCount ?>/<?= count($checks) ?> comprobaciones OK</small></div></div>
 <div class="health-grid">
 <?php foreach($checks as $c): ?><div class="health-item"><div class="d-flex justify-content-between gap-3"><div><div class="health-label"><?= htmlspecialchars($c['label']) ?></div><div class="health-detail"><?= htmlspecialchars($c['detail']) ?></div></div><div><?= $c['ok']?'<span class="badge bg-success">✅ OK</span>':'<span class="badge bg-danger">❌ Revisar</span>' ?></div></div></div><?php endforeach; ?>
 </div>
</div>
