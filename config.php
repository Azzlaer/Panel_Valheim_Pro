<?php
// =====================
// CONFIGURACIÓN BÁSICA
// =====================

// Credenciales de acceso al panel
define('ADMIN_USER', 'Azzlaer');          // Usuario de login
define('ADMIN_PASS', '35027595');  // Contraseña de login

// =====================
// RUTAS DEL SERVIDOR
// =====================

// Carpeta de backups (puede estar dentro del proyecto o en otra unidad)
define('BACKUP_DIR', __DIR__ . '/backups'); 
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0777, true);
}


// Define carpeta de Mundos de Valheim (comumente esta en %AppData% 
// pero yo modifique el arranque del servidor para alojarlo en la misma carpeta donde se ejecuta el servidor
define('WORLDS_DIR', 'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\server01\\worlds_local');

// Ejecutable del servidor Valheim
define('VALHEIM_EXE', 'valheim_server.exe');

// Carpeta de plugins
define('PLUGINS_DIR', 'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\BepInEx\\plugins');

// Carpeta de archivos CFG
define('CFG_DIR', 'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\BepInEx\\config');

// Carpeta donde están adminlist, bannedlist y permittedlist
define('SERVER_DIR', 'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\server01\\');

// Logs
define('SERVER_LOG', 'C:\\Servidores\\Steam\\steamapps\\common\\Valheim dedicated server\\server1.txt');
define('STEAMCMD_LOG', 'C:\\Servidores\\Steam\\logs\\console_log.txt');

// Ruta de servers.json
define('SERVERS_JSON', __DIR__ . DIRECTORY_SEPARATOR . 'servers.json');

// SteamCMD
define('STEAMCMD_EXE', 'C:\\Servidores\\Steam\\steamcmd.exe');

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
