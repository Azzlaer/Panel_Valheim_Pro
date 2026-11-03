<?php
$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) require_once $configPath;
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$installed = defined('PANEL_INSTALLED') ? PANEL_INSTALLED : false;
$allowReinstall = defined('ALLOW_REINSTALL') ? ALLOW_REINSTALL : false;
if ($installed && !$allowReinstall) {
    http_response_code(403);
    exit('El panel ya está instalado. Habilita ALLOW_REINSTALL temporalmente para volver a ejecutar el asistente.');
}

function cfgv(string $name, $default='') { return defined($name) ? constant($name) : $default; }
function detect_first(array $paths): string { foreach($paths as $p){ if(is_file($p) || is_dir($p)) return $p; } return ''; }
function escv($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$detectedSteam = detect_first([
    'C:\\Servidores\\Steam\\steamcmd.exe',
    'C:\\SteamCMD\\steamcmd.exe',
    'D:\\Servidores\\Steam\\steamcmd.exe'
]);
$detectedValheim = detect_first([
    'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\valheim_server.exe',
    'C:\\SteamCMD\\steamapps\\common\\Valheim dedicated server\\valheim_server.exe',
    'D:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\valheim_server.exe'
]);

$defaults = [
 'ADMIN_USER'=>cfgv('ADMIN_USER','admin'),'SERVER_IP'=>cfgv('SERVER_IP','0.0.0.0'),'SERVER_PORT'=>cfgv('SERVER_PORT',2456),
 'SERVER_NAME'=>cfgv('SERVER_NAME','Servidor Valheim'),'SERVER_PATH'=>cfgv('SERVER_PATH',$detectedValheim),
 'SERVER_BASE'=>cfgv('SERVER_BASE',$detectedValheim?dirname($detectedValheim).DIRECTORY_SEPARATOR:''),
 'SERVER_DIR'=>cfgv('SERVER_DIR',$detectedValheim?dirname($detectedValheim).'\\server1\\':''),
 'WORLDS_DIR'=>cfgv('WORLDS_DIR',$detectedValheim?dirname($detectedValheim).'\\server1\\worlds_local':''),
 'PLUGINS_DIR'=>cfgv('PLUGINS_DIR',$detectedValheim?dirname($detectedValheim).'\\BepInEx\\plugins':''),
 'CFG_DIR'=>cfgv('CFG_DIR',$detectedValheim?dirname($detectedValheim).'\\BepInEx\\config':''),
 'SERVER_LOG'=>cfgv('SERVER_LOG',$detectedValheim?dirname($detectedValheim).'\\server1\\server1.txt':''),
 'STEAMCMD_EXE'=>cfgv('STEAMCMD_EXE',$detectedSteam),'STEAMCMD_PATH'=>cfgv('STEAMCMD_PATH',$detectedSteam?dirname($detectedSteam):''),
 'STEAMCMD_LOG'=>cfgv('STEAMCMD_LOG',$detectedSteam?dirname($detectedSteam).'\\logs\\console_log.txt':''),
 'RCON_HOST'=>cfgv('RCON_HOST','127.0.0.1'),'RCON_PORT'=>cfgv('RCON_PORT',2974),'RCON_PASS'=>cfgv('RCON_PASS',''),
 'LOG_MAX_LINES'=>cfgv('LOG_MAX_LINES',100),'SERVER_STATUS_MODE'=>cfgv('SERVER_STATUS_MODE','none')
];

$errors=[];$success='';
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['install_action']??'')==='finish') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf']??'')) $errors[]='Token CSRF inválido.';
    $admin=trim($_POST['ADMIN_USER']??'');$pass=(string)($_POST['ADMIN_PASSWORD']??'');$serverPath=trim($_POST['SERVER_PATH']??'');
    if($admin==='')$errors[]='Debes indicar un usuario administrador.';
    if(strlen($pass)<8)$errors[]='La contraseña debe tener al menos 8 caracteres.';
    if($serverPath==='' || !is_file($serverPath))$errors[]='No se encontró valheim_server.exe en la ruta indicada.';
    $steam=trim($_POST['STEAMCMD_EXE']??''); if($steam!=='' && !is_file($steam))$errors[]='La ruta de SteamCMD no es válida.';

    if(!$errors){
        $v=[];foreach(array_keys($defaults) as $k)$v[$k]=trim((string)($_POST[$k]??$defaults[$k]));
        $hash=password_hash($pass,PASSWORD_DEFAULT);
        $php="<?php\n// Generado por Valheim Enterprise Installer V2\n";
        $php.="define('PANEL_INSTALLED', true);\ndefine('ALLOW_REINSTALL', false);\n";
        $php.="define('ADMIN_USER', ".var_export($admin,true).");\ndefine('ADMIN_PASS_HASH', ".var_export($hash,true).");\ndefine('ADMIN_PASS', ''); // Compatibilidad legacy\n";
        foreach($v as $k=>$val){
            if(in_array($k,['ADMIN_USER'],true))continue;
            if(in_array($k,['SERVER_PORT','RCON_PORT','LOG_MAX_LINES'],true))$php.="define('$k', ".intval($val).");\n";
            else $php.="define('$k', ".var_export($val,true).");\n";
        }
        $php.="define('SERVERS_JSON', __DIR__ . DIRECTORY_SEPARATOR . 'servers.json');\n";
        $php.="\$LISTS = ['adminlist.txt'=>'👑 Administradores','bannedlist.txt'=>'🚫 Baneados','permittedlist.txt'=>'✅ Permitidos'];\n";
        $php.="if (session_status() === PHP_SESSION_NONE) session_start();\nif (empty(\$_SESSION['csrf_token'])) \$_SESSION['csrf_token']=bin2hex(random_bytes(32));\n";
        if(@file_put_contents($configPath,$php,LOCK_EX)!==false){
            @mkdir(__DIR__.'/backups',0775,true);@mkdir(__DIR__.'/data',0775,true);
            $success='Instalación completada. Ya puedes entrar al panel.';
        } else $errors[]='No se pudo escribir config.php. Revisa permisos de Apache.';
    }
}

