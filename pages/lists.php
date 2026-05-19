<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}

$lists = [
    'adminlist.txt'     => '👑 Administradores',
    'bannedlist.txt'    => '🚫 Baneados',
    'permittedlist.txt' => '✅ Permitidos',
];
?>

<style>
.lists-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(16,185,129,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.lists-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.lists-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}

.lists-wrap .hero-actions .btn {
    border-radius: 12px;
    font-weight: 600;
}

.lists-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.lists-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.lists-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.lists-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}

.lists-wrap .module-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
    margin-bottom: 22px;
}

.lists-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.lists-wrap .section-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.lists-wrap .section-header small {
    color: #9ca3af;
}

.lists-wrap .section-body {
    padding: 20px;
}

.lists-wrap .table-dark {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255,255,255,.02);
    --bs-table-hover-bg: rgba(255,255,255,.035);
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.06);
    margin-bottom: 0;
}

.lists-wrap .table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    font-weight: 700;
    padding-top: 14px;
    padding-bottom: 14px;
}

.lists-wrap .list-entry {
    color: #fff;
    font-weight: 700;
}

.lists-wrap .form-control,
.lists-wrap .form-control:focus {
    background: #0b1220;
    color: #e5e7eb;
    border: 1px solid rgba(255,255,255,.08);
    box-shadow: none;
    border-radius: 14px 0 0 14px;
}

.lists-wrap .input-group .btn {
    border-radius: 0 14px 14px 0;
    font-weight: 700;
}

.lists-wrap .helper-text {
    color: #9ca3af;
    font-size: 13px;
}

.lists-wrap .btn-action {
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 12px;
}

@media (max-width: 991px) {
    .lists-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .lists-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 lists-wrap">

    <div class="panel-hero">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2>📂 Gestión de Listas</h2>
                <p>Administra entradas de <code>adminlist.txt</code>, <code>bannedlist.txt</code> y <code>permittedlist.txt</code> desde una vista centralizada.</p>
            </div>
            <div class="hero-actions d-flex gap-2">
                <button class="btn btn-outline-light" onclick="reloadListsSection()">↻ Refrescar</button>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Listas gestionadas</div>
            <div class="stat-value"><?= count($lists) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Administradores</div>
            <div class="stat-value" id="count-adminlist.txt">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Baneados</div>
            <div class="stat-value" id="count-bannedlist.txt">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Permitidos</div>
            <div class="stat-value" id="count-permittedlist.txt">0</div>
        </div>
    </div>

    <?php foreach ($lists as $file => $label): ?>
        <?php $key = md5($file); ?>
        <div class="module-card">
            <div class="section-header">
                <div>
                    <h3><?= $label ?></h3>
                    <small>Archivo: <code><?= htmlspecialchars($file) ?></code></small>
                </div>
                <small>Base: <code><?= htmlspecialchars(SERVER_DIR) ?></code></small>
            </div>

            <div class="section-body">
                <div class="table-responsive">
                    <table class="table table-dark table-bordered align-middle mb-3">
                        <thead>
                            <tr>
                                <th style="width:70%">SteamID / Usuario</th>
                                <th style="width:30%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="list-<?= $key ?>">
                            <tr><td colspan="2" class="text-center py-4">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="input-group">
                    <input type="text" class="form-control" id="new-<?= $key ?>" placeholder="Agregar nuevo ID o usuario…">
                    <button class="btn btn-success" onclick="addListEntry('<?= htmlspecialchars($file, ENT_QUOTES) ?>','<?= $key ?>')">➕ Agregar</button>
                </div>

                <div class="helper-text mt-2">
                    Cada línea del archivo representa una entrada independiente. Usa el botón 🗑️ para eliminar elementos existentes.
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function reloadListsSection() {
  if (window.jQuery) {
    $('#main').load('pages/lists.php');
  } else {
    fetch('pages/lists.php', { credentials: 'same-origin' })
      .then(r => r.text())
      .then(html => { document.getElementById('main').innerHTML = html; });
  }
}

// ------- utilidades fetch -------
function apiGet(url) {
  return fetch(url, { credentials: 'same-origin' }).then(async r => {
    const text = await r.text();
    try { return JSON.parse(text); } catch {
      console.error('Respuesta no-JSON de', url, text);
      throw new Error('Respuesta no válida del servidor');
    }
  });
}

function apiPost(url, dataObj) {
  const body = new URLSearchParams(dataObj || {});
  return fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString(),
    credentials: 'same-origin'
  }).then(async r => {
    const text = await r.text();
    try { return JSON.parse(text); } catch {
      console.error('Respuesta no-JSON de', url, text);
      throw new Error('Respuesta no válida del servidor');
    }
  });
}

