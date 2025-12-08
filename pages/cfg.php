<?php
require_once __DIR__ . "/../config.php";

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// === Helpers ===
function listConfigFiles($dir) {
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    $files = [];
    foreach ($rii as $file) {
        if ($file->isFile()) {
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ["cfg","ini","yml","yaml","txt"])) {
                $files[] = $file->getPathname();
            }
        }
    }
    sort($files);
    return $files;
}
function relFromCfg($abs) {
    $base = rtrim(realpath(CFG_DIR), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $absR = realpath($abs);
    return ($absR && strpos($absR, $base) === 0) ? substr($absR, strlen($base)) : basename($abs);
}

$configFiles = is_dir(CFG_DIR) ? listConfigFiles(CFG_DIR) : [];
?>
<div class="container mt-4">
  <div class="d-flex align-items-center gap-2 mb-2">
    <h2 class="mb-0">⚙️ Archivos de Configuración</h2>
    <span class="badge bg-info text-dark"><?= count($configFiles) ?></span>
    <small class="ms-2 text-muted">Carpeta: <code><?= htmlspecialchars(CFG_DIR) ?></code></small>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle table-dark">
      <thead>
        <tr>
          <th style="min-width:180px">Archivo</th>
          <th>Ruta completa</th>
          <th style="width:120px">Editar</th>
          <th style="width:120px">Eliminar</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($configFiles)): ?>
        <tr><td colspan="4" class="text-center">📭 No se encontraron archivos CFG, INI, YML o TXT</td></tr>
      <?php else: ?>
        <?php foreach ($configFiles as $file):
            $rel = relFromCfg($file);
        ?>
        <tr>
          <td><?= htmlspecialchars(basename($file)) ?></td>
          <td class="text-start"><small><?= htmlspecialchars($file) ?></small></td>
          <td class="text-center">
            <button class="btn btn-primary btn-sm"
                    data-rel="<?= htmlspecialchars($rel, ENT_QUOTES) ?>"
                    onclick="openCfgEditor(this)">📝 Editar</button>
          </td>
          <td class="text-center">
            <button class="btn btn-danger btn-sm"
                    data-rel="<?= htmlspecialchars($rel, ENT_QUOTES) ?>"
                    onclick="deleteCfg(this)">🗑️ Eliminar</button>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal para edición -->
<div class="modal fade" id="cfgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title" id="cfgTitle">Editar archivo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <textarea id="cfgEditor" style="width:100%;height:480px"></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" onclick="saveCfg()">💾 Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- CodeMirror -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/codemirror.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/theme/dracula.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/mode/properties/properties.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/mode/yaml/yaml.min.js"></script>

<script>
let cm = null;
let currentRel = null;

function openCfgEditor(btn){
    const rel = btn.getAttribute('data-rel');
    if (!rel) { alert('❌ Falta parámetro rel'); return; }
    currentRel = rel;
    document.getElementById('cfgTitle').textContent = "Editar: " + rel;

    fetch('api.php?action=get_cfg&rel=' + encodeURIComponent(rel))
      .then(r => r.json())
      .then(j => {
          if (!j || j.ok === false || j.error) {
              alert('❌ ' + (j && j.error ? j.error : 'Error desconocido'));
              return;
          }
          const ta = document.getElementById('cfgEditor');
          if (cm) { cm.toTextArea(); cm = null; } // Reiniciar CodeMirror
          cm = CodeMirror.fromTextArea(ta, {
              lineNumbers: true,
              mode: guessMode(rel),
              theme: 'dracula',
              indentUnit: 2,
              tabSize: 2
          });
          cm.setValue(j.content || '');
          new bootstrap.Modal(document.getElementById('cfgModal')).show();
      })
      .catch(() => alert('⚠️ Error al cargar el archivo.'));
}

function guessMode(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    if (ext === 'yml' || ext === 'yaml') return 'yaml';
    if (ext === 'ini' || ext === 'cfg' || ext === 'txt') return 'properties';
    return 'properties';
}

function saveCfg(){
    if (!currentRel) return;
    const content = cm ? cm.getValue() : document.getElementById('cfgEditor').value;
    const body = new URLSearchParams();
    body.set('rel', currentRel);
    body.set('content', content);
    <?php if (!empty($_SESSION['csrf_token'])): ?>
    body.set('csrf', '<?= $_SESSION['csrf_token'] ?>');
    <?php endif; ?>

    fetch('api.php?action=save_cfg', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body
    })
    .then(r => r.json())
    .then(j => {
        if (j && j.ok) {
            alert('✅ Archivo guardado correctamente.');
            bootstrap.Modal.getInstance(document.getElementById('cfgModal')).hide();
        } else {
            alert('❌ Error al guardar: ' + (j && j.error ? j.error : 'desconocido'));
        }
    })
    .catch(() => alert('⚠️ Error de red al guardar.'));
}

function deleteCfg(btn){
    const rel = btn.getAttribute('data-rel');
    if (!rel) return;
    if (!confirm('⚠️ ¿Seguro que quieres eliminar ' + rel + '?')) return;
    const body = new URLSearchParams();
    body.set('rel', rel);
    <?php if (!empty($_SESSION['csrf_token'])): ?>
    body.set('csrf', '<?= $_SESSION['csrf_token'] ?>');
    <?php endif; ?>

    fetch('api.php?action=delete_cfg', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body
    })
    .then(r => r.json())
    .then(j => {
        if (j && j.ok) {
            alert('🗑️ Archivo eliminado');
            location.reload();
        } else {
            alert('❌ Error al eliminar: ' + (j && j.error ? j.error : 'desconocido'));
        }
    })
    .catch(() => alert('⚠️ Error de red al eliminar.'));
}
</script>
