<?php
require_once __DIR__ . "/../config.php";

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// PID file que usa el panel
define("PID_FILE", __DIR__ . "/../server.pid");

// ==== Detectar si el servidor está online basado en PID guardado ====
function isServerOnlineByPID() {
    return file_exists(PID_FILE) && intval(trim(file_get_contents(PID_FILE))) > 0;
}

// ==== Cargar lista de servidores desde JSON ====
$servers = [];
if (file_exists(SERVERS_JSON)) {
    $servers = json_decode(file_get_contents(SERVERS_JSON), true) ?: [];
}

$online = isServerOnlineByPID();
$totalServers = count($servers);
$currentPid = file_exists(PID_FILE) ? intval(trim(file_get_contents(PID_FILE))) : 0;
?>

<style>
.servers-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(59,130,246,.15), rgba(17,24,39,.6));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.servers-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.servers-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}

.servers-wrap .hero-actions .btn {
    border-radius: 12px;
    font-weight: 600;
}

.servers-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.servers-wrap .stat-card {
    background: rgba(17,24,39,.92);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.servers-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.servers-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}

.servers-wrap .servers-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
}

.servers-wrap .servers-card-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.servers-wrap .servers-card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.servers-wrap .servers-card-header small {
    color: #9ca3af;
}

.servers-wrap table {
    margin-bottom: 0;
}

.servers-wrap .table-dark {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255,255,255,.02);
    --bs-table-hover-bg: rgba(255,255,255,.035);
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.06);
}

.servers-wrap .table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    font-weight: 700;
    border-bottom-width: 1px;
    padding-top: 14px;
    padding-bottom: 14px;
}

.servers-wrap .server-name {
    font-weight: 700;
    color: #fff;
}

.servers-wrap .server-path,
.servers-wrap .server-params {
    color: #cbd5e1;
    font-size: 13px;
}

.servers-wrap .server-params {
    display: block;
    max-width: 100%;
    white-space: normal;
    word-break: break-word;
    line-height: 1.45;
}

.servers-wrap .status-badge {
    border-radius: 999px;
    font-weight: 700;
    padding: 8px 12px;
    font-size: 12px;
}

.servers-wrap .btn-action {
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 12px;
}

.servers-wrap .json-modal textarea {
    min-height: 460px;
    font-family: Consolas, monospace;
    font-size: 14px;
    white-space: pre;
    border-radius: 14px;
}

.servers-wrap .json-tip {
    color: #9ca3af;
    font-size: 13px;
}

@media (max-width: 991px) {
    .servers-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 servers-wrap">

    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>🖥️ Administración de Servidor</h2>
                <p>Gestiona la instancia asignada, revisa su estado actual y edita la definición de <code>servers.json</code> desde un editor integrado.</p>
            </div>

            <div class="hero-actions d-flex gap-2">
                <button class="btn btn-outline-light btn-sm" onclick="reloadServersSection()">↻ Refrescar</button>
                <button class="btn btn-primary btn-sm" onclick="openServersJsonEditor()">📝 Editar servers.json</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Estado actual</div>
            <div class="stat-value">
                <?php if ($online): ?>
                    <span class="text-success">🟢 Online</span>
                <?php else: ?>
                    <span class="text-danger">🔴 Offline</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Servidores definidos</div>
            <div class="stat-value"><?= (int)$totalServers ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">PID registrado</div>
            <div class="stat-value"><?= $currentPid > 0 ? (int)$currentPid : 'N/A' ?></div>
        </div>
    </div>

    <div class="servers-card">
        <div class="servers-card-header">
            <div>
                <h3>📋 Configuración del servidor</h3>
                <small>Vista operativa de la instancia y sus parámetros de arranque.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle text-center">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Ejecutable</th>
                        <th>Puerto</th>
                        <th>Estado</th>
                        <th>Parámetros</th>
                        <th style="width:180px">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($servers)): ?>
                        <tr>
                            <td colspan="6" class="py-4 text-warning">⚠️ No existen servidores configurados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($servers as $s): ?>
                            <?php
                                $port = '';
                                if (!empty($s['params']) && preg_match('/-port\s+(\d+)/', $s['params'], $m)) {
                                    $port = $m[1];
                                }
                            ?>
                            <tr>
                                <td><span class="server-name"><?= htmlspecialchars($s['name']) ?></span></td>

                                <td class="text-start">
                                    <span class="server-path"><?= htmlspecialchars($s['path']) ?></span>
                                </td>

                                <td>
                                    <span class="badge rounded-pill text-bg-secondary px-3 py-2">
                                        <?= $port ?: '-' ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($online): ?>
                                        <span class="badge bg-success status-badge">🟢 ONLINE</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger status-badge">🔴 OFFLINE</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-start">
                                    <span class="server-params"><?= htmlspecialchars($s['params']) ?></span>
                                </td>

                                <td>
                                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                                        <button class="btn btn-success btn-sm btn-action" onclick="serverOp('start', <?= (int)$s['id'] ?>)">🚀 Iniciar</button>
                                        <button class="btn btn-danger btn-sm btn-action" onclick="serverOp('stop', <?= (int)$s['id'] ?>)">🛑 Detener</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Editor servers.json -->
<div class="modal fade json-modal" id="serversJsonModal" tabindex="-1" aria-labelledby="serversJsonModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light border-secondary">
      <div class="modal-header border-secondary">
        <div>
            <h5 class="modal-title mb-1" id="serversJsonModalLabel">📝 Editor de servers.json</h5>
            <small class="json-tip">Gestiona manualmente la definición de servidores en formato JSON.</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div id="serversJsonMsg" class="alert d-none"></div>

        <div class="mb-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <small class="json-tip">
                El contenido debe ser un arreglo JSON válido. Usa el formateador antes de guardar para mantener una estructura limpia.
            </small>
            <button type="button" class="btn btn-sm btn-outline-info" onclick="formatServersJson()">✨ Formatear JSON</button>
        </div>

        <textarea
            id="serversJsonText"
            class="form-control bg-black text-light border-secondary"
            spellcheck="false"></textarea>
      </div>

      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" onclick="saveServersJson()">💾 Guardar cambios</button>
      </div>
    </div>
  </div>
</div>

<script>
let serversJsonModalInstance = null;

function reloadServersSection(){
    fetch('pages/servers.php')
        .then(r => r.text())
        .then(html => {
            document.getElementById('main').innerHTML = html;
        });
}

function getServersJsonMsgBox(){
    return document.getElementById('serversJsonMsg');
}

function showServersJsonMsg(text, type='info'){
    const box = getServersJsonMsgBox();
    if (!box) return;
    box.className = 'alert alert-' + type;
    box.textContent = text;
}

function hideServersJsonMsg(){
    const box = getServersJsonMsgBox();
    if (!box) return;
    box.className = 'alert d-none';
    box.textContent = '';
}

function