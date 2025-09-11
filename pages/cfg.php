<?php
require_once __DIR__ . "/../config.php";

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// === Helpers ===
function listCFGFiles($dir) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    $files = [];
    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        if ($file->isFile() && strtolower($file->getExtension()) === "cfg") {
            $files[] = $file->getPathname();
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

$cfgFiles = is_dir(CFG_DIR) ? listCFGFiles(CFG_DIR) : [];
?>
<div class="container mt-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <h2 class="mb-0">⚙️ Archivos CFG en Config</h2>
        <span class="badge bg-info text-dark"><?= count($cfgFiles) ?></span>
        <small class="ms-2 text-muted">Carpeta: <code><?= htmlspecialchars(CFG_DIR) ?></code></small>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle table-dark">
            <thead>
                <tr>
                    <th style="min-width:240px">Archivo</th>
                    <th>Ruta completa</th>
                    <th style="width:140px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($cfgFiles)): ?>
                <tr><td colspan="3" class="text-center">📭 No se encontraron archivos .cfg</td></tr>
            <?php else: ?>
                <?php foreach ($cfgFiles as $file):
                    $rel = relFromCfg($file);
                ?>
                <tr>
                    <td><?= htmlspecialchars(basename($file)) ?></td>
                    <td class="text-start"><small><?= htmlspecialchars($file) ?></small></td>
                    <td>
                        <button
                            class="btn btn-primary btn-sm"
                            data-rel="<?= htmlspecialchars($rel, ENT_QUOTES) ?>"
                            onclick="openCfgEditor(this)">📝 Editar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de edición -->
<div class="modal fade" id="cfgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title" id="cfgTitle">Editar archivo CFG</h5>
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

<!-- CodeMirror (si no lo cargas global en footer.php, deja estas líneas) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/codemirror.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/theme/dracula.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/mode/properties/properties.min.js"></script>

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
          if (!cm) {
              cm = CodeMirror.fromTextArea(ta, {
                  lineNumbers: true,
                  mode: 'properties',
                  theme: 'dracula',
                  indentUnit: 2,
                  tabSize: 2
              });
          }
          cm.setValue(j.content || '');
          new bootstrap.Modal(document.getElementById('cfgModal')).show();
      })
      .catch(() => alert('⚠️ Error al cargar el archivo.'));
}

function saveCfg(){
    if (!currentRel) return;
    const content = cm ? cm.getValue() : document.getElementById('cfgEditor').value;

    const body = new URLSearchParams();
    body.set('rel', currentRel);
    body.set('content', content);
    // Si quieres exigir CSRF en api.php, envíalo así:
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
</script>
