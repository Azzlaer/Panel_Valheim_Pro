<?php
require  'config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$configPath = __DIR__ . '/../config.php';
$mensaje = '';
$errores = [];

// fallback para list arrays
if (!isset($LISTS) || !is_array($LISTS)) {
    $LISTS = [
        "adminlist.txt"     => "👑 Administradores",
        "bannedlist.txt"    => "🚫 Baneados",
        "permittedlist.txt" => "✅ Permitidos"
    ];
}

function cfg($name, $default = '') {
    return defined($name) ? constant($name) : $default;
}

// Procesar guardado
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
        foreach ($fields as $f) { $data[$f] = trim($_POST[$f] ?? ""); }

        foreach (["ADMIN_USER","ADMIN_PASS","SERVER_BASE","SERVER_DIR","SERVER_PATH"] as $f) {
            if ($data[$f] === "") $errores[] = "$f no puede estar vacío.";
        }

        if (!$errores) {
            $php = "<?php\n";
            $php .= "// Archivo generado desde panel\n\n";

            foreach ($data as $key => $val) {
                $php .= "define('$key', " . var_export($val, true) . ");\n";
            }

            $php .= "\$LISTS = " . var_export($LISTS, true) . ";\n\n";

            $php .= "if (session_status() === PHP_SESSION_NONE) session_start();\n";
            $php .= "if (empty(\$_SESSION['csrf_token'])) \$_SESSION['csrf_token']=bin2hex(random_bytes(32));\n";

            if (file_put_contents($configPath, $php) !== false)
                $mensaje = "✔ Configuración guardada exitosamente.";
            else
                $errores[] = "❌ Error al escribir config.php";
        }
    }
}

// Cargar valores actuales
$cfg=[];
$names=[
    "ADMIN_USER","ADMIN_PASS","SERVER_IP","SERVER_PORT",
    "SERVER_BASE","SERVER_DIR","SERVER_PATH","SERVER_STATUS_MODE","SERVER_NAME",
    "LOG_MAX_LINES","WORLDS_DIR","VALHEIM_EXE","PLUGINS_DIR","CFG_DIR",
    "SERVER_LOG","STEAMCMD_LOG","STEAMCMD_EXE","STEAMCMD_PATH","RCON_SEND_CMD",
    "RCON_HOST","RCON_PORT","RCON_PASS"
];
foreach ($names as $n) { $cfg[$n]=cfg($n); }
?><style type="text/css">
<!--
body,td,th {
	color: #FFFFFF;
}
-->
</style>

<div class="container mt-4">

    <?php if($mensaje): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if($errores): ?>
        <div class="alert alert-danger"><ul class="mb-0">
            <?php foreach($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <!-- CREDENCIALES -->
        <div class="card bg-dark border-light mb-3">
            <div class="card-header">🔐 Credenciales de Panel</div>
            <div class="card-body row g-3">
                <?php foreach(["ADMIN_USER","ADMIN_PASS"] as $f): ?>
                <div class="col-md-6">
                    <label><?= $f ?></label>
                    <input class="form-control" name="<?= $f ?>" value="<?= htmlspecialchars($cfg[$f]) ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RED -->
        <div class="card bg-dark border-light mb-3">
            <div class="card-header">🌐 Configuración de Red</div>
            <div class="card-body row g-3">
                <?php foreach(["SERVER_IP","SERVER_PORT"] as $f): ?>
                <div class="col-md-6">
                    <label><?= $f ?></label>
                    <input class="form-control" name="<?= $f ?>" value="<?= htmlspecialchars($cfg[$f]) ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RUTAS -->
        <div class="card bg-dark border-light mb-3">
            <div class="card-header">📂 Rutas del Servidor</div>
            <div class="card-body row g-3">
                <?php foreach(["SERVER_BASE","SERVER_DIR","SERVER_PATH"] as $f): ?>
                <div class="col-md-4">
                    <label><?= $f ?></label>
                    <input class="form-control" name="<?= $f ?>" value="<?= htmlspecialchars($cfg[$f]) ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ESTADO Y NOMBRE -->
        <div class="card bg-dark border-light mb-3">
            <div class="card-header">📌 Estado / Nombre</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label>SERVER_STATUS_MODE</label>
                    <select name="SERVER_STATUS_MODE" class="form-select">
                        <?php foreach(["none"=>"Sin Comprobación","folder"=>"Por Carpeta","port"=>"Por Puerto"] as $val=>$txt): ?>
                        <option value="<?= $val ?>" <?= $cfg["SERVER_STATUS_MODE"]===$val?"selected":"" ?>>
                            <?= $txt ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>SERVER_NAME</label>
                    <input class="form-control" name="SERVER_NAME" value="<?= htmlspecialchars($cfg["SERVER_NAME"]) ?>">
                </div>
                <div class="col-md-4">
                    <label>LOG_MAX_LINES</label>
                    <input class="form-control" type="number" name="LOG_MAX_LINES" value="<?= htmlspecialchars($cfg["LOG_MAX_LINES"]) ?>">
                </div>
            </div>
        </div>

        <!-- DIRECTORIOS / LOGS -->
        <div class="card bg-dark border-light mb-3">
            <div class="card-header">📁 Carpetas y Logs</div>
            <div class="card-body row g-3">
                <?php foreach(["WORLDS_DIR","VALHEIM_EXE","PLUGINS_DIR","CFG_DIR","SERVER_LOG","STEAMCMD_LOG","STEAMCMD_EXE","STEAMCMD_PATH","RCON_SEND_CMD"] as $f): ?>
                <div class="col-md-6">
                    <label><?= $f ?></label>
                    <input class="form-control" name="<?= $f ?>" value="<?= htmlspecialchars($cfg[$f]) ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- RCON -->
        <div class="card bg-dark border-light mb-3">
            <div class="card-header">📡 RCON</div>
            <div class="card-body row g-3">
                <?php foreach(["RCON_HOST","RCON_PORT","RCON_PASS"] as $f): ?>
                <div class="col-md-4">
                    <label><?= $f ?></label>
                    <input class="form-control" name="<?= $f ?>" value="<?= htmlspecialchars($cfg[$f]) ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button class="btn btn-success w-100 mb-5">💾 Guardar Configuración</button>
    </form>
</div>

<script>
function reloadSetup() {
  fetch('pages/setup_config.php')
    .then(r => r.text())
    .then(html => { document.getElementById('main').innerHTML = html; });
}
</script>
