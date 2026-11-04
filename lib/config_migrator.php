<?php
/**
 * Safe configuration migration helpers.
 * IMPORTANT: imported PHP is parsed as tokens and is never included/eval'ed.
 */

function cm_schema(): array {
    return [
        'ADMIN_USER' => ['type'=>'string','required'=>true,'label'=>'Usuario administrador'],
        'ADMIN_PASS_HASH' => ['type'=>'secret','required'=>false,'label'=>'Hash contraseña admin'],
        'SERVER_IP' => ['type'=>'string','required'=>true,'default'=>'0.0.0.0','label'=>'IP servidor'],
        'SERVER_PORT' => ['type'=>'int','required'=>true,'default'=>2456,'label'=>'Puerto Valheim'],
        'SERVER_NAME' => ['type'=>'string','required'=>true,'default'=>'Servidor Valheim','label'=>'Nombre servidor'],
        'SERVER_BASE' => ['type'=>'path','required'=>true,'label'=>'Directorio Valheim'],
        'SERVER_PATH' => ['type'=>'file','required'=>true,'label'=>'valheim_server.exe'],
        'SERVER_DIR' => ['type'=>'path','required'=>true,'label'=>'Savedir / servidor'],
        'WORLDS_DIR' => ['type'=>'path','required'=>true,'label'=>'worlds_local'],
        'PLUGINS_DIR' => ['type'=>'path','required'=>false,'label'=>'BepInEx plugins'],
        'CFG_DIR' => ['type'=>'path','required'=>false,'label'=>'BepInEx config'],
        'SERVER_LOG' => ['type'=>'file_path','required'=>false,'label'=>'Log Valheim'],
        'STEAMCMD_EXE' => ['type'=>'file','required'=>false,'label'=>'steamcmd.exe'],
        'STEAMCMD_PATH' => ['type'=>'path','required'=>false,'label'=>'Directorio SteamCMD'],
        'STEAMCMD_LOG' => ['type'=>'file_path','required'=>false,'label'=>'Log SteamCMD'],
        'RCON_HOST' => ['type'=>'string','required'=>false,'default'=>'127.0.0.1','label'=>'RCON host'],
        'RCON_PORT' => ['type'=>'int','required'=>false,'default'=>2974,'label'=>'RCON puerto'],
        'RCON_PASS' => ['type'=>'secret','required'=>false,'label'=>'RCON contraseña'],
        'LOG_MAX_LINES' => ['type'=>'int','required'=>false,'default'=>100,'label'=>'Máx. líneas log'],
        'SERVER_STATUS_MODE' => ['type'=>'enum','required'=>false,'default'=>'none','values'=>['none','port','folder'],'label'=>'Modo estado'],
    ];
}

function cm_aliases(): array {
    return [
        'VALHEIM_PATH'=>'SERVER_PATH',
        'VALHEIM_SERVER_PATH'=>'SERVER_PATH',
        'VALHEIM_DIR'=>'SERVER_BASE',
        'VALHEIM_BASE'=>'SERVER_BASE',
        'WORLD_DIR'=>'WORLDS_DIR',
        'WORLDS_PATH'=>'WORLDS_DIR',
        'SAVE_DIR'=>'SERVER_DIR',
        'SAVEDIR'=>'SERVER_DIR',
        'BEPINEX_PLUGINS'=>'PLUGINS_DIR',
        'BEPINEX_CONFIG'=>'CFG_DIR',
        'STEAMCMD'=>'STEAMCMD_EXE',
        'STEAM_CMD'=>'STEAMCMD_EXE',
        'RCON_PASSWORD'=>'RCON_PASS',
        'RCON_IP'=>'RCON_HOST',
    ];
}

function cm_normalize_path(string $v): string {
    $v = trim($v, " \t\n\r\0\x0B\"'");
    return $v;
}

