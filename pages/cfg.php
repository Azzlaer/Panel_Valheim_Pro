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

$countCfg  = 0;
$countIni  = 0;
$countYaml = 0;
$countTxt  = 0;

foreach ($configFiles as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'cfg') $countCfg++;
    elseif ($ext === 'ini') $countIni++;
    elseif ($ext === 'yml' || $ext === 'yaml') $countYaml++;
    elseif ($ext === 'txt') $countTxt++;
}
?>

<style>
.cfg-wrap .panel-hero {
    background: linear-gradient(135deg, rgba(59,130,246,.14), rgba(17,24,39,.62));
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    padding: 24px;
    box-shadow: 0 18px 40px rgba(0,0,0,.28);
    margin-bottom: 22px;
}

.cfg-wrap .panel-hero h2 {
    margin: 0;
    font-weight: 800;
    color: #fff;
}

.cfg-wrap .panel-hero p {
    margin: 8px 0 0;
    color: #9ca3af;
}

.cfg-wrap .hero-actions .btn {
    border-radius: 12px;
    font-weight: 600;
}

.cfg-wrap .stats-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0,1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.cfg-wrap .stat-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 24px rgba(0,0,0,.20);
}

.cfg-wrap .stat-label {
    font-size: 12px;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 8px;
}

.cfg-wrap .stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
}

.cfg-wrap .module-card {
    background: rgba(17,24,39,.94);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(0,0,0,.24);
    margin-bottom: 22px;
}

.cfg-wrap .section-header {
    padding: 18px 20px;
    border-bottom: 1px solid rgba(255,255,255,.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.cfg-wrap .section-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.cfg-wrap .section-header small {
    color: #9ca3af;
}

.cfg-wrap .table-dark {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: rgba(255,255,255,.02);
    --bs-table-hover-bg: rgba(255,255,255,.035);
    --bs-table-color: #e5e7eb;
    --bs-table-border-color: rgba(255,255,255,.06);
    margin-bottom: 0;
}

.cfg-wrap .table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    font-weight: 700;
    padding-top: 14px;
    padding-bottom: 14px;
}

.cfg-wrap .file-name {
    font-weight: 700;
    color: #fff;
}

.cfg-wrap .file-path {
    color: #cbd5e1;
    font-size: 13px;
    line-height: 1.45;
    word-break: break-word;
}

.cfg-wrap .btn-action {
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 12px;
}

.cfg-wrap .cfg-modal .modal-content {
    border-radius: 22px;
    border: 1px solid rgba(255,255,255,.08);
    overflow: hidden;
}

.cfg-wrap .cfg-modal .modal-header,
.cfg-wrap .cfg-modal .modal-footer {
    border-color: rgba(255,255,255,.08);
}

.cfg-wrap .CodeMirror {
    height: 500px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.08);
    font-size: 14px;
}

@media (max-width: 1199px) {
    .cfg-wrap .stats-grid {
        grid-template-columns: repeat(3, minmax(0,1fr));
    }
}
@media (max-width: 767px) {
    .cfg-wrap .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 575px) {
    .cfg-wrap .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-fluid mt-4 cfg-wrap">

  <div class="panel-hero">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
      <div>
        <h2>⚙️ Gestión de Archivos de Configuración</h2>
        <p>Administra, edita y elimina archivos desde la carpeta <code><?= htmlspecialchars(CFG_DIR) ?></code> en un entorno centralizado y seguro.</p>
      </div>

      <div class="hero-actions d-flex gap-2">
        <button class="btn btn-outline-light" onclick="reloadCfgSection()">↻ Refrescar</button>
      </div>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Total de archivos</div>
      <div class="stat-value"><?= count($configFiles) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">CFG</div>
      <div class="stat-value"><?= $countCfg ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">INI</div>
      <div class="stat-value"><?= $countIni ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">YAML</div>
      <div class="stat-value"><?= $countYaml ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">TXT</div>
      <div class="stat-value"><?= $countTxt ?></div>
    </div>
  </div>

  <div class="module-card">
    <div class="section-header">
      <div>
        <h3>📄 Archivos disponibles</h3>
        <small>Se detectan archivos con extensiones <code>.cfg</code>, <code>.ini</code>, <code>.yml</code>, <code>.yaml</code> y <code>.txt</code>.</small>
      </div>
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
          <tr>
            <td colspan="4" class="text-center py-4">📭 No se encontraron archivos CFG, INI, YML, YAML o TXT</td>
          </tr>
        <?php else: ?>
          <?php foreach ($configFiles as $file):
              $rel = relFromCfg($file);
          ?>
          <tr>
            <td><span class="file-name"><?= htmlspecialchars(basename($file)) ?></span></td>
            <td class="text-start"><span class="file-path"><?= htmlspecialchars($file) ?></span></td>
            <td class="text-center">
              <button class="btn btn-primary btn-sm btn-action"
                      data-rel="<?= htmlspecialchars($rel, ENT_QUOTES) ?>"
                      onclick="openCfgEditor(this)">📝 Editar</button>
            </td>
            <td class="text-center">
              <button class="btn btn-danger btn-sm btn-action"
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
</div>

<!-- Modal para edición -->
<div class="modal fade cfg-modal" id="cfgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-1" id="cfgTitle">Editar archivo</h5>
          <small class="text-muted">Editor integrado con resaltado de sintaxis.</small>
        </div>
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

function reloadCfgSection() {
  if (window.jQuery) {
    $('#main').load('pages/cfg.php');
  } else {
    fetch('pages/cfg.php', {credentials:'same-origin'})
      .then(r => r.text())
      .then(html => { document.getElementById('main').innerHTML = html; });
  }
}

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
          if (cm) { cm.toTextArea(); cm = null; }

          cm = CodeMirror.fromTextArea(ta, {
              lineNumbers: true,
              mode: guessMode(rel),
              theme: 'dracula',
              indentUnit: 2,
              tabSize: 2,
              lineWrapping: false
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
            reloadCfgSection();
        } else {
            alert('❌ Error al eliminar: ' + (j && j.error ? j.error : 'desconocido'));
        }
    })
    .catch(() => alert('⚠️ Error de red al eliminar.'));
}
</script>