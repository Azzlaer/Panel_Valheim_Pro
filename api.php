<?php
require_once __DIR__ . "/config.php";
header("Content-Type: application/json; charset=utf-8");

// =====================
// Autenticación
// =====================
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================
// Helpers
// =====================
function out($arr) {
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function safe_read_lines($path) {
    if (!is_file($path) || !is_readable($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) return [];
    $lines = array_map('trim', $lines);
    return array_values(array_filter($lines, fn($v) => $v !== ''));
}

function safe_write_lines($path, $lines) {
    $data = implode(PHP_EOL, $lines) . PHP_EOL;
    return file_put_contents($path, $data) !== false;
}

function check_csrf($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function check_csrf_if_present() {
    if (isset($_POST['csrf']) && !check_csrf($_POST['csrf'])) {
        out(['ok' => false, 'error' => 'CSRF']);
    }
}

function is_safe_relative_path($rel) {
    if ($rel === '' || strpos($rel, "\0") !== false) return false;
    if (preg_match('#(^[a-zA-Z]:[\\\\/])|(^[\\\\/])#', $rel)) return false;
    if (strpos($rel, '..') !== false) return false;
    return true;
}

function normalize_win_path($path) {
    return strtolower(str_replace('/', '\\', trim((string)$path)));
}

function getServers() {
    if (!defined('SERVERS_JSON') || !file_exists(SERVERS_JSON)) return [];
    $arr = json_decode(file_get_contents(SERVERS_JSON), true);
    return is_array($arr) ? $arr : [];
}

function saveServers($jsonText) {
    $data = json_decode($jsonText, true);
    if (!is_array($data)) return false;
    return file_put_contents(
        SERVERS_JSON,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ) !== false;
}

// Normalizar rutas base
$CFG_DIR_REAL    = defined('CFG_DIR') ? (realpath(CFG_DIR) ?: null) : null;
$SERVER_DIR_REAL = defined('SERVER_DIR') ? (realpath(SERVER_DIR) ?: null) : null;

// =====================
// PID STORAGE
// =====================
define("PID_FILE", __DIR__ . "/server.pid");

function savePID($pid){
    file_put_contents(PID_FILE, intval($pid));
}

function getSavedPID(){
    if (!file_exists(PID_FILE)) return null;
    $pid = intval(trim(file_get_contents(PID_FILE)));
    return ($pid > 0) ? $pid : null;
}

function clearPID(){
    if (file_exists(PID_FILE)) @unlink(PID_FILE);
}

function pidExists($pid){
    $pid = intval($pid);
    if ($pid <= 0) return false;

    $out = @shell_exec('tasklist /FI "PID eq ' . $pid . '" /FO CSV /NH');
    if (!$out) return false;

    $line = trim($out);
    if ($line === '' || stripos($line, 'No tasks are running') !== false) return false;

    return strpos($line, '"' . $pid . '"') !== false;
}

function getProcessNameByPID($pid) {
    $pid = intval($pid);
    if ($pid <= 0) return null;

    $out = @shell_exec('tasklist /FI "PID eq ' . $pid . '" /FO CSV /NH');
    if (!$out) return null;

    $line = trim($out);
    if ($line === '' || stripos($line, 'No tasks are running') !== false) return null;

    $row = str_getcsv($line);
    return $row[0] ?? null;
}

function isServerRunning(){
    $pid = getSavedPID();
    if (!$pid) return false;

    if (!pidExists($pid)){
        clearPID();
        return false;
    }

    $expectedExe = defined('SERVER_PATH') ? basename(SERVER_PATH) : 'valheim_server.exe';
    $realExe = getProcessNameByPID($pid);

    if (!$realExe || strcasecmp($realExe, $expectedExe) !== 0) {
        clearPID();
        return false;
    }

    return true;
}

// =====================
// Detección robusta del proceso Valheim
// Basada en la misma lógica que sí funciona en procesos_valheim.php
// =====================
function getValheimProcessesRaw($expectedExe = 'valheim_server.exe') {
    $expectedExe = basename($expectedExe);

    $psCommand = 'Get-CimInstance Win32_Process | ' .
        'Where-Object { $_.Name -eq "' . $expectedExe . '" } | ' .
        'Select-Object ProcessId, Name, ExecutablePath, HandleCount, WorkingSetSize, VirtualSize, CreationDate, CommandLine | ' .
        'ConvertTo-Json -Compress';

    $output = @shell_exec(
        'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psCommand . '"'
    );

    if (!isset($output) || !is_string($output) || trim($output) === '') {
        return [];
    }

    $data = @json_decode(trim($output), true);
    if (!is_array($data)) {
        return [];
    }

    if (isset($data['ProcessId'])) {
        $data = [$data];
    }

    return $data;
}

function extractSavedirFromParams($params) {
    if (preg_match('/-savedir\s+"([^"]+)"/i', $params, $m)) {
        return normalize_win_path($m[1]);
    }
    if (preg_match('/-savedir\s+([^\s]+)/i', $params, $m)) {
        return normalize_win_path(trim($m[1], '"'));
    }
    return null;
}

function findNewestValheimPID($exePath, $params = '') {
    if (!is_file($exePath)) return false;

    $expectedExe = basename($exePath);
    $realExePath = realpath($exePath);
    if (!$realExePath) return false;

    $realExeNorm = normalize_win_path($realExePath);
    $savedirMatch = extractSavedirFromParams($params);

    for ($try = 0; $try < 8; $try++) {
        $processes = getValheimProcessesRaw($expectedExe);

        if (!empty($processes)) {
            usort($processes, function($a, $b){
                return strcmp((string)($b['CreationDate'] ?? ''), (string)($a['CreationDate'] ?? ''));
            });

            foreach ($processes as $proc) {
                $pid  = intval($proc['ProcessId'] ?? 0);
                $name = $proc['Name'] ?? '';
                $path = normalize_win_path($proc['ExecutablePath'] ?? '');
                $cmdl = normalize_win_path($proc['CommandLine'] ?? '');

                if ($pid <= 0) continue;
                if (!$name || strcasecmp($name, $expectedExe) !== 0) continue;

                if ($path && $path === $realExeNorm) {
                    return $pid;
                }

                if ($cmdl && $realExeNorm && strpos($cmdl, $realExeNorm) !== false) {
                    return $pid;
                }

                if ($savedirMatch && $cmdl && strpos($cmdl, $savedirMatch) !== false) {
                    return $pid;
                }
            }
        }

        sleep(2);
    }

    return false;
}

// =====================
// Inicio / detención servidor con PID real
// =====================
function startServerHidden($exe, $params){
    if (!is_file($exe)) {
        return ['ok' => false, 'error' => 'Ejecutable no encontrado: ' . $exe];
    }

    $expectedExe = basename($exe);
    if (strcasecmp($expectedExe, 'valheim_server.exe') !== 0) {
        return ['ok' => false, 'error' => 'El ejecutable configurado no es valheim_server.exe'];
    }

    $cmd = 'start "" /B "' . $exe . '" ' . $params;
    @pclose(@popen($cmd, "r"));

    sleep(4);

    $pid = findNewestValheimPID($exe, $params);
    if (!$pid) {
        return ['ok' => false, 'error' => 'El servidor arrancó, pero no se pudo capturar un PID válido.'];
    }

    if (!pidExists($pid)) {
        return ['ok' => false, 'error' => 'Se detectó un PID, pero el proceso ya no existe.'];
    }

    $realExe = getProcessNameByPID($pid);
    if (!$realExe || strcasecmp($realExe, $expectedExe) !== 0) {
        return ['ok' => false, 'error' => 'El PID detectado no corresponde a ' . $expectedExe];
    }

    savePID($pid);
    return ['ok' => true, 'pid' => $pid];
}

function stopServer() {
    $pid = getSavedPID();
    if (!$pid) {
        return ['ok' => false, 'error' => 'No hay PID guardado.'];
    }

    $expectedExe = defined('SERVER_PATH') ? basename(SERVER_PATH) : 'valheim_server.exe';
    $realExe = getProcessNameByPID($pid);

    if (!$realExe || strcasecmp($realExe, $expectedExe) !== 0) {
        clearPID();
        return ['ok' => false, 'error' => 'El PID guardado ya no corresponde al servidor actual.'];
    }

    @shell_exec('taskkill /PID ' . intval($pid) . ' /F');
    sleep(2);

    if (!pidExists($pid)) {
        clearPID();
        return ['ok' => true, 'msg' => 'Servidor detenido correctamente.'];
    }

    return ['ok' => false, 'error' => 'No se pudo detener el proceso.'];
}

function getValheimProcessesForPidDetect() {
    $psCommand = 'Get-CimInstance Win32_Process | Where-Object { $_.Name -eq \"valheim_server.exe\" } | Select-Object ProcessId, Name, ExecutablePath, HandleCount, WorkingSetSize, VirtualSize, CreationDate, CommandLine | ConvertTo-Json -Compress';
    $output = @shell_exec('C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $psCommand . '"');

    if (!isset($output) || !is_string($output) || trim($output) === '') {
        return [];
    }

    $data = @json_decode(trim($output), true);
    if (!is_array($data)) {
        return [];
    }

    if (isset($data['ProcessId'])) {
        $data = [$data];
    }

    return $data;
}

function detectValheimPidAndSave() {
    $processList = getValheimProcessesForPidDetect();

    if (empty($processList)) {
        return ['ok' => false, 'error' => 'No se encontraron procesos activos de valheim_server.exe'];
    }

    $servers = getServers();
    $server = $servers[0] ?? null;

    $exePath = '';
    $params = '';
    $savedir = '';

    if (is_array($server)) {
        $exePath = $server['path'] ?? '';
        $params  = $server['params'] ?? '';

        if (preg_match('/-savedir\s+"([^"]+)"/i', $params, $m)) {
            $savedir = strtolower(str_replace('/', '\\', trim($m[1])));
        }
    }

    $realExe = $exePath && is_file($exePath)
        ? strtolower(str_replace('/', '\\', realpath($exePath) ?: $exePath))
        : '';

    // Intento 1: coincidencia exacta por ExecutablePath
    foreach ($processList as $p) {
        $pid  = intval($p['ProcessId'] ?? 0);
        $path = strtolower(str_replace('/', '\\', (string)($p['ExecutablePath'] ?? '')));

        if ($pid > 0 && $realExe && $path === $realExe) {
            savePID($pid);
            return ['ok' => true, 'pid' => $pid, 'mode' => 'ExecutablePath'];
        }
    }

    // Intento 2: coincidencia por savedir dentro del CommandLine
    foreach ($processList as $p) {
        $pid  = intval($p['ProcessId'] ?? 0);
        $cmdl = strtolower(str_replace('/', '\\', (string)($p['CommandLine'] ?? '')));

        if ($pid > 0 && $savedir && $cmdl && strpos($cmdl, $savedir) !== false) {
            savePID($pid);
            return ['ok' => true, 'pid' => $pid, 'mode' => 'savedir'];
        }
    }

    // Intento 3: tomar el más reciente (igual que lo verías en procesos_valheim.php)
    usort($processList, function($a, $b){
        return strcmp((string)($b['CreationDate'] ?? ''), (string)($a['CreationDate'] ?? ''));
    });

    $first = $processList[0] ?? null;
    $pid = intval($first['ProcessId'] ?? 0);
    if ($pid > 0) {
        savePID($pid);
        return ['ok' => true, 'pid' => $pid, 'mode' => 'first_recent'];
    }

    return ['ok' => false, 'error' => 'No se pudo determinar un PID válido'];
}



// =====================
// Router
// =====================
$action = $_GET['action'] ?? '';

switch($action){
	
case 'detect_pid':
    $result = detectValheimPidAndSave();

    if (!$result['ok']) {
        out([
            'ok' => false,
            'error' => $result['error']
        ]);
    }

    out([
        'ok'   => true,
        'pid'  => $result['pid'],
        'mode' => $result['mode'] ?? '',
        'msg'  => 'PID detectado y guardado correctamente'
    ]);
break;

    case 'get_list': {
        $file = $_GET['file'] ?? '';
        global $LISTS;
        if (!isset($LISTS[$file])) out(['ok'=>false,'error'=>'Archivo inválido']);

        if (!$SERVER_DIR_REAL) out(['ok'=>false,'error'=>'SERVER_DIR inválido']);
        $path = $SERVER_DIR_REAL . DIRECTORY_SEPARATOR . basename($file);

        if (!file_exists($path)) {
            @file_put_contents($path, '');
        }

        $pathReal = realpath($path);
        if (!$pathReal || strpos($pathReal, $SERVER_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Ruta fuera de alcance']);

        $list = safe_read_lines($pathReal);
        out($list);
    }
	case 'download_map': {
    $file = basename($_GET['file'] ?? '');
    if ($file === '') {
        http_response_code(400);
        exit('Archivo no especificado');
    }

    if (!defined('WORLDS_DIR')) {
        http_response_code(500);
        exit('WORLDS_DIR no definido');
    }

    $target = rtrim(WORLDS_DIR, '\\/') . DIRECTORY_SEPARATOR . $file;
    if (!is_file($target) || !is_readable($target)) {
        http_response_code(404);
        exit('Archivo no encontrado');
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($target) . '"');
    header('Content-Length: ' . filesize($target));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    readfile($target);
    exit;
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

    case 'get_cfg': {
        if (!$CFG_DIR_REAL) out(['ok'=>false,'error'=>'CFG_DIR inválido']);
        $rel = $_GET['rel'] ?? '';
        if ($rel === '' || !is_safe_relative_path($rel)) out(['ok'=>false,'error'=>'Falta parámetro o ruta inválida']);

        $target = $CFG_DIR_REAL . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        $targetReal = realpath($target);

        if (!$targetReal || strpos($targetReal, $CFG_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Archivo inválido']);
        if (!is_file($targetReal) || !is_readable($targetReal)) out(['ok'=>false,'error'=>'No encontrado']);

        out(['ok'=>true, 'content'=>file_get_contents($targetReal)]);
    }

    case 'save_cfg': {
        check_csrf_if_present();
        if (!$CFG_DIR_REAL) out(['ok'=>false,'error'=>'CFG_DIR inválido']);

        $rel = $_POST['rel'] ?? '';
        $content = $_POST['content'] ?? '';

        if ($rel === '' || !is_safe_relative_path($rel)) out(['ok'=>false,'error'=>'Falta parámetro o ruta inválida']);

        $target = $CFG_DIR_REAL . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        $targetReal = realpath($target);

        if (!$targetReal || strpos($targetReal, $CFG_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Archivo inválido']);
        if (!is_file($targetReal) || !is_writable($targetReal)) out(['ok'=>false,'error'=>'No se puede escribir']);

        if (file_put_contents($targetReal, $content) === false) {
            out(['ok'=>false,'error'=>'Error al guardar']);
        }

        out(['ok'=>true]);
    }

    case 'delete_cfg': {
        check_csrf_if_present();
        if (!$CFG_DIR_REAL) out(['ok'=>false,'error'=>'CFG_DIR inválido']);

        $rel = $_POST['rel'] ?? '';
        if ($rel === '' || !is_safe_relative_path($rel)) out(['ok'=>false,'error'=>'Falta parámetro o ruta inválida']);

        $target = $CFG_DIR_REAL . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
        $targetReal = realpath($target);

        if (!$targetReal || strpos($targetReal, $CFG_DIR_REAL) !== 0) out(['ok'=>false,'error'=>'Archivo inválido']);
        if (!is_file($targetReal) || !is_writable($targetReal)) out(['ok'=>false,'error'=>'No se puede eliminar']);

        if (!unlink($targetReal)) out(['ok'=>false,'error'=>'Error al eliminar']);
        out(['ok'=>true]);
    }

    case 'view_log': {
        $which = $_GET['file'] ?? '';
        $allowed = [
            'server'   => SERVER_LOG,
            'steamcmd' => STEAMCMD_LOG
        ];
        if (!isset($allowed[$which])) out(['ok'=>false,'error'=>'No permitido']);

        $log = $allowed[$which];
        if (!is_file($log) || !is_readable($log)) out(['ok'=>false,'error'=>'Log no encontrado']);

        $lines = file($log, FILE_IGNORE_NEW_LINES);
        if ($lines === false) out(['ok'=>false,'error'=>'No se pudo leer el log']);

        $count = count($lines);
        if (defined('LOG_MAX_LINES') && $count > LOG_MAX_LINES) {
            $lines = array_slice($lines, -LOG_MAX_LINES);
        }

        out(['ok'=>true, 'content'=>implode("\n", $lines)]);
    }

    case 'clear_log': {
        $which = $_POST['file'] ?? '';
        $allowed = [
            'server'   => SERVER_LOG,
            'steamcmd' => STEAMCMD_LOG
        ];
        if (!isset($allowed[$which])) out(['ok'=>false,'error'=>'No permitido']);

        $log = $allowed[$which];
        if (!is_file($log) || !is_writable($log)) out(['ok'=>false,'error'=>'Log no editable']);

        if (file_put_contents($log, '') === false) {
            out(['ok'=>false,'error'=>'No se pudo limpiar el log']);
        }

        out(['ok'=>true]);
    }

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

                    $payloadStr = substr($body, 8, -2);
                    $response .= $payloadStr;

                    if (strlen($payloadStr) === 0) break;
                    if (strlen($payloadStr) < 4000) break;
                }

                return $response;
            }

            public function login($password) {
                $this->sendPacket(3, $password);
                return true;
            }

            public function command($cmd) {
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

    case 'upload_map': {
        if (empty($_FILES['mapFile']) || $_FILES['mapFile']['error'] !== UPLOAD_ERR_OK) {
            out(['ok'=>false,'error'=>'Error de subida']);
        }

        $allowed = ['fwl','db','old'];
        $ext = strtolower(pathinfo($_FILES['mapFile']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) out(['ok'=>false,'error'=>'Extensión no permitida']);

        if (!defined('WORLDS_DIR')) out(['ok'=>false,'error'=>'WORLDS_DIR no definido']);

        $dest = rtrim(WORLDS_DIR,'\\/') . DIRECTORY_SEPARATOR . basename($_FILES['mapFile']['name']);
        if (!move_uploaded_file($_FILES['mapFile']['tmp_name'], $dest)) {
            out(['ok'=>false,'error'=>'No se pudo mover el archivo']);
        }

        out(['ok'=>true]);
    }

    case 'list_maps': {
        if (!defined('WORLDS_DIR')) out(['ok'=>false,'error'=>'WORLDS_DIR no definido']);

        $dir = realpath(WORLDS_DIR);
        if (!$dir || !is_dir($dir)) out(['ok'=>false,'error'=>'Directorio inválido']);

        $items = [];
        foreach (glob($dir.'/*.{fwl,db,old}', GLOB_BRACE) as $f) {
            $items[] = [
                'name' => basename($f),
                'size_mb' => round(filesize($f)/1048576, 2),
                'ext' => strtolower(pathinfo($f, PATHINFO_EXTENSION))
            ];
        }

        out(['ok'=>true,'items'=>$items]);
    }

    case 'delete_map': {
        $file = basename($_GET['file'] ?? '');
        if ($file === '') out(['ok'=>false,'error'=>'Archivo no especificado']);
        if (!defined('WORLDS_DIR')) out(['ok'=>false,'error'=>'WORLDS_DIR no definido']);

        $target = rtrim(WORLDS_DIR,'\\/') . DIRECTORY_SEPARATOR . $file;
        if (!is_file($target)) out(['ok'=>false,'error'=>'No encontrado']);
        if (!unlink($target)) out(['ok'=>false,'error'=>'No se pudo eliminar']);

        out(['ok'=>true]);
    }

    case 'create_backup': {
        if (!defined('WORLDS_DIR')) out(['ok'=>false,'error'=>'WORLDS_DIR no definido']);

        $src = realpath(WORLDS_DIR);
        if(!$src || !is_dir($src)) out(['ok'=>false,'error'=>'Directorio worlds_local inválido']);

        $backupDir = __DIR__ . '/backups';
        if(!is_dir($backupDir)) @mkdir($backupDir,0777,true);

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
                'size_mb'=>round(filesize($f)/1048576, 2),
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

    case 'get_servers_json':
        if (!defined('SERVERS_JSON') || !file_exists(SERVERS_JSON)) {
            out(['ok'=>false,'error'=>'No existe servers.json']);
        }
        out(['ok'=>true,'content'=>file_get_contents(SERVERS_JSON)]);
    break;

    case 'server':
        $op = $_POST['op'] ?? '';

        if ($op === 'savejson') {
            $content = $_POST['content'] ?? '';
            if (!saveServers($content)) {
                out(['ok'=>false,'error'=>'JSON inválido o no se pudo guardar']);
            }
            out(['ok'=>true,'msg'=>'Guardado']);
        }

        $servers = getServers();
        if (!$servers || !isset($servers[0])) {
            out(['ok'=>false,'error'=>'servers.json vacío o inválido']);
        }

        $srv = $servers[0];
        $exe = $srv['path'] ?? '';
        $params = $srv['params'] ?? '';

        if (!is_file($exe)) {
            out(['ok'=>false,'error'=>'Ejecutable no encontrado']);
        }

        if ($op === 'start'){
            if (isServerRunning()) {
                out(['ok'=>false,'error'=>'Servidor ya está iniciado.']);
            }

            $result = startServerHidden($exe, $params);

            if (!$result['ok']) {
                out(['ok'=>false,'error'=>$result['error']]);
            }

            out([
                'ok'=>true,
                'msg'=>"Servidor iniciado con PID " . $result['pid'],
                'pid'=>$result['pid']
            ]);
        }

        if ($op === 'stop'){
            if (!isServerRunning()) {
                clearPID();
                out(['ok'=>false,'error'=>'Servidor no está ejecutándose.']);
            }

            $result = stopServer();

            if (!$result['ok']) {
                out(['ok'=>false,'error'=>$result['error']]);
            }

            out(['ok'=>true,'msg'=>$result['msg']]);
        }

        out(['ok'=>false,'error'=>'Operación inválida']);
    break;

    case 'status_servers':
        out([
            'ok'=>true,
            'status'=>[
                'current' => isServerRunning() ? 'running' : 'stopped'
            ]
        ]);
    break;

    case 'steam_update': {
        check_csrf_if_present();

        $type = $_POST['type'] ?? '';
        if (!in_array($type, ['normal','prebeta'], true)) out(['ok'=>false,'error'=>'Tipo inválido']);
        if (!defined('STEAMCMD_EXE') || !is_file(STEAMCMD_EXE)) out(['ok'=>false,'error'=>'steamcmd.exe no encontrado']);

        if (isServerRunning()) {
            out(['ok'=>false,'error'=>'Servidor en ejecución, apágalo antes de actualizar']);
        }

        if ($type === 'prebeta') {
            $cmd = '"' . STEAMCMD_EXE . '" +login anonymous +app_update 896660 -beta public-test -betapassword yesimadebackups validate +quit';
        } else {
            $cmd = '"' . STEAMCMD_EXE . '" +login anonymous +app_update 896660 validate +quit';
        }

        @pclose(@popen('start "ValheimUpdate" cmd /c ' . $cmd, 'r'));
        out(['ok'=>true]);
    }

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

        $servers = getServers();
        $found = null;
        foreach ($servers as $s) {
            if (intval($s['id'] ?? 0) === $serverId) { $found = $s; break; }
        }
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
            'next_run_ts'   => $now + $interval,
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
            if (($j['id'] ?? '') === $id) {
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

        $servers = getServers();
        $found = null;
        foreach ($servers as $s) {
            if (intval($s['id'] ?? 0) === $serverId) { $found = $s; break; }
        }
        if (!$found) out(['ok'=>false,'error'=>'Servidor no encontrado']);

        $py = escapeshellarg(PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');
        $script = escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'rcon_alert.py');

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

        $launch = 'start "VH Alert" cmd /c ' . $cmd;
        @pclose(@popen($launch, 'r'));

        out(['ok'=>true]);
    }

    case 'schedule_restart': {
        check_csrf_if_present();

        $serverId    = intval($_POST['server_id'] ?? 0);
        $delay       = intval($_POST['delay'] ?? 0);
        $saveBefore  = intval($_POST['save_before'] ?? 0) === 1 ? 1 : 0;

        if ($serverId <= 0) out(['ok'=>false,'error'=>'ID de servidor inválido']);
        if ($delay < 0) out(['ok'=>false,'error'=>'Delay inválido']);

        $servers = getServers();
        $found = null;
        foreach ($servers as $s) {
            if (intval($s['id'] ?? 0) === $serverId) { $found = $s; break; }
        }
        if (!$found) out(['ok'=>false,'error'=>'Servidor no encontrado']);

        $py = escapeshellarg(PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');
        $script = escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'rcon_restart.py');

        $args = [
            '--host', RCON_HOST,
            '--port', (string)RCON_PORT,
            '--password', RCON_PASS,
            '--delay', (string)$delay,
            '--servers-json', SERVERS_JSON,
            '--server-id', (string)$serverId,
            '--exe-name', defined('SERVER_PATH') ? basename(SERVER_PATH) : 'valheim_server.exe',
            '--save-before', (string)$saveBefore
        ];

        $parts = [];
        foreach ($args as $a) { $parts[] = escapeshellarg($a); }
        $cmd = $py . ' ' . $script . ' ' . implode(' ', $parts);

        $launch = 'start "VH Cron Restart" cmd /c ' . $cmd;
        @pclose(@popen($launch, 'r'));

        out(['ok'=>true]);
    }

    default:
        out(['ok'=>false,'error'=>'Acción inválida']);
}
