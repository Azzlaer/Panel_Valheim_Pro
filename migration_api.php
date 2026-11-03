<?php
$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) require_once $configPath;
require_once __DIR__ . '/lib/config_migrator.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$installed = defined('PANEL_INSTALLED') ? PANEL_INSTALLED : false;
if ($installed && (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'Debes iniciar sesión para migrar una instalación existente'], JSON_UNESCAPED_UNICODE);
    exit;
}

function mc_out(array $payload, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
function mc_csrf(): void {
    $token = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        mc_out(['ok'=>false,'error'=>'Token CSRF inválido'],403);
    }
}
function mc_current_values(): array {
    $out=[];
    foreach(cm_schema() as $k=>$s){
        if(defined($k)) $out[$k]=constant($k);
    }
    return $out;
}
function mc_public_values(array $values): array {
    $pub=$values;
    foreach(cm_schema() as $k=>$s){
        if(($s['type']??'')==='secret'){
            $pub[$k]='';
            $pub[$k.'_present']=!empty($values[$k]);
        }
    }
    return $pub;
}

$action=$_GET['action'] ?? $_POST['action'] ?? 'status';

try {
    switch($action){
        case 'status': {
            $current=mc_current_values();
            [$current,$derived]=cm_derive($current);
            $report=cm_validate($current);
            mc_out([
                'ok'=>true,
                'installed'=>$installed,
                'staged'=>!empty($_SESSION['migration_values']),
                'current'=>mc_public_values($current),
                'current_report'=>$report,
                'schema'=>cm_schema()
            ]);
        }

        case 'import': {
            mc_csrf();
            if (empty($_FILES['config_file']) || $_FILES['config_file']['error'] !== UPLOAD_ERR_OK) {
                mc_out(['ok'=>false,'error'=>'No se recibió un archivo de configuración válido'],400);
            }
            if ((int)$_FILES['config_file']['size'] > 2*1024*1024) {
                mc_out(['ok'=>false,'error'=>'El archivo supera el máximo de 2 MB'],400);
            }
            $name=(string)$_FILES['config_file']['name'];
            $content=(string)file_get_contents($_FILES['config_file']['tmp_name']);
            $raw=cm_parse_import($name,$content);
            [$mapped,$unknown]=cm_map($raw);

            // Si hay una instalación actual, úsala como fallback pero prioriza lo importado.
            $current=mc_current_values();
            $values=array_replace($current,$mapped);
            [$values,$derived]=cm_derive($values);
            $report=cm_validate($values);

            $_SESSION['migration_values']=$values;
            $_SESSION['migration_source']=[
                'filename'=>$name,
                'imported_at'=>date('c'),
                'unknown'=>$unknown,
                'derived'=>$derived,
                'legacy_plain_admin_pass'=>array_key_exists('ADMIN_PASS',$raw),
            ];

            mc_out([
                'ok'=>true,
                'filename'=>$name,
                'schema'=>cm_schema(),
                'values'=>mc_public_values($values),
                'report'=>$report,
                'derived'=>$derived,
                'unknown_keys'=>array_keys($unknown),
                'legacy_plain_admin_pass'=>array_key_exists('ADMIN_PASS',$raw),
                'message'=>'Configuración importada a staging. Aún no se ha modificado config.php.'
            ]);
        }

        case 'apply': {
            mc_csrf();
            if (empty($_SESSION['migration_values']) || !is_array($_SESSION['migration_values'])) {
                mc_out(['ok'=>false,'error'=>'No hay una configuración importada en staging'],400);
            }

            $values=$_SESSION['migration_values'];
            foreach(cm_schema() as $k=>$s){
                if(($s['type']??'')==='secret') continue;
                if(array_key_exists($k,$_POST)) $values[$k]=trim((string)$_POST[$k]);
            }
            if(isset($_POST['RCON_PASS']) && $_POST['RCON_PASS']!=='') $values['RCON_PASS']=(string)$_POST['RCON_PASS'];

            $newAdminPassword=(string)($_POST['ADMIN_PASSWORD_NEW']??'');
            if($newAdminPassword!=='' && strlen($newAdminPassword)<8) {
                mc_out(['ok'=>false,'error'=>'La nueva contraseña de administrador debe tener al menos 8 caracteres'],400);
            }

            [$values,$derived]=cm_derive($values);
            $report=cm_validate($values);
            if(($report['summary']['missing']??0)>0 || ($report['summary']['invalid']??0)>0) {
                mc_out(['ok'=>false,'error'=>'Aún hay campos requeridos faltantes o inválidos','report'=>$report],422);
            }

            $php=cm_render_config($values,$newAdminPassword!==''?$newAdminPassword:null);

            $backup=null;
            if(is_file($configPath)){
                $backup=__DIR__ . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'config';
                if(!is_dir($backup) && !@mkdir($backup,0775,true)) throw new RuntimeException('No se pudo crear backups/config');
                $backup.=DIRECTORY_SEPARATOR.'config_'.date('Ymd_His').'.php.bak';
                if(!@copy($configPath,$backup)) throw new RuntimeException('No se pudo respaldar config.php actual');
            }

            // El backup ya existe; ahora sobrescribimos de forma bloqueada.
            // Evita el hueco de borrar config.php antes de un rename fallido en Windows.
            if(@file_put_contents($configPath,$php,LOCK_EX)===false) {
                throw new RuntimeException('No se pudo reemplazar config.php');
            }

            unset($_SESSION['migration_values'],$_SESSION['migration_source']);
            mc_out([
                'ok'=>true,
                'message'=>'Migración aplicada correctamente',
                'backup'=>$backup?basename($backup):null
            ]);
        }

        case 'reset': {
            mc_csrf();
            unset($_SESSION['migration_values'],$_SESSION['migration_source']);
            mc_out(['ok'=>true]);
        }

        default:
            mc_out(['ok'=>false,'error'=>'Acción inválida'],400);
    }
} catch(Throwable $e) {
    mc_out(['ok'=>false,'error'=>$e->getMessage()],500);
}