$requirements=[
 ['PHP 8.1+',version_compare(PHP_VERSION,'8.1.0','>=')],
 ['shell_exec',function_exists('shell_exec') && stripos((string)ini_get('disable_functions'),'shell_exec')===false],
 ['ZipArchive',class_exists('ZipArchive')],
 ['PowerShell',is_file('C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe')],
 ['config.php escribible',is_writable($configPath) || (!file_exists($configPath)&&is_writable(__DIR__))]
];
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Valheim Enterprise Installer V2</title>
<style>
:root{--bg:#08111f;--card:#111827;--soft:#94a3b8;--line:rgba(255,255,255,.08);--blue:#3b82f6;--green:#10b981;--red:#ef4444}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at top left,rgba(59,130,246,.16),transparent 25%),linear-gradient(135deg,#07101c,#0b1220 50%,#111827);color:#e5e7eb;font-family:Segoe UI,Arial,sans-serif;min-height:100vh}.shell{max-width:1180px;margin:auto;padding:28px}.brand{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px}.brand h1{margin:0;font-size:30px}.brand p{margin:6px 0 0;color:var(--soft)}.version{border:1px solid var(--line);background:rgba(255,255,255,.03);padding:9px 13px;border-radius:999px;color:#bfdbfe}.wizard{display:grid;grid-template-columns:250px 1fr;background:rgba(10,18,32,.88);border:1px solid var(--line);border-radius:24px;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,.4)}.steps{padding:24px;background:rgba(15,23,42,.9);border-right:1px solid var(--line)}.stepnav{padding:12px;border-radius:12px;color:var(--soft);font-weight:700;margin-bottom:8px}.stepnav.active{background:linear-gradient(135deg,var(--blue),#1d4ed8);color:white}.content{padding:28px}.step{display:none}.step.active{display:block}.step h2{margin:0 0 8px}.hint{color:var(--soft);margin-bottom:22px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.full{grid-column:1/-1}label{display:block;font-size:13px;color:#cbd5e1;font-weight:700;margin-bottom:6px}input,select{width:100%;background:#08111f;color:white;border:1px solid var(--line);border-radius:12px;padding:12px}input:focus,select:focus{outline:none;border-color:#60a5fa}.req{display:flex;justify-content:space-between;gap:12px;padding:13px 14px;background:#0b1220;border:1px solid var(--line);border-radius:12px;margin-bottom:9px}.ok{color:#6ee7b7}.bad{color:#fca5a5}.actions{display:flex;justify-content:space-between;gap:10px;margin-top:26px;padding-top:20px;border-top:1px solid var(--line)}button,.btn{border:0;border-radius:12px;padding:11px 18px;font-weight:800;cursor:pointer;text-decoration:none}.primary{background:var(--blue);color:white}.secondary{background:#334155;color:white}.success{background:var(--green);color:white}.alert{padding:13px 15px;border-radius:12px;margin-bottom:16px}.alertbad{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3)}.alertok{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3)}.summary{background:#0b1220;border:1px solid var(--line);border-radius:14px;padding:16px}.summary code{color:#93c5fd}@media(max-width:850px){.wizard{grid-template-columns:1fr}.steps{display:none}.grid{grid-template-columns:1fr}.full{grid-column:auto}}
</style></head><body><div class="shell"><div class="brand"><div><h1>⚔️ Valheim Enterprise Installer</h1><p>Asistente de instalación y diagnóstico para Windows + XAMPP.</p></div><div class="version">V2 · Gaming Panel</div></div>
<?php if($errors):?><div class="alert alertbad"><?php foreach($errors as $e):?><div>❌ <?=escv($e)?></div><?php endforeach;?></div><?php endif;?>
<?php if($success):?><div class="alert alertok">✅ <?=escv($success)?> &nbsp; <a class="btn success" href="index.php">Entrar al panel</a></div><?php endif;?>
<div class="wizard"><aside class="steps"><?php foreach(['Bienvenida','Requisitos','Administrador','Servidor Valheim','SteamCMD / RCON','Rutas y módulos','Finalizar'] as $i=>$n):?><div class="stepnav <?=$i===0?'active':''?>" data-nav="<?=$i?>"><?=($i+1)?>. <?=escv($n)?></div><?php endforeach;?></aside><main class="content"><form method="post" id="wizardForm"><input type="hidden" name="csrf" value="<?=escv($_SESSION['csrf_token'])?>"><input type="hidden" name="install_action" value="finish">
<section class="step active"><h2>👋 Bienvenido</h2><p class="hint">Instalaremos el panel sin que tengas que editar rutas manualmente después.</p><div class="summary"><strong>Modo recomendado:</strong> instalación guiada. El asistente detectará componentes comunes y validará las rutas antes de guardar.</div><div class="summary" style="margin-top:14px"><strong>¿Vienes de una versión anterior?</strong><br><span style="color:#94a3b8">Puedes importar tu <code>config.php</code> antiguo, recuperar rutas y parámetros, y completar solo lo que la versión nueva necesite.</span><br><br><a class="btn primary" href="migration.php">🧭 Importar configuración antigua</a></div></section>
<section class="step"><h2>🩺 Requisitos del sistema</h2><p class="hint">Estas comprobaciones previenen la mayoría de errores del panel.</p><?php foreach($requirements as [$n,$ok]):?><div class="req"><span><?=escv($n)?></span><strong class="<?=$ok?'ok':'bad'?>"><?=$ok?'✅ Correcto':'❌ Revisar'?></strong></div><?php endforeach;?></section>
<section class="step"><h2>🔐 Administrador</h2><p class="hint">La contraseña se almacenará mediante <code>password_hash()</code>.</p><div class="grid"><div><label>Usuario administrador</label><input name="ADMIN_USER" value="<?=escv($defaults['ADMIN_USER'])?>" required></div><div><label>Contraseña (mín. 8 caracteres)</label><input type="password" name="ADMIN_PASSWORD" minlength="8" required autocomplete="new-password"></div></div></section>
<section class="step"><h2>🎮 Servidor Valheim</h2><p class="hint">Indica el ejecutable y la identidad principal del servidor.</p><div class="grid"><div class="full"><label>Ruta a valheim_server.exe</label><input name="SERVER_PATH" value="<?=escv($defaults['SERVER_PATH'])?>" required></div><div><label>Nombre del servidor</label><input name="SERVER_NAME" value="<?=escv($defaults['SERVER_NAME'])?>"></div><div><label>Puerto</label><input type="number" name="SERVER_PORT" value="<?=escv($defaults['SERVER_PORT'])?>"></div><div><label>IP</label><input name="SERVER_IP" value="<?=escv($defaults['SERVER_IP'])?>"></div><div><label>SERVER_BASE</label><input name="SERVER_BASE" value="<?=escv($defaults['SERVER_BASE'])?>"></div></div></section>
<section class="step"><h2>🔄 SteamCMD y RCON</h2><p class="hint">Puedes dejar RCON vacío si todavía no lo utilizas.</p><div class="grid"><div class="full"><label>steamcmd.exe</label><input name="STEAMCMD_EXE" value="<?=escv($defaults['STEAMCMD_EXE'])?>"></div><div><label>Carpeta SteamCMD</label><input name="STEAMCMD_PATH" value="<?=escv($defaults['STEAMCMD_PATH'])?>"></div><div><label>Log SteamCMD</label><input name="STEAMCMD_LOG" value="<?=escv($defaults['STEAMCMD_LOG'])?>"></div><div><label>RCON Host</label><input name="RCON_HOST" value="<?=escv($defaults['RCON_HOST'])?>"></div><div><label>RCON Puerto</label><input type="number" name="RCON_PORT" value="<?=escv($defaults['RCON_PORT'])?>"></div><div class="full"><label>RCON Password</label><input type="password" name="RCON_PASS" value="<?=escv($defaults['RCON_PASS'])?>"></div></div></section>
<section class="step"><h2>📂 Rutas y módulos</h2><p class="hint">Estas carpetas alimentan Mapas, Backups, Plugins, CFG, Logs y Listas.</p><div class="grid"><div><label>SERVER_DIR</label><input name="SERVER_DIR" value="<?=escv($defaults['SERVER_DIR'])?>"></div><div><label>WORLDS_DIR</label><input name="WORLDS_DIR" value="<?=escv($defaults['WORLDS_DIR'])?>"></div><div><label>PLUGINS_DIR</label><input name="PLUGINS_DIR" value="<?=escv($defaults['PLUGINS_DIR'])?>"></div><div><label>CFG_DIR</label><input name="CFG_DIR" value="<?=escv($defaults['CFG_DIR'])?>"></div><div class="full"><label>SERVER_LOG</label><input name="SERVER_LOG" value="<?=escv($defaults['SERVER_LOG'])?>"></div><div><label>Máximo líneas de log</label><input type="number" name="LOG_MAX_LINES" value="<?=escv($defaults['LOG_MAX_LINES'])?>"></div><div><label>Modo de estado</label><select name="SERVER_STATUS_MODE"><option value="none">PID / proceso</option><option value="port">Puerto</option><option value="folder">Carpeta</option></select></div></div></section>
<section class="step"><h2>🚀 Listo para instalar</h2><p class="hint">Se escribirá <code>config.php</code>, se activará el login seguro y el instalador quedará bloqueado.</p><div class="summary">Después podrás usar <strong>🩺 Health Center</strong> para comprobar PHP, PowerShell, SteamCMD, Valheim, BepInEx, backups y PID.</div></section>
<div class="actions"><button type="button" class="secondary" id="prevBtn">← Anterior</button><button type="button" class="primary" id="nextBtn">Siguiente →</button><button type="submit" class="success" id="finishBtn" style="display:none">✅ Instalar Panel</button></div></form></main></div></div>
<script>let step=0;const steps=[...document.querySelectorAll('.step')],nav=[...document.querySelectorAll('.stepnav')],prev=document.getElementById('prevBtn'),next=document.getElementById('nextBtn'),finish=document.getElementById('finishBtn');function show(){steps.forEach((x,i)=>x.classList.toggle('active',i===step));nav.forEach((x,i)=>x.classList.toggle('active',i===step));prev.style.visibility=step===0?'hidden':'visible';next.style.display=step===steps.length-1?'none':'inline-block';finish.style.display=step===steps.length-1?'inline-block':'none'}next.onclick=()=>{if(step<steps.length-1){step++;show()}};prev.onclick=()=>{if(step>0){step--;show()}};show();</script></body></html>
