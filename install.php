<?php
require_once __DIR__ . '/config.php';

$configPath = __DIR__ . '/config.php';
$mensaje = '';
$errores = [];

// Seguridad de acceso al instalador
$installed = defined('PANEL_INSTALLED') ? PANEL_INSTALLED : false;
$allowReinstall = defined('ALLOW_REINSTALL') ? ALLOW_REINSTALL : false;

// Si ya fue instalado y no está permitida la reinstalación, bloquear
if ($installed && !$allowReinstall) {
    http_response_code(403);
    exit('El sistema ya fue instalado. Para volver a ejecutar el instalador, habilita ALLOW_REINSTALL en config.php');
}

// Asegurar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// fallback para LISTS
if (!isset($LISTS) || !is_array($LISTS)) {
    $LISTS = [
        "adminlist.txt"     => "👑 Administradores",
        "bannedlist.txt"    => "🚫 Baneados",
        "permittedlist.txt" => "✅ Permitidos"
    ];
}

function cfg_install($name, $default = '') {
    return defined($name) ? constant($name) : $default;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrf = $_POST["csrf"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $csrf)) {
        $errores[] = "Token CSRF inválido.";
    } else {
        $fields = [
            "ADMIN_USER","ADMIN_PASS","SERVER_IP","SERVER_PORT",
            "SERVER_BASE","SERVER_DIR","SERVER_PATH","SERVER_STATUS_MODE","SERVER_NAME",
            "LOG_MAX_LINES","WORLDS_DIR","VALHEIM_EXE","PLUGINS_DIR","CFG_DIR",
            "SERVER_LOG","STEAMCMD_LOG","STEAMCMD_EXE","STEAMCMD_PATH","RCON_SEND_CMD",
            "RCON_HOST","RCON_PORT","RCON_PASS"
        ];

        $data = [];
        foreach ($fields as $f) {
            $data[$f] = trim($_POST[$f] ?? "");
        }

        foreach (["ADMIN_USER","ADMIN_PASS","SERVER_BASE","SERVER_DIR","SERVER_PATH"] as $f) {
            if ($data[$f] === "") {
                $errores[] = "$f no puede estar vacío.";
            }
        }

        if (!$errores) {
            $php  = "<?php\n";
            $php .= "// =====================================\n";
            $php .= "// Archivo generado por install.php\n";
            $php .= "// =====================================\n\n";

            // Estado de instalación
            $php .= "define('PANEL_INSTALLED', true);\n";
            $php .= "define('ALLOW_REINSTALL', false);\n\n";

            // Configuración principal
            foreach ($data as $key => $val) {
                if (in_array($key, ['SERVER_PORT','LOG_MAX_LINES','RCON_PORT'], true)) {
                    $php .= "define('$key', " . intval($val) . ");\n";
                } else {
                    $php .= "define('$key', " . var_export($val, true) . ");\n";
                }
            }

            $php .= "\n";
            $php .= '$LISTS = ' . var_export($LISTS, true) . ";\n\n";

            $php .= "if (session_status() === PHP_SESSION_NONE) {\n";
            $php .= "    session_start();\n";
            $php .= "}\n";
            $php .= "if (empty(\$_SESSION['csrf_token'])) {\n";
            $php .= "    \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));\n";
            $php .= "}\n";

            if (file_put_contents($configPath, $php) !== false) {
                $mensaje = "✅ Instalación completada correctamente.";
            } else {
                $errores[] = "❌ No se pudo escribir config.php";
            }
        }
    }
}