function cm_eval_expr(array $tokens, array $known) {
    $parts = [];
    foreach ($tokens as $tok) {
        if (is_string($tok)) {
            if (trim($tok)==='' || $tok==='.' || $tok==='(' || $tok===')') continue;
            return null;
        }
        [$id,$text] = $tok;
        if (in_array($id,[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true)) continue;
        if ($id===T_CONSTANT_ENCAPSED_STRING) {
            $q=$text[0]??"'"; $body=substr($text,1,-1);
            if ($q==="'") $body=str_replace(["\\'","\\\\"],["'","\\"],$body);
            else $body=stripcslashes($body);
            $parts[]=$body; continue;
        }
        if ($id===T_LNUMBER) { $parts[]=(int)$text; continue; }
        if ($id===T_DNUMBER) { $parts[]=(float)$text; continue; }
        if ($id===T_DIR) { $parts[]='__DIR__'; continue; }
        if ($id===T_STRING) {
            $l=strtolower($text);
            if ($l==='true') {$parts[]=true;continue;}
            if ($l==='false') {$parts[]=false;continue;}
            if ($l==='null') {$parts[]=null;continue;}
            if ($text==='DIRECTORY_SEPARATOR') {$parts[]=DIRECTORY_SEPARATOR;continue;}
            if (array_key_exists($text,$known)) {$parts[]=$known[$text];continue;}
            return null;
        }
        return null;
    }
    if (!$parts) return null;
    if (count($parts)===1) return $parts[0];
    foreach($parts as $p){ if(is_array($p)||is_object($p)) return null; }
    return implode('', array_map(fn($x)=>$x===null?'':(string)$x,$parts));
}

function cm_parse_php(string $source): array {
    $tokens=token_get_all($source);
    $found=[]; $n=count($tokens);
    for($i=0;$i<$n;$i++){
        $tok=$tokens[$i];
        if(!is_array($tok)||$tok[0]!==T_STRING||strtolower($tok[1])!=='define') continue;
        $j=$i+1; while($j<$n && is_array($tokens[$j]) && $tokens[$j][0]===T_WHITESPACE)$j++;
        if(($tokens[$j]??null)!=='(') continue; $j++;
        while($j<$n && is_array($tokens[$j]) && $tokens[$j][0]===T_WHITESPACE)$j++;
        $nameTok=$tokens[$j]??null;
        if(!is_array($nameTok)||$nameTok[0]!==T_CONSTANT_ENCAPSED_STRING) continue;
        $name=trim($nameTok[1],"\"'"); $j++;
        while($j<$n && (is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE))$j++;
        if(($tokens[$j]??null)!==',') continue; $j++;
        $expr=[]; $depth=1;
        for(;$j<$n;$j++){
            $x=$tokens[$j];
            if($x==='('){$depth++;$expr[]=$x;continue;}
            if($x===')'){ $depth--; if($depth===0) break; $expr[]=$x;continue; }
            $expr[]=$x;
        }
        $value=cm_eval_expr($expr,$found);
        if($value!==null || preg_match('/\bnull\b/i',implode('',array_map(fn($x)=>is_array($x)?$x[1]:$x,$expr)))){
            $found[$name]=$value;
        }
        $i=$j;
    }
    return $found;
}

function cm_parse_import(string $filename, string $content): array {
    $ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
    if($ext==='json'){
        $d=json_decode($content,true);
        if(!is_array($d)) throw new RuntimeException('JSON de configuración inválido');
        if(isset($d['config'])&&is_array($d['config'])) $d=$d['config'];
        return $d;
    }
    if($ext==='php' || str_contains($content,'define(')) return cm_parse_php($content);
    throw new RuntimeException('Formato no soportado. Usa config.php o JSON exportado por el panel.');
}

function cm_map(array $raw): array {
    $schema=cm_schema(); $aliases=cm_aliases(); $mapped=[]; $unknown=[];
    foreach($raw as $k=>$v){
        // Contraseñas legacy en texto plano nunca se migran automáticamente.
        if ($k === 'ADMIN_PASS') { $unknown[$k] = '[LEGACY_PLAINTEXT_SECRET]'; continue; }
        $target = array_key_exists($k, $schema) ? $k : ($aliases[$k] ?? null);
        if($target && isset($schema[$target])) {
            if(!array_key_exists($target,$mapped)) $mapped[$target]=$v;
        } else {
            if(!in_array($k,['PANEL_INSTALLED','ALLOW_REINSTALL','SERVERS_JSON','VALHEIM_EXE'],true)) $unknown[$k]=$v;
        }
    }
    return [$mapped,$unknown];
}

function cm_derive(array $v): array {
    $derived=[];
    $base=isset($v['SERVER_BASE'])?rtrim(cm_normalize_path((string)$v['SERVER_BASE']),"\\/"):'';
    $serverDir=isset($v['SERVER_DIR'])?rtrim(cm_normalize_path((string)$v['SERVER_DIR']),"\\/"):'';

    if($base!==''){
        $candidates=[
            'SERVER_PATH'=>$base.DIRECTORY_SEPARATOR.'valheim_server.exe',
            'PLUGINS_DIR'=>$base.DIRECTORY_SEPARATOR.'BepInEx'.DIRECTORY_SEPARATOR.'plugins',
            'CFG_DIR'=>$base.DIRECTORY_SEPARATOR.'BepInEx'.DIRECTORY_SEPARATOR.'config',
        ];
        foreach($candidates as $k=>$x) if(empty($v[$k])) {$v[$k]=$x;$derived[$k]='Derivado desde SERVER_BASE';}
    }
    if($serverDir!==''){
        if(empty($v['WORLDS_DIR'])) {$v['WORLDS_DIR']=$serverDir.DIRECTORY_SEPARATOR.'worlds_local';$derived['WORLDS_DIR']='Derivado desde SERVER_DIR';}
        if(empty($v['SERVER_LOG'])) {$v['SERVER_LOG']=$serverDir.DIRECTORY_SEPARATOR.'server1.txt';$derived['SERVER_LOG']='Derivado desde SERVER_DIR';}
    }
    if(!empty($v['STEAMCMD_EXE']) && empty($v['STEAMCMD_PATH'])){
        $v['STEAMCMD_PATH']=dirname((string)$v['STEAMCMD_EXE']);$derived['STEAMCMD_PATH']='Derivado desde STEAMCMD_EXE';
    }
    if(!empty($v['STEAMCMD_PATH']) && empty($v['STEAMCMD_LOG'])){
        $v['STEAMCMD_LOG']=rtrim((string)$v['STEAMCMD_PATH'],"\\/").DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'console_log.txt';$derived['STEAMCMD_LOG']='Derivado desde STEAMCMD_PATH';
    }
    foreach(cm_schema() as $k=>$s) if(!array_key_exists($k,$v) && array_key_exists('default',$s)) {$v[$k]=$s['default'];$derived[$k]='Valor por defecto actual';}
    return [$v,$derived];
}

function cm_validate(array $values): array {
    $report=[];$missing=0;$invalid=0;$ok=0;
    foreach(cm_schema() as $k=>$s){
        $exists=array_key_exists($k,$values) && $values[$k]!=='' && $values[$k]!==null;
        $status='ok';$message='Detectado';
        if(!$exists){
            if(!empty($s['required'])){$status='missing';$message='Requerido por la versión actual';$missing++;}
            else {$status='optional';$message='Opcional';}
        } else {
            $v=$values[$k];
            if($s['type']==='int' && (!is_numeric($v) || (int)$v<0)) {$status='invalid';$message='Debe ser numérico';$invalid++;}
            elseif($s['type']==='enum' && !in_array((string)$v,$s['values'],true)) {$status='invalid';$message='Valor no reconocido';$invalid++;}
            elseif($s['type']==='file' && !is_file((string)$v)) {$status='warning';$message='La ruta no existe actualmente';}
            elseif($s['type']==='path' && !is_dir((string)$v)) {$status='warning';$message='La carpeta no existe actualmente';}
            else {$ok++;}
        }
        $report[$k]=['status'=>$status,'message'=>$message,'label'=>$s['label'],'required'=>(bool)$s['required']];
    }
    return ['fields'=>$report,'summary'=>['ok'=>$ok,'missing'=>$missing,'invalid'=>$invalid,'total'=>count(cm_schema())]];
}

function cm_masked(array $values): array {
    foreach(cm_schema() as $k=>$s){
        if(($s['type']??'')==='secret' && !empty($values[$k])) $values[$k]='••••••••';
    }
    return $values;
}

function cm_render_config(array $v, ?string $adminPassword=null): string {
    $schema=cm_schema();
    $lines=["<?php","// Generado por Migration & Configuration Center","define('PANEL_INSTALLED', true);","define('ALLOW_REINSTALL', false);"];

    $admin=(string)($v['ADMIN_USER']??'admin');
    $lines[]="define('ADMIN_USER', ".var_export($admin,true).");";

    $hash=(string)($v['ADMIN_PASS_HASH']??'');
    if($adminPassword!==null && $adminPassword!=='') $hash=password_hash($adminPassword,PASSWORD_DEFAULT);
    if($hash==='') throw new RuntimeException('Debes establecer una contraseña de administrador nueva o importar ADMIN_PASS_HASH.');
    $lines[]="define('ADMIN_PASS_HASH', ".var_export($hash,true).");";
    $lines[]="define('ADMIN_PASS', '');";

    foreach($schema as $k=>$s){
        if(in_array($k,['ADMIN_USER','ADMIN_PASS_HASH'],true)) continue;
        $val=$v[$k]??($s['default']??'');
        if($s['type']==='int') $lines[]="define('$k', ".(int)$val.");";
        else $lines[]="define('$k', ".var_export((string)$val,true).");";
    }
    $lines[]="define('SERVERS_JSON', __DIR__ . DIRECTORY_SEPARATOR . 'servers.json');";
    $lines[]="\$LISTS = ['adminlist.txt'=>'👑 Administradores','bannedlist.txt'=>'🚫 Baneados','permittedlist.txt'=>'✅ Permitidos'];";
    $lines[]="if (session_status() === PHP_SESSION_NONE) session_start();";
    $lines[]="if (empty(\$_SESSION['csrf_token'])) \$_SESSION['csrf_token']=bin2hex(random_bytes(32));";
    return implode(PHP_EOL,$lines).PHP_EOL;
}
