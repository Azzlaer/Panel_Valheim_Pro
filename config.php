<?php
// =====================
// CONFIGURACIÓN BÁSICA
// =====================

// Credenciales de acceso al panel
define('ADMIN_USER', 'Azzlaer');          // Usuario de login
define('ADMIN_PASS', '35027595');  // Contraseña de login

// ===============================
// CONFIGURACIÓN DE RED DEL SERVIDOR
// ===============================
define('SERVER_IP', '0.0.0.0');  // o la IP LAN o pública de tu máquina dedicada
define('SERVER_PORT', 2457);       // puerto del servidor Valheim asociado a este panel


// Carpeta raíz donde está el ejecutable
define('SERVER_BASE', 'D:\\Servidores\\Valheim\\1\\');

// Carpeta específica donde se guardan los datos del servidor (listas, mundos, etc.)
define('SERVER_DIR', SERVER_BASE . 'server1\\');

// Ruta completa al ejecutable del servidor
define('SERVER_PATH', SERVER_BASE . 'valheim_server.exe');

// Ruta al archivo servers.json
define('SERVERS_JSON', __DIR__ . DIRECTORY_SEPARATOR . 'servers.json');

// Método de comprobación del estado del servidor
// Valores posibles: 'folder', 'port', 'none'
define('SERVER_STATUS_MODE', 'none'); // o 'port' o 'none'

// =====================
// RUTAS DEL SERVIDOR
// =====================

// Nombre opcional del servidor (para mostrar en el panel)
define('SERVER_NAME', 'Servidor #1');

// Número máximo de líneas de log a mostrar (ej. 2000)
define('LOG_MAX_LINES', 50);

// Carpeta de mundos locales
define('WORLDS_DIR', 'D:\\Servidores\\Valheim\\1\\server1\\worlds_local');

// Ejecutable del servidor Valheim
define('VALHEIM_EXE', 'valheim_server.exe');

// Carpeta de plugins
define('PLUGINS_DIR', 'D:\Servidores\Valheim\1\BepInEx\plugins');

// Carpeta de archivos CFG
define('CFG_DIR', 'D:\\Servidores\\Valheim\\1\\BepInEx\\config');

// Logs
define('SERVER_LOG', 'D:\\Servidores\\Valheim\\1\\server1\\server1.txt');
define('STEAMCMD_LOG', 'D:\\Servidores\\Steam\\logs\\console_log.txt');

// SteamCMD
define('STEAMCMD_EXE', 'D:\\Servidores\\Steam\\steamcmd.exe');
define('STEAMCMD_PATH', 'D:\\Servidores\\Steam\\steamcmd.exe');

// Ruta al ejecutable RCON o script que envía los comandos
define('RCON_SEND_CMD', 'C:\\Servidores\\Herramientas\\send_rcon.cmd');

// =====================
// LISTAS ADMIN / BAN / PERMITIDOS
// =====================
$LISTS = [
    "adminlist.txt"     => "👑 Administradores",
    "bannedlist.txt"    => "🚫 Baneados",
    "permittedlist.txt" => "✅ Permitidos"
];

// =====================
// RCON (si lo usas)
// =====================
define('RCON_HOST', '127.0.0.1');
define('RCON_PORT', 2974);
define('RCON_PASS', '35027595');

// =====================
// SESIÓN Y CSRF
// =====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
