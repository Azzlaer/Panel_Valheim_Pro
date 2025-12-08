<?php
require_once __DIR__ . "/config.php";

$pidFile = __DIR__ . "/server.pid";

function getPID() {
    global $pidFile;
    return file_exists($pidFile) ? intval(trim(file_get_contents($pidFile))) : null;
}
function savePID($pid) { global $pidFile; file_put_contents($pidFile, intval($pid)); }
function clearPID() { global $pidFile; if(file_exists($pidFile)) unlink($pidFile); }

// Buscar PID basado en EXE + carpeta real
function findPIDByFolder($exePath) {
    $workDir = dirname($exePath);
    $exeName = basename($exePath);

    // Windows 11 reliable PID detection
    $output = shell_exec("tasklist /FI \"IMAGENAME eq $exeName\" /V");

    if (!$output) return null;

    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        // Asegurar que la ruta del working dir se vea
        if (stripos($line, $exeName) !== false && stripos($line, basename($workDir)) !== false) {
            if (preg_match('/\s+(\d+)\s+/', $line, $m)) {
                return intval($m[1]);
            }
        }
    }
    return null;
}

// Iniciar
function startServer($exe, $params) {
    if (!file_exists($exe)) return "❌ No existe el ejecutable.";

    $cmd = "start /B \"VALHEIM\" \"$exe\" $params";
    pclose(popen($cmd, "r"));

    sleep(5);

    $pid = findPIDByFolder($exe);
    if ($pid) {
        savePID($pid);
        return "✔ Servidor iniciado - PID detectado: $pid";
    }
    return "⚠ Servidor iniciado, pero Windows no reportó PID (prueba otra vez).";
}

// Detener
function stopServer() {
    $pid = getPID();
    if (!$pid) return "⚠ No hay PID guardado.";

    shell_exec("taskkill /PID $pid /F");
    sleep(2);

    $check = shell_exec("tasklist /FI \"PID eq $pid\"");
    if (strpos($check, (string)$pid) === false) {
        clearPID();
        return "✔ Proceso detenido correctamente.";
    }

    return "❌ No se pudo detener.";
}

// POST handler
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $servers = json_decode(file_get_contents(SERVERS_JSON), true) ?: [];
    if (empty($servers)) exit("⚠ No hay servidores configurados.");

    $srv = $servers[0];
    $exe = $srv['path'];
    $params = $srv['params'];

    echo ($_POST['action'] === "start")
        ? startServer($exe, $params)
        : stopServer();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>TEST CONTROL</title>
<style>
body { background:#111;color:#eee;font-family:Arial;text-align:center;padding-top:40px; }
button { padding:12px 25px;margin:10px;background:#333;border:none;font-size:18px;cursor:pointer;border-radius:6px;color:white; }
.start{ background:#28a745; }
.stop{ background:#dc3545; }
</style>
</head>
<body>

<h2>⚙ TEST SIN POWERSHELL — DETECCIÓN PID WINDOWS 11</h2>

<form method="POST">
    <button class="start" name="action" value="start">🚀 INICIAR</button>
    <button class="stop" name="action" value="stop">🛑 DETENER</button>
</form>

<?php
echo "<p>PID actual: " . (getPID() ?: "Ninguno") . "</p>";
?>

</body>
</html>
