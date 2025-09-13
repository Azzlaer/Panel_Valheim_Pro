<?php
require_once __DIR__ . "/config.php";

header("Content-Type: application/json; charset=utf-8");

// Requiere sesión activa
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// =====================
// Helpers
// =====================
function out($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}
function is_server_running($exeName) {
    $out = shell_exec('tasklist /FI "IMAGENAME eq ' . $exeName . '"');
    return strpos($out ?? '', $exeName) !== false;
}
function safe_read_lines($path) {
    if (!is_file($path) || !is_readable($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) return [];
    // trim y filtrar vacíos
    $lines = array_map('trim', $lines);
    $lines = array_values(array_filter($lines, fn($v) => $v !== ''));
    return $lines;
}
function safe_write_lines($path, $lines) {
    $data = implode(PHP_EOL, $lines) . PHP_EOL;
    return file_put_contents($path, $data) !== false;
}
function check_csrf($token) {
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}
function check_csrf_if_present() {
    if (isset($_POST['csrf']) && !check_csrf($_POST['csrf'])) {
        out(['ok' => false, 'error' => 'CSRF']);
    }
}

// Normalizar bases reales
$CFG_DIR_REAL    = realpath(CFG_DIR) ?: null;
$SERVER_DIR_REAL = realpath(SERVER_DIR) ?: null;

$action = $_GET['action'] ?? '';

// =====================
// Router
// =====================
switch ($action) {
    
	    // -------------------------------------------------
    // MAPAS worlds_local
    // -------------------------------------------------
    case 'upload_map': {
        if (empty($_FILES['mapFile']) || $_FILES['mapFile']['error'] !== UPLOAD_ERR_OK) {
            out(['ok'=>false,'error'=>'Error de subida']);
        }
        $allowed = ['fwl','db','old'];
        $ext = strtolower(pathinfo($_FILES['mapFile']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext,$allowed)) out(['ok'=>false,'error'=>'Extensión no permitida']);
        $dest = rtrim(WORLDS_DIR,'\\/') . DIRECTORY_SEPARATOR . basename($_FILES['mapFile']['name']);
        if (!move_uploaded_file($_FILES['mapFile']['tmp_name'], $dest))
            out(['ok'=>false,'error'=>'No se pudo mover el archivo']);
        out(['ok'=>true]);
    }

    case 'list_maps': {
        $dir = realpath(WORLDS_DIR);
        if (!$dir || !is_dir($dir)) out(['ok'=>false,'error'=>'Directorio inválido']);
        $items = [];
        foreach (glob($dir.'/*.{fwl,db,old}', GLOB_BRACE) as $f) {
            $items[] = [
                'name'=>basename($f),
                'size_mb'=>filesize($f)/1048576,
                'ext'=>strtolower(pathinfo($f, PATHINFO_EXTENSION))
            ];
        }
        out(['ok'=>true,'items'=>$items]);
    }

    case 'delete_map': {
        $file = basename($_GET['file'] ?? '');
        if ($file === '') out(['ok'=>false,'error'=>'Archivo no especificado']);
        $target = rtrim(WORLDS_DIR,'\\/') . DIRECTORY_SEPARATOR . $file;
        if (!is_file($target)) out(['ok'=>false,'error'=>'No encontrado']);
        if (!unlink($target)) out(['ok'=>false,'error'=>'No se pudo eliminar']);
        out(['ok'=>true]);
    }

	
	
	
	// -------------------------------------------------
    // BACKUPS worlds_local
    // -------------------------------------------------
    case 'create_backup': {
        $src = realpath(WORLDS_DIR);
        if(!$src || !is_dir($src)) out(['ok'=>false,'error'=>'Directorio worlds_local inválido']);

        $backupDir = __DIR__ . '/backups';
        if(!is_dir($backupDir)) mkdir($backupDir,0777,true);

        $fname = 'worlds_backup_'.date('Ymd_His').'.zip';
        $zipPath = $backupDir . '/' . $fname;

        $zip = new ZipArchive();
        if($zip->open($zipPath, ZipArchive::CREATE)!==TRUE){
            out(['ok'=>false,'error'=>'No se pudo crear ZIP']);
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS));
        foreach($it as $file){
            $path = $file->getRealPath();
            $rel  = substr($path, strlen($src)+1);
            $zip->addFile($path, $rel);
        }
        $zip->close();
        out(['ok'=>true,'file'=>$fname]);
    }

    case 'list_backups': {
        $dir = __DIR__ . '/backups';
        if(!is_dir($dir)) out(['ok'=>true,'items'=>[]]);
        $files = glob($dir.'/*.zip');
        $items=[];
        foreach($files as $f){
            $items[]=[
                'name'=>basename($f),
                'size_mb'=>filesize($f)/1048576,
                'mtime'=>date('Y-m-d H:i:s',filemtime($f))
            ];
        }
        out(['ok'=>true,'items'=>$items]);
    }

    case 'delete_backup': {
        $file = basename($_GET['file'] ?? '');
        if($file==='') out(['ok'=>false,'error'=>'Archivo no especificado']);
        $path = __DIR__ . '/backups/' . $file;
        if(!is_file($path)) out(['ok'=>false,'error'=>'No existe']);
        if(!unlink($path)) out(['ok'=>false,'error'=>'No se pudo eliminar']);
        out(['ok'=>true]);
    }

	
	
	// -------------------------------------------------
    // SERVERS: start/stop/savejson + status
    // -------------------------------------------------
		
	case 'alert_create': {
    check_csrf_if_present();

    $serverId = intval($_POST['server_id'] ?? 0);
    $command  = trim($_POST['command'] ?? '');
    $interval = intval($_POST['interval'] ?? 0);
    $repeats  = intval($_POST['repeats'] ?? 1);

    if ($serverId <= 0) out(['ok'=>false,'error'=>'ID de servidor inválido']);
    if ($command === '') out(['ok'=>false,'error'=>'Comando vacío']);
    if ($interval <= 0) out(['ok'=>false,'error'=>'Intervalo inválido']);
    if ($repeats < 1) out(['ok'=>false,'error'=>'Repeticiones inválidas']);
    if (!file_exists(SERVERS_JSON)) out(['ok'=>false,'error'=>'servers.json no existe']);

    $servers = json_decode(file_get_contents(SERVERS_JSON), true);
    if (!is_array($servers)) out(['ok'=>false,'error'=>'servers.json inválido']);
    $found = null;
    foreach ($servers as $s) { if (intval($s['id']) === $serverId) { $found = $s; break; } }
    if (!$found) out(['ok'=>false,'error'=>'Servidor no encontrado']);

    $alertsFile = __DIR__ . DIRECTORY_SEPARATOR . 'alerts.json';
    if (!file_exists($alertsFile)) file_put_contents($alertsFile, '[]');

    $jobs = json_decode(file_get_contents($alertsFile), true);
    if (!is_array($jobs)) $jobs = [];

    $now = time();
    $id  = 'alrt_' . $now . '_' . substr(bin2hex(random_bytes(3)), 0, 6);

    $jobs[] = [
        'id'            => $id,
        'server_id'     => $serverId,
        'command'       => $command,
        'interval'      => $interval,
        'repeats_total' => $repeats,
        'repeats_left'  => $repeats,
        'next_run_ts'   => $now + $interval, // primera ejecución en un intervalo
        'status'        => 'active',
        'created_at'    => $now,
        'last_run_ts'   => null
    ];

    if (file_put_contents($alertsFile, json_encode($jobs, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) === false) {
        out(['ok'=>false,'error'=>'No se pudo escribir alerts.json']);
    }
    out(['ok'=>true, 'id'=>$id]);
}

case 'alerts_list': {
    $alertsFile = __DIR__ . DIRECTORY_SEPARATOR . 'alerts.json';
    if (!file_exists($alertsFile)) out(['ok'=>true, 'items'=>[]]);

    $jobs = json_decode(file_get_contents($alertsFile), true);
    if (!is_array($jobs)) $jobs = [];
    // Puedes filtrar/ordenar aquí si quieres
    out(['ok'=>true, 'items'=>$jobs]);
}

case 'alert_cancel': {
    check_csrf_if_present();

    $id = trim($_POST['id'] ?? $_GET['id'] ?? '');
    if ($id === '') out(['ok'=>false,'error'=>'ID vacío']);

    $alertsFile = __DIR__ . DIRECTORY_SEPARATOR . 'alerts.json';
    if (!file_exists($alertsFile)) out(['ok'=>false,'error'=>'alerts.json no existe']);

    $jobs = json_decode(file_get_contents($alertsFile), true);
    if (!is_array($jobs)) $jobs = [];

    $found = false;
    foreach ($jobs as &$j) {
        if ($j['id'] === $id) {
            $j['status'] = 'canceled';
            $found = true;
            break;
        }
    }
    unset($j);

    if (!$found) out(['ok'=>false,'error'=>'Alerta no encontrada']);

    if (file_put_contents($alertsFile, json_encode($jobs, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) === false) {
        out(['ok'=>false,'error'=>'No se pudo actualizar alerts.json']);
    }
    out(['ok'=>true]);
}


	case 'schedule_alert': {
    check_csrf_if_present();

    $serverId = intval($_POST['server_id'] ?? 0);
    $command  = trim($_POST['command'] ?? '');
    $interval = intval($_POST['interval'] ?? 0);
    $repeats  = intval($_POST['repeats'] ?? 1);

    if ($serverId <= 0) out(['ok'=>false,'error'=>'ID de servidor inválido']);
    if ($command === '') out(['ok'=>false,'error'=>'Comando vacío']);
    if ($interval <= 0) out(['ok'=>false,'error'=>'Intervalo inválido']);
    if ($repeats < 1) out(['ok'=>false,'error'=>'Repeticiones inválidas']);

    if (!file_exists(SERVERS_JSON)) out(['ok'=>false,'error'=>'servers.json no existe']);
    $servers = json_decode(file_get_contents(SERVERS_JSON), true);
    if (!is_array($servers)) out(['ok'=>false,'error'=>'servers.json inválido']);
    $found = null;
    foreach ($servers as $s) {
        if (intval($s['id']) === $serverId) { $found = $s; break; }
    }
    if (!$found) out(['ok'=>false,'error'=>'Servidor no encontrado']);

    // Python a ejecutar
    $py = escapeshellarg(PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'); // ajusta si usas "py" o "python3"
    $script = escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'rcon_alert.py');

    // Argumentos
    $args = [
        '--host', RCON_HOST,
        '--port', (string)RCON_PORT,
        '--password', RCON_PASS,
        '--command', $command,
        '--interval', (string)$interval,
        '--repeats', (string)$repeats
    ];

    $parts = [];
    foreach ($args as $a) $parts[] = escapeshellarg($a);
    $cmd = $py . ' ' . $script . ' ' . implode(' ', $parts);

    // Lanzar en background (Windows)
    $launch = 'start "VH Alert" cmd /c ' . $cmd;
    pclose(popen($launch, 'r'));

    out(['ok'=>true]);
}


	case 'server': {
        $op = $_POST['op'] ?? '';

        if ($op === 'start') {
            $id = intval($_POST['id'] ?? 0);
            if (!file_exists(SERVERS_JSON)) out(['ok'=>false,'error'=>'servers.json no existe']);
            $servers = json_decode(file_get_contents(SERVERS_JSON), true);
            if (!is_array($servers)) out(['ok'=>false,'error'=>'servers.json inválido']);

            foreach ($servers as $s) {
                if (intval($s['id']) === $id) {
                    // Ejecutar sin bloquear (Windows)
                    $cmd = 'start "ValheimServer'.$id.'" "'.$s['path'].'" '.$s['params'];
                    pclose(popen($cmd, 'r'));
                    out(['ok'=>true]);
                }
            }
            out(['ok'=>false,'error'=>'Servidor no encontrado']);
        }

        if ($op === 'stop') {
            // Mata el proceso principal
            // Nota: sin escapeshellarg en IMAGENAME por formato de taskkill; asume VALHEIM_EXE simple
            shell_exec('taskkill /F /IM ' . VALHEIM_EXE);
            out(['ok'=>true]);
        }

        if ($op === 'savejson') {
            check_csrf_if_present();
            $content = $_POST['content'] ?? '';
            $parsed = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
                out(['ok'=>false,'error'=>'JSON inválido']);
            }
            if (file_put_contents(SERVERS_JSON, $content) === false) {
                out(['ok'=>false,'error'=>'No se pudo escribir servers.json']);
            }
            out(['ok'=>true]);
        }

        out(['ok'=>false,'error'=>'Operación de servidor inválida']);
    }

    case 'status_servers': {
        $running = is_server_running(VALHEIM_EXE);
        $map = [];
        if (file_exists(SERVERS_JSON)) {
            $servers = json_decode(file_get_contents(SERVERS_JSON), true) ?: [];
            foreach ($servers as $s) {
                $map[$s['id']] = $running ? 'running' : 'stopped';
            }
        }
        out(['ok'=>true,'status'=>$map]);
    }

    // -------------------------------------------------
    // LISTS: get/add/delete (admin/banned/permitted)
    // -------------------------------------------------
    case 'get_list': {
        $file = $_GET['file'] ?? '';
        global $LISTS;
        if (!isset($LISTS[$file])) out(['ok'=>false,'error'=>'Archivo inválido']);

        if (!$SERVER_DIR_REAL) out(['ok'=>false,'error'=>'SERVER_DIR inválido']);
        $path = $SERVER_DIR_REAL . DIRECTORY_SEPARATOR . basename($file);

        // autocrear si no existe
        if (!file_exists($path)) {
            @file_put_contents($path, '');
        }

        $pathReal = realpath($path);
        if (!$pathReal || strpos($pathReal, $SERVER_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Ruta fuera de alcance']);

        $list = safe_read_lines($pathReal);
        // Por compat con el frontend de lists.php, devolvemos array puro
        out($list);
    }

    case 'add_list': {
        check_csrf_if_present();
        $file  = $_GET['file'] ?? $_POST['file'] ?? '';
        $entry = trim($_POST['entry'] ?? $_POST['value'] ?? '');
        global $LISTS;

        if (!isset($LISTS[$file])) out(['ok'=>false,'error'=>'Archivo inválido']);
        if ($entry === '') out(['ok'=>false,'error'=>'Entrada vacía']);
        if (!$SERVER_DIR_REAL) out(['ok'=>false,'error'=>'SERVER_DIR inválido']);

        $path = $SERVER_DIR_REAL . DIRECTORY_SEPARATOR . basename($file);
        if (!file_exists($path)) { @file_put_contents($path, ''); }

        $pathReal = realpath($path);
        if (!$pathReal || strpos($pathReal, $SERVER_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Ruta fuera de alcance']);

        $list = safe_read_lines($pathReal);
        if (!in_array($entry, $list, true)) $list[] = $entry;

        if (!safe_write_lines($pathReal, $list)) out(['ok'=>false,'error'=>'No se pudo guardar']);
        out(['ok'=>true]);
    }

    case 'delete_list': {
        check_csrf_if_present();
        $file  = $_GET['file'] ?? $_POST['file'] ?? '';
        $index = isset($_GET['index']) ? intval($_GET['index']) : (isset($_POST['index']) ? intval($_POST['index']) : -1);
        global $LISTS;

        if (!isset($LISTS[$file])) out(['ok'=>false,'error'=>'Archivo inválido']);
        if ($index < 0) out(['ok'=>false,'error'=>'Índice inválido']);
        if (!$SERVER_DIR_REAL) out(['ok'=>false,'error'=>'SERVER_DIR inválido']);

        $path = $SERVER_DIR_REAL . DIRECTORY_SEPARATOR . basename($file);
        $pathReal = realpath($path);
        if (!$pathReal || strpos($pathReal, $SERVER_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Ruta fuera de alcance']);

        $list = safe_read_lines($pathReal);
        if (!isset($list[$index])) out(['ok'=>false,'error'=>'Índice fuera de rango']);

        unset($list[$index]);
        $list = array_values($list);

        if (!safe_write_lines($pathReal, $list)) out(['ok'=>false,'error'=>'No se pudo guardar']);
        out(['ok'=>true]);
    }

    // -------------------------------------------------
    // CFG: get/save (usando rutas relativas a CFG_DIR)
    // -------------------------------------------------
    case 'get_cfg': {
        if (!$CFG_DIR_REAL) out(['ok'=>false,'error'=>'CFG_DIR inválido']);
        $rel = $_GET['rel'] ?? '';
        if ($rel === '') out(['ok'=>false,'error'=>'Falta parámetro']);

        $target = realpath($CFG_DIR_REAL . DIRECTORY_SEPARATOR . $rel);
        if (!$target || strpos($target, $CFG_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Archivo inválido']);
        if (!is_file($target) || !is_readable($target)) out(['ok'=>false,'error'=>'No encontrado']);

        out(['ok'=>true, 'content'=>file_get_contents($target)]);
    }

    case 'save_cfg': {
        check_csrf_if_present();
        if (!$CFG_DIR_REAL) out(['ok'=>false,'error'=>'CFG_DIR inválido']);
        $rel = $_POST['rel'] ?? '';
        $content = $_POST['content'] ?? '';

        if ($rel === '') out(['ok'=>false,'error'=>'Falta parámetro']);
        $target = realpath($CFG_DIR_REAL . DIRECTORY_SEPARATOR . $rel);
        if (!$target || strpos($target, $CFG_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Archivo inválido']);
        if (!is_file($target) || !is_writable($target)) out(['ok'=>false,'error'=>'No se puede escribir']);

        if (file_put_contents($target, $content) === false) {
            out(['ok'=>false,'error'=>'Error al guardar']);
        }
        out(['ok'=>true]);
    }

    // -------------------------------------------------
    // LOGS: server / steamcmd
    // -------------------------------------------------
    case 'view_log': {
        $which = $_GET['file'] ?? '';
        $allowed = [
            'server'   => SERVER_LOG,
            'steamcmd' => STEAMCMD_LOG
        ];
        if (!isset($allowed[$which])) out(['ok'=>false,'error'=>'No permitido']);

        $log = $allowed[$which];
        if (!is_file($log) || !is_readable($log)) out(['ok'=>false,'error'=>'Log no encontrado']);

        out(['ok'=>true, 'content'=>file_get_contents($log)]);
    }


	case 'schedule_restart': {
    check_csrf_if_present();

    $serverId    = intval($_POST['server_id'] ?? 0);
    $delay       = intval($_POST['delay'] ?? 0);
    $saveBefore  = intval($_POST['save_before'] ?? 0) === 1 ? 1 : 0;

    if ($serverId <= 0) out(['ok'=>false,'error'=>'ID de servidor inválido']);
    if ($delay < 0) out(['ok'=>false,'error'=>'Delay inválido']);

    if (!file_exists(SERVERS_JSON)) out(['ok'=>false,'error'=>'servers.json no existe']);
    $servers = json_decode(file_get_contents(SERVERS_JSON), true);
    if (!is_array($servers)) out(['ok'=>false,'error'=>'servers.json inválido']);
    $found = null;
    foreach ($servers as $s) {
        if (intval($s['id']) === $serverId) { $found = $s; break; }
    }
    if (!$found) out(['ok'=>false,'error'=>'Servidor no encontrado']);

    // Ruta del script Python
    $py = escapeshellarg(PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'); // ajusta si usas pyw/python3
    $script = escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'rcon_restart.py');

    // Construir args
    $args = [
        '--host', RCON_HOST,
        '--port', (string)RCON_PORT,
        '--password', RCON_PASS,
        '--delay', (string)$delay,
        '--servers-json', SERVERS_JSON,
        '--server-id', (string)$serverId,
        '--exe-name', VALHEIM_EXE,
        '--save-before', (string)$saveBefore
    ];
    // escapar arguments
    $parts = [];
    foreach ($args as $a) { $parts[] = escapeshellarg($a); }
    $cmd = $py . ' ' . $script . ' ' . implode(' ', $parts);

    // Lanzar en segundo plano (Windows: start)
    $launch = 'start "VH Cron Restart" cmd /c ' . $cmd;
    pclose(popen($launch, 'r'));

    out(['ok'=>true]);
}


    // -------------------------------------------------
    // RCON (simple): password\\n + command\\n
    // -------------------------------------------------
    case 'rcon_send': {
    $cmd = trim($_POST['command'] ?? $_POST['cmd'] ?? '');
    if ($cmd === '') out(['ok'=>false,'error'=>'Comando vacío']);

    class PhpRconClient {
        private $fp;
        private $reqId = 0;

        public function __construct($host, $port, $timeout = 3) {
            $this->fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
            if (!$this->fp) throw new Exception("No se pudo conectar a RCON: $errstr");
            stream_set_timeout($this->fp, $timeout);
        }
        private function sendPacket($type, $payload) {
            $this->reqId++;
            $reqId = $this->reqId;

            $data = pack('V', $reqId) . pack('V', $type) . $payload . "\x00\x00";
            $packet = pack('V', strlen($data)) . $data;
            fwrite($this->fp, $packet);

            // Leer respuesta (puede venir en varios paquetes)
            $response = '';
            while (true) {
                $lenData = fread($this->fp, 4);
                if (strlen($lenData) !== 4) break;
                $length = unpack('V', $lenData)[1];

                $body = '';
                $read = 0;
                while ($read < $length) {
                    $chunk = fread($this->fp, $length - $read);
                    if ($chunk === false || $chunk === '') break 2;
                    $read += strlen($chunk);
                    $body .= $chunk;
                }

                $respReqId = unpack('V', substr($body, 0, 4))[1];
                $respType  = unpack('V', substr($body, 4, 4))[1];
                // payload es hasta los 2 bytes nulos finales
                $payloadStr = substr($body, 8, -2);

                // Acumular
                $response .= $payloadStr;

                // Heurística simple: si el payload es corto o no quedan datos pendientes, salimos.
                // (Para respuestas largas, algunos clientes envían un paquete “vacío” sentinel; aquí mantenemos simple.)
                if (strlen($payloadStr) === 0) break;
                if (strlen($payloadStr) < 4000) break;
            }
            return $response;
        }
        public function login($password) {
            // type 3 = login
            $resp = $this->sendPacket(3, $password);
            // Algunos servidores no devuelven texto útil en login; si llega algo, lo ignoramos.
            return true;
        }
        public function command($cmd) {
            // type 2 = command
            return $this->sendPacket(2, $cmd);
        }
        public function close() {
            if ($this->fp) fclose($this->fp);
        }
    }

    try {
        $cli = new PhpRconClient(RCON_HOST, RCON_PORT, 4);
        $cli->login(RCON_PASS);

        $resp = trim($cli->command($cmd));
        $cli->close();

        out(['ok'=>true,'response'=>$resp]);
    } catch (Exception $e) {
        out(['ok'=>false,'error'=>$e->getMessage()]);
    }
}


    // -------------------------------------------------
    // STEAM UPDATE: normal / prebeta
    // -------------------------------------------------
    case 'steam_update': {
        check_csrf_if_present();

        $type = $_POST['type'] ?? '';
        if (!in_array($type, ['normal','prebeta'], true)) out(['ok'=>false,'error'=>'Tipo inválido']);
        if (!is_file(STEAMCMD_EXE)) out(['ok'=>false,'error'=>'steamcmd.exe no encontrado']);

        // Opcional: bloquear si el server corre
        // if (is_server_running(VALHEIM_EXE)) out(['ok'=>false,'error'=>'Servidor en ejecución']);

        if ($type === 'prebeta') {
            $cmd = '"' . STEAMCMD_EXE . '" +login anonymous +app_update 896660 -beta public-test -betapassword yesimadebackups validate +quit';
        } else {
            $cmd = '"' . STEAMCMD_EXE . '" +login anonymous +app_update 896660 validate +quit';
        }

        pclose(popen('start "ValheimUpdate" cmd /c ' . $cmd, 'r'));
        out(['ok'=>true]);
    }

    // -------------------------------------------------
    default:
        out(['ok' => false, 'error' => 'Acción inválida']);
}
