<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}
?>
<div class="container mt-4 text-light">
    <h1 class="mb-4">🆘 Soporte &amp; Documentación</h1>

    <p>
        Bienvenido al <strong>Panel de Administración de Valheim Pro</strong>.  
        Este panel fue desarrollado en conjunto con <strong>ChatGPT</strong> y <strong>Azzlaer</strong> para
        administrar servidores dedicados de Valheim en Windows de manera moderna y centralizada.
    </p>

    <hr class="border-secondary">

    <h2>✨ Características principales</h2>
    <ul>
        <li>🔑 <strong>Login seguro</strong> con usuario y contraseña configurables en <code>config.php</code>.</li>
        <li>🖥️ <strong>Gestión de servidores</strong>: iniciar, detener y actualizar el servidor Valheim (normal o pre-beta).</li>
        <li>⚙️ <strong>Edición de archivos CFG</strong> con editor en modal y resaltado de sintaxis.</li>
        <li>📊 <strong>Plugins</strong>: subir DLL con barra de progreso, eliminar, deshabilitar/habilitar.</li>
        <li>🗺️ <strong>Mapas (worlds_local)</strong>: subir/gestionar archivos .FWL, .DB y .OLD.</li>
        <li>📂 <strong>Listas</strong>: administración de <code>adminlist.txt</code>, <code>bannedlist.txt</code> y <code>permittedlist.txt</code>.</li>
        <li>📜 <strong>Visor de Logs</strong> en tiempo real para logs del servidor y de SteamCMD.</li>
        <li>🛰️ <strong>RCON</strong>: enviar comandos y ver respuesta en consola estilo terminal.</li>
        <li>⏱️ <strong>Crons</strong>: programar reinicios automáticos con avisos por RCON y guardado previo.</li>
        <li>🔔 <strong>Alertas</strong>: envíos de mensajes RCON personalizados y repetitivos.</li>
    </ul>

    <hr class="border-secondary">

    <h2>⚙️ Tecnologías usadas</h2>
    <ul>
        <li>PHP 8.x + sesiones para autenticación.</li>
        <li>Bootstrap 5 para el front-end y diseño responsivo.</li>
        <li>AJAX/Fetch para carga dinámica de secciones y acciones (sin recargar la página completa).</li>
        <li>CodeMirror para edición de CFG con resaltado de sintaxis.</li>
        <li>JavaScript/Fetch para uploads con barra de progreso y manejo de acciones (eliminar, habilitar, etc.).</li>
    </ul>

    <hr class="border-secondary">

    <h2>📂 Estructura de carpetas principal</h2>
    <pre class="bg-dark p-3 rounded text-light">
valheim/
├─ config.php           # Configuración de rutas y credenciales
├─ index.php            # Login
├─ dashboard.php        # Contenedor principal del panel
├─ api.php              # Endpoints AJAX (listas, logs, cfg, etc.)
├─ upload_plugin.php    # Subida de plugins
├─ plugin_actions.php   # Acciones (eliminar / deshabilitar plugins)
├─ maps_upload.php      # Subida de mundos
├─ maps_actions.php     # Acciones sobre mundos
└─ pages/               # Secciones cargadas vía AJAX
   ├─ servers.php
   ├─ plugins.php
   ├─ cfg.php
   ├─ lists.php
   ├─ logs.php
   ├─ update.php
   ├─ rcon.php
   ├─ crons.php
   ├─ alerts.php
   └─ soporte.php
    </pre>

    <hr class="border-secondary">

    <h2>🔗 Repositorio oficial</h2>
    <p>
        Para actualizaciones, reportes de bugs o nuevas ideas visita:<br>
        <a href="https://github.com/Azzlaer/Panel_Valheim_Pro" target="_blank">
            https://github.com/Azzlaer/Panel_Valheim_Pro
        </a>
    </p>

    <p class="mt-4 text-muted">
        © <?= date('Y') ?> Panel Valheim Pro — Desarrollado junto a Azzlaer.
    </p>
</div>
