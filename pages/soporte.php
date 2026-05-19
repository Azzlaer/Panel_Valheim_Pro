<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}
?>

<style>
.support-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(59,130,246,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 28px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.support-wrap .panel-hero h1 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.support-wrap .panel-hero p {
    margin: 10px 0 0;
    color: #9ca3af;
    line-height: 1.7;
}

.support-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.support-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.support-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.support-wrap .stat-value {
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    line-height: 1.4;
}

.support-wrap .section-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
    margin-bottom: 22px;
}

.support-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.support-wrap .section-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #fff;
}

.support-wrap .section-body {
    padding: 20px;
    color: #d1d5db;
    line-height: 1.75;
}

.support-wrap .feature-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 14px;
}

.support-wrap .feature-item {
    background: rgba(255,255,255,.02);
    border: 1px solid rgba(255,255,255,.05);
    border-radius: 16px;
    padding: 16px;
}

.support-wrap .feature-item strong {
    color: #fff;
}

.support-wrap ul.clean-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.support-wrap ul.clean-list li {
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,.06);
}

.support-wrap ul.clean-list li:last-child {
    border-bottom: none;
}

.support-wrap pre.code-block {
    background: #020617;
    color: #e5e7eb;
    padding: 18px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,.08);
    overflow-x: auto;
    margin: 0;
    font-family: Consolas, Monaco, monospace;
    font-size: 13px;
    line-height: 1.6;
}

.support-wrap a {
    color: #93c5fd;
    text-decoration: none;
}

.support-wrap a:hover {
    color: #bfdbfe;
    text-decoration: underline;
}

.support-wrap .footer-note {
    color: #9ca3af;
    text-align: center;
    padding: 10px 0 20px;
    font-size: 13px;
}

@media (max-width: 991px) {
    .support-wrap .stats-grid,
    .support-wrap .feature-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .support-wrap .stats-grid,
    .support-wrap .feature-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 support-wrap">

    <div class="panel-hero">
        <h1>🆘 Soporte &amp; Documentación</h1>
        <p>
            Bienvenido al <strong>Panel de Administración de Valheim Pro</strong>.  
            Este proyecto fue desarrollado junto a <strong>ChatGPT</strong> y <strong>Azzlaer</strong> para
            administrar servidores dedicados de Valheim en Windows con una arquitectura moderna,
            modular y centralizada.
        </p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Proyecto</div>
            <div class="stat-value">Valheim Pro Panel</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Tecnología base</div>
            <div class="stat-value">PHP + Bootstrap + AJAX</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Automatización</div>
            <div class="stat-value">Python + RCON</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Autoría</div>
            <div class="stat-value">Azzlaer + ChatGPT</div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h2>✨ Características principales</h2>
        </div>
        <div class="section-body">
            <div class="feature-grid">
                <div class="feature-item">🔑 <strong>Login seguro</strong> con usuario y contraseña configurables en <code>config.php</code>.</div>
                <div class="feature-item">🖥️ <strong>Gestión de servidores</strong> para iniciar, detener y actualizar instancias Valheim.</div>
                <div class="feature-item">⚙️ <strong>Edición de CFG</strong> con modal y resaltado de sintaxis.</div>
                <div class="feature-item">📊 <strong>Plugins</strong> con subida, habilitación, deshabilitación y eliminación.</div>
                <div class="feature-item">🗺️ <strong>Mapas</strong> para gestionar archivos <code>.FWL</code>, <code>.DB</code> y <code>.OLD</code>.</div>
                <div class="feature-item">📂 <strong>Listas</strong> para administrar <code>adminlist.txt</code>, <code>bannedlist.txt</code> y <code>permittedlist.txt</code>.</div>
                <div class="feature-item">📜 <strong>Visor de logs</strong> con lectura en tiempo real del servidor y SteamCMD.</div>
                <div class="feature-item">🛰️ <strong>RCON</strong> con consola integrada para comandos remotos.</div>
                <div class="feature-item">⏱️ <strong>Cron Jobs</strong> para reinicios programados y flujos automáticos.</div>
                <div class="feature-item">🔔 <strong>Alertas</strong> para mensajes RCON repetitivos y personalizados.</div>
                <div class="feature-item">🗂️ <strong>Respaldos</strong> de <code>worlds_local</code> con creación y descarga.</div>
                <div class="feature-item">📈 <strong>Operación centralizada</strong> de múltiples módulos desde una única interfaz web.</div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h2>⚙️ Tecnologías usadas</h2>
        </div>
        <div class="section-body">
            <ul class="clean-list">
                <li><strong>PHP 8.x</strong> + sesiones para autenticación y backend AJAX.</li>
                <li><strong>Bootstrap 5</strong> para la interfaz web y diseño responsivo.</li>
                <li><strong>JavaScript (Fetch / AJAX)</strong> para acciones dinámicas sin recargar la página.</li>
                <li><strong>CodeMirror</strong> para edición avanzada de archivos de configuración.</li>
                <li><strong>Python 3</strong> para automatizaciones como reinicios y alertas RCON.</li>
            </ul>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h2>📂 Estructura principal del proyecto</h2>
        </div>
        <div class="section-body">
            <pre class="code-block">valheim/
├─ config.php           # Configuración de rutas y credenciales
├─ index.php            # Login
├─ dashboard.php        # Contenedor principal del panel
├─ api.php              # Endpoints AJAX (listas, logs, cfg, rcon, backups, etc.)
├─ pages/               # Secciones cargadas vía AJAX
│  ├─ servers.php
│  ├─ backups.php
│  ├─ plugins.php
│  ├─ cfg.php
│  ├─ lists.php
│  ├─ logs.php
│  ├─ update.php
│  ├─ rcon.php
│  ├─ procesos_valheim.php
│  └─ soporte.php</pre>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h2>🔗 Repositorio oficial</h2>
        </div>
        <div class="section-body">
            <p>
                Para actualizaciones, reportes de bugs o nuevas ideas visita:
            </p>
            <p>
                <a href="https://github.com/Azzlaer/Panel_Valheim_Pro" target="_blank" rel="noopener noreferrer">
                    https://github.com/Azzlaer/Panel_Valheim_Pro
                </a>
            </p>
        </div>
    </div>

    <div class="footer-note">
        © <?= date('Y') ?> Panel Valheim Pro — Desarrollado junto a Azzlaer y ChatGPT OpenAI
    </div>
</div>