// valores por defecto / actuales
$cfg = [];
$names = [
    "ADMIN_USER","ADMIN_PASS","SERVER_IP","SERVER_PORT",
    "SERVER_BASE","SERVER_DIR","SERVER_PATH","SERVER_STATUS_MODE","SERVER_NAME",
    "LOG_MAX_LINES","WORLDS_DIR","VALHEIM_EXE","PLUGINS_DIR","CFG_DIR",
    "SERVER_LOG","STEAMCMD_LOG","STEAMCMD_EXE","STEAMCMD_PATH","RCON_SEND_CMD",
    "RCON_HOST","RCON_PORT","RCON_PASS"
];
foreach ($names as $n) {
    $cfg[$n] = cfg_install($n);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Instalación del Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{
            background:#111;
            color:#eee;
            font-family:Arial, sans-serif;
            margin:0;
            padding:30px;
        }
        .wrap{
            max-width:1100px;
            margin:0 auto;
        }
        .card{
            background:#1c1c1c;
            border:1px solid #444;
            border-radius:10px;
            margin-bottom:20px;
            overflow:hidden;
        }
        .card-header{
            background:#2a2a2a;
            padding:12px 16px;
            font-weight:bold;
        }
        .card-body{
            padding:16px;
            display:grid;
            grid-template-columns:repeat(2,1fr);
            gap:14px;
        }
        .full{ grid-column:1 / -1; }
        label{
            display:block;
            margin-bottom:6px;
            color:#bbb;
            font-size:14px;
        }
        input, select{
            width:100%;
            box-sizing:border-box;
            padding:10px;
            border-radius:6px;
            border:1px solid #555;
            background:#0f0f0f;
            color:#fff;
        }
        .alert{
            padding:12px 14px;
            border-radius:8px;
            margin-bottom:15px;
        }
        .ok{ background:#16361f; border:1px solid #2f7d46; }
        .err{ background:#3a1616; border:1px solid #8d3434; }
        .submit{
            width:100%;
            padding:14px;
            border:none;
            border-radius:8px;
            background:#198754;
            color:white;
            font-size:16px;
            cursor:pointer;
        }
        ul{ margin:0; padding-left:20px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>⚙ Instalación del Panel Valheim</h1>
    <p>Completa la configuración inicial. Después de guardar, el instalador quedará bloqueado automáticamente.</p>

    <?php if ($mensaje): ?>
        <div class="alert ok"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($errores): ?>
        <div class="alert err">
            <ul>
                <?php foreach ($errores as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="card">
            <div class="card-header">🔐 Credenciales del panel</div>
            <div class="card-body">
                <div>
                    <label>ADMIN_USER</label>
                    <input name="ADMIN_USER" value="<?= htmlspecialchars($cfg['ADMIN_USER']) ?>">
                </div>
                <div>
                    <label>ADMIN_PASS</label>
                    <input name="ADMIN_PASS" value="<?= htmlspecialchars($cfg['ADMIN_PASS']) ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">🌐 Red del servidor</div>
            <div class="card-body">
                <div>
                    <label>SERVER_IP</label>
                    <input name="SERVER_IP" value="<?= htmlspecialchars($cfg['SERVER_IP']) ?>">
                </div>
                <div>
                    <label>SERVER_PORT</label>
                    <input name="SERVER_PORT" value="<?= htmlspecialchars($cfg['SERVER_PORT']) ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">📂 Rutas principales</div>
            <div class="card-body">
                <div>
                    <label>SERVER_BASE</label>
                    <input name="SERVER_BASE" value="<?= htmlspecialchars($cfg['SERVER_BASE']) ?>">
                </div>
                <div>
                    <label>SERVER_DIR</label>
                    <input name="SERVER_DIR" value="<?= htmlspecialchars($cfg['SERVER_DIR']) ?>">
                </div>
                <div class="full">
                    <label>SERVER_PATH</label>
                    <input name="SERVER_PATH" value="<?= htmlspecialchars($cfg['SERVER_PATH']) ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">📌 Estado / Nombre</div>
            <div class="card-body">
                <div>
                    <label>SERVER_STATUS_MODE</label>
                    <select name="SERVER_STATUS_MODE">
                        <option value="none"   <?= $cfg['SERVER_STATUS_MODE']==='none'?'selected':'' ?>>Sin comprobación</option>
                        <option value="folder" <?= $cfg['SERVER_STATUS_MODE']==='folder'?'selected':'' ?>>Por carpeta</option>
                        <option value="port"   <?= $cfg['SERVER_STATUS_MODE']==='port'?'selected':'' ?>>Por puerto</option>
                    </select>
                </div>
                <div>
                    <label>SERVER_NAME</label>
                    <input name="SERVER_NAME" value="<?= htmlspecialchars($cfg['SERVER_NAME']) ?>">
                </div>
                <div>
                    <label>LOG_MAX_LINES</label>
                    <input name="LOG_MAX_LINES" value="<?= htmlspecialchars($cfg['LOG_MAX_LINES']) ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">📁 Carpetas / Logs / SteamCMD</div>
            <div class="card-body">
                <?php foreach ([
                    "WORLDS_DIR","VALHEIM_EXE","PLUGINS_DIR","CFG_DIR",
                    "SERVER_LOG","STEAMCMD_LOG","STEAMCMD_EXE","STEAMCMD_PATH","RCON_SEND_CMD"
                ] as $f): ?>
                    <div class="<?= in_array($f, ['SERVER_LOG','STEAMCMD_LOG','STEAMCMD_EXE','STEAMCMD_PATH','RCON_SEND_CMD'], true) ? 'full' : '' ?>">
                        <label><?= $f ?></label>
                        <input name="<?= $f ?>" value="<?= htmlspecialchars($cfg[$f]) ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">📡 RCON</div>
            <div class="card-body">
                <div>
                    <label>RCON_HOST</label>
                    <input name="RCON_HOST" value="<?= htmlspecialchars($cfg['RCON_HOST']) ?>">
                </div>
                <div>
                    <label>RCON_PORT</label>
                    <input name="RCON_PORT" value="<?= htmlspecialchars($cfg['RCON_PORT']) ?>">
                </div>
                <div>
                    <label>RCON_PASS</label>
                    <input name="RCON_PASS" value="<?= htmlspecialchars($cfg['RCON_PASS']) ?>">
                </div>
            </div>
        </div>

        <button class="submit" type="submit">💾 Completar instalación</button>
    </form>
</div>
</body>
</html>