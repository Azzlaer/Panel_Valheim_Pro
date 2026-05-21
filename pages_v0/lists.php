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
<div class="container mt-4">
    <h2>📂 Gestión de Listas</h2>

    <?php foreach ($lists as $file => $label): ?>
        <?php $key = md5($file); ?>
        <div class="card bg-dark text-light mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><?= $label ?> <small class="text-muted">(<?= htmlspecialchars($file) ?>)</small></span>
                <small class="text-muted">Base: <code><?= htmlspecialchars(SERVER_DIR) ?></code></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-dark table-bordered align-middle mb-2">
                        <thead>
                            <tr>
                                <th style="width:70%">SteamID / Usuario</th>
                                <th style="width:30%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="list-<?= $key ?>">
                            <tr><td colspan="2" class="text-center">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="input-group">
                    <input type="text" class="form-control" id="new-<?= $key ?>" placeholder="Agregar nuevo ID…">
                    <button class="btn btn-success" onclick="addListEntry('<?= htmlspecialchars($file, ENT_QUOTES) ?>','<?= $key ?>')">➕ Agregar</button>
                </div>
                <div class="form-text text-muted mt-1">
                    Cada línea del archivo representa una entrada. Usa el botón 🗑️ para eliminar.
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
// ------- utilidades fetch (fuerza envío de cookies de sesión) -------
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

// ------- cargar / render -------
function loadList(file, targetKey) {
  const tbody = tbodyFor(targetKey);
  tbody.innerHTML = `<tr><td colspan="2" class="text-center">Cargando…</td></tr>`;

  apiGet(`api.php?action=get_list&file=${encodeURIComponent(file)}`)
    .then(data => {
      if (Array.isArray(data)) {
        if (data.length === 0) {
          tbody.innerHTML = `<tr><td colspan="2" class="text-center">📭 Vacío</td></tr>`;
          return;
        }
        tbody.innerHTML = data.map((line, i) => `
          <tr>
            <td><code>${esc(line)}</code></td>
            <td>
              <button class="btn btn-danger btn-sm"
                      onclick="deleteListEntry('${esc(file)}', ${i}, '${targetKey}')">🗑️ Eliminar</button>
            </td>
          </tr>`).join('');
      } else if (data && data.ok === false) {
        tbody.innerHTML = `<tr><td colspan="2" class="text-center text-danger">❌ ${esc(data.error || 'Error al cargar')}</td></tr>`;
      } else {
        tbody.innerHTML = `<tr><td colspan="2" class="text-center">📭 Vacío</td></tr>`;
      }
    })
    .catch((e) => {
      console.error('loadList error:', e);
      tbody.innerHTML = `<tr><td colspan="2" class="text-center text-warning">⚠️ Error al cargar ${esc(file)}</td></tr>`;
    });
}

function addListEntry(file, targetKey) {
  const input = document.getElementById('new-' + targetKey);
  const value = (input.value || '').trim();
  if (!value) { alert('Ingrese un valor válido.'); return; }

  apiPost(`api.php?action=add_list&file=${encodeURIComponent(file)}`, {
    entry: value
    // , csrf: '<?= $_SESSION['csrf_token'] ?? '' ?>'
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
  apiPost(`api.php?action=delete_list&file=${encodeURIComponent(file)}&index=${index}`, {
    // csrf: '<?= $_SESSION['csrf_token'] ?? '' ?>'
  })
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