// ------- helpers DOM -------
function tbodyFor(targetKey) {
  return document.getElementById('list-' + targetKey);
}

function esc(s) {
  return (s + '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
}

function updateListCounter(file, count) {
  const el = document.getElementById('count-' + file);
  if (el) el.textContent = count;
}

// ------- cargar / render -------
function loadList(file, targetKey) {
  const tbody = tbodyFor(targetKey);
  tbody.innerHTML = `<tr><td colspan="2" class="text-center py-4">Cargando…</td></tr>`;

  apiGet(`api.php?action=get_list&file=${encodeURIComponent(file)}`)
    .then(data => {
      if (Array.isArray(data)) {
        updateListCounter(file, data.length);

        if (data.length === 0) {
          tbody.innerHTML = `<tr><td colspan="2" class="text-center py-4">📭 Vacío</td></tr>`;
          return;
        }

        tbody.innerHTML = data.map((line, i) => `
          <tr>
            <td><code class="list-entry">${esc(line)}</code></td>
            <td>
              <button class="btn btn-danger btn-sm btn-action"
                      onclick="deleteListEntry('${esc(file)}', ${i}, '${targetKey}')">🗑️ Eliminar</button>
            </td>
          </tr>`).join('');
      } else if (data && data.ok === false) {
        tbody.innerHTML = `<tr><td colspan="2" class="text-center text-danger py-4">❌ ${esc(data.error || 'Error al cargar')}</td></tr>`;
      } else {
        updateListCounter(file, 0);
        tbody.innerHTML = `<tr><td colspan="2" class="text-center py-4">📭 Vacío</td></tr>`;
      }
    })
    .catch((e) => {
      console.error('loadList error:', e);
      updateListCounter(file, 0);
      tbody.innerHTML = `<tr><td colspan="2" class="text-center text-warning py-4">⚠️ Error al cargar ${esc(file)}</td></tr>`;
    });
}

function addListEntry(file, targetKey) {
  const input = document.getElementById('new-' + targetKey);
  const value = (input.value || '').trim();
  if (!value) { alert('Ingrese un valor válido.'); return; }

  apiPost(`api.php?action=add_list&file=${encodeURIComponent(file)}`, {
    entry: value
  })
  .then(j => {
    if (j && j.ok) {
      input.value = '';
      loadList(file, targetKey);
    } else {
      alert('❌ ' + (j && j.error ? j.error : 'No se pudo agregar'));
    }
  })
  .catch((e) => {
    console.error('addListEntry error:', e);
    alert('⚠️ Error de red al agregar');
  });
}

function deleteListEntry(file, index, targetKey) {
  apiPost(`api.php?action=delete_list&file=${encodeURIComponent(file)}&index=${index}`, {})
  .then(j => {
    if (j && j.ok) {
      loadList(file, targetKey);
    } else {
      alert('❌ ' + (j && j.error ? j.error : 'No se pudo eliminar'));
    }
  })
  .catch((e) => {
    console.error('deleteListEntry error:', e);
    alert('⚠️ Error de red al eliminar');
  });
}

// ------- inicializar -------
<?php foreach ($lists as $file => $label): ?>
loadList("<?= $file ?>", "<?= md5($file) ?>");
<?php endforeach; ?>
</script>