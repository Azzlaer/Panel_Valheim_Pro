<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>

<style>
.backups-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(16,185,129,.12), rgba(17,24,39,.6));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.backups-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.backups-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}

.backups-wrap .hero-actions .btn {
    border-radius: 12px;
    font-weight: 600;
}

.backups-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.backups-wrap .stat-card {
    background: rgba(17,24,39,.92);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.backups-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.backups-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}

.backups-wrap .backups-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
}

.backups-wrap .backups-card-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.backups-wrap .backups-card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.backups-wrap .backups-card-header small {
    color: #9ca3af;
}

.backups-wrap table {
    margin-bottom: 0;
}

.backups-wrap .table-dark {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255,255,255,.02);
    --bs-table-hover-bg: rgba(255,255,255,.035);
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.06);
}

.backups-wrap .table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    font-weight: 700;
    border-bottom-width: 1px;
    padding-top: 14px;
    padding-bottom: 14px;
}

.backups-wrap .backup-name {
    font-weight: 700;
    color: #fff;
}

.backups-wrap .msg-box {
    display: none;
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 18px;
    border: 1px solid rgba(255,255,255,.08);
    font-weight: 600;
}

.backups-wrap .msg-info    { display:block; background: rgba(59,130,246,.12); border-color: rgba(59,130,246,.2); color: #bfdbfe; }
.backups-wrap .msg-success { display:block; background: rgba(16,185,129,.12); border-color: rgba(16,185,129,.2); color: #a7f3d0; }
.backups-wrap .msg-error   { display:block; background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.2); color: #fecaca; }

.backups-wrap .btn-action {
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 12px;
}

@media (max-width: 991px) {
    .backups-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 backups-wrap">
    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>🗂️ Gestión de Respaldos</h2>
                <p>Administra respaldos manuales de <code>worlds_local</code>, descarga copias existentes y elimina archivos antiguos desde una vista centralizada.</p>
            </div>

            <div class="hero-actions d-flex gap-2">
                <button id="create-backup" class="btn btn-success">➕ Crear respaldo manual</button>
                <button class="btn btn-outline-light" onclick="loadBackups()">↻ Refrescar</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Estado del módulo</div>
            <div class="stat-value text-info">Operativo</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Respaldos detectados</div>
            <div class="stat-value" id="backup-count">0</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Última acción</div>
            <div class="stat-value" id="backup-last-action">N/A</div>
        </div>
    </div>

    <div id="backup-msg" class="msg-box"></div>

    <div class="backups-card">
        <div class="backups-card-header">
            <div>
                <h3>📦 Historial de respaldos</h3>
                <small>Respaldos ZIP generados desde el panel para proteger los mundos y datos del servidor.</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-striped text-center align-middle">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Tamaño</th>
                        <th>Creado</th>
                        <th style="width:220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="backups-table">
                    <tr><td colspan="4">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function setBackupMsg(text, type='info'){
  const box = document.getElementById('backup-msg');
  box.className = 'msg-box';
  if(type === 'success') box.classList.add('msg-success');
  else if(type === 'error') box.classList.add('msg-error');
  else box.classList.add('msg-info');
  box.textContent = text;
}

function updateStats(items = [], lastAction = 'N/A'){
  document.getElementById('backup-count').textContent = items.length;
  document.getElementById('backup-last-action').textContent = lastAction;
}

function loadBackups(){
  fetch('api.php?action=list_backups', {credentials:'same-origin'})
    .then(r => r.json())
    .then(data => {
      if(!data.ok){ throw new Error(data.error || 'Error'); }

      const tbody = document.getElementById('backups-table');
      const items = data.items || [];

      updateStats(items, 'Consulta');

      if(items.length === 0){
        tbody.innerHTML = '<tr><td colspan="4" class="py-4">📭 Sin respaldos disponibles</td></tr>';
        return;
      }

      tbody.innerHTML = items.map(f => `
        <tr>
          <td><span class="backup-name">${f.name}</span></td>
          <td>${Number(f.size_mb).toFixed(2)} MB</td>
          <td>${f.mtime}</td>
          <td>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
              <a class="btn btn-success btn-sm btn-action" href="backups/${encodeURIComponent(f.name)}" download>⬇️ Descargar</a>
              <button class="btn btn-danger btn-sm btn-action" onclick="deleteBackup('${encodeURIComponent(f.name)}')">🗑️ Eliminar</button>
            </div>
          </td>
        </tr>
      `).join('');
    })
    .catch(e => {
      document.getElementById('backups-table').innerHTML =
        `<tr><td colspan="4" class="text-danger py-4">⚠️ ${e.message || e}</td></tr>`;
      setBackupMsg('No se pudo cargar la lista de respaldos.', 'error');
    });
}

function createBackup(){
  setBackupMsg('⏳ Creando respaldo manual, por favor espera...', 'info');

  fetch('api.php?action=create_backup', {credentials:'same-origin'})
    .then(r => r.json())
    .then(d => {
      if(d.ok){
        setBackupMsg('✅ Respaldo creado correctamente: ' + d.file, 'success');
        updateStats([], 'Creación');
        loadBackups();
      } else {
        setBackupMsg('❌ ' + (d.error || 'Error desconocido'), 'error');
      }
    })
    .catch(e => {
      setBackupMsg('⚠️ Error al crear respaldo: ' + (e.message || e), 'error');
    });
}

function deleteBackup(name){
  if(!confirm('¿Eliminar el respaldo seleccionado?\n\n' + decodeURIComponent(name))) return;

  fetch('api.php?action=delete_backup&file=' + name, {credentials:'same-origin'})
    .then(r => r.json())
    .then(d => {
      if(d.ok){
        setBackupMsg('🗑️ Respaldo eliminado correctamente.', 'success');
        updateStats([], 'Eliminación');
        loadBackups();
      } else {
        setBackupMsg('❌ ' + (d.error || 'Error desconocido'), 'error');
      }
    })
    .catch(e => {
      setBackupMsg('⚠️ Error al eliminar respaldo: ' + (e.message || e), 'error');
    });
}

document.getElementById('create-backup').addEventListener('click', createBackup);
loadBackups();
</script>