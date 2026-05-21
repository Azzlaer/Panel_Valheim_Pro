<?php
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

$worlds = glob(WORLDS_DIR . '/*.fwl') ?: [];
?>
<div class="container mt-4 text-light">
    <h2>🌍 Editor de Mundos (FWL)</h2>
<p class="text-muted"><span style="color: #ffffff;">Selecciona un archivo <code>.fwl</code> para leer y editar su contenido usando</span> <a href="https://github.com/Kakoen/valheim-save-tools" target="_blank">valheim-save-tools</a>.</p>

    <div class="mb-3">
        <label class="form-label">Archivo FWL:</label>
        <select id="fwlFile" class="form-select bg-dark text-light">
            <option value="">-- Selecciona un mundo --</option>
            <?php foreach ($worlds as $file): ?>
                <option value="<?= htmlspecialchars(basename($file)) ?>">
                    <?= htmlspecialchars(basename($file)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <button class="btn btn-primary" onclick="loadFwl()">📂 Cargar</button>
    </div>

    <div id="editorArea" style="display:none;">
        <h4 id="editorTitle" class="mt-4"></h4>
        <textarea id="fwlEditor" class="form-control bg-dark text-light" rows="20"></textarea>
        <div class="mt-3">
            <button class="btn btn-success" onclick="saveFwl()">💾 Guardar cambios</button>
        </div>
    </div>
</div>

<script>
function loadFwl(){
    const file = document.getElementById('fwlFile').value;
    if(!file){ alert('Seleccione un archivo .fwl'); return; }

    fetch('tools_fwl_api.php?action=read&file='+encodeURIComponent(file), {credentials:'same-origin'})
      .then(r => r.json())
      .then(j => {
        if(j.ok){
            document.getElementById('editorTitle').textContent = "Editando: " + file;
            document.getElementById('fwlEditor').value = JSON.stringify(j.data, null, 2);
            document.getElementById('editorArea').style.display = 'block';
        } else {
            alert('❌ Error: ' + j.error);
        }
      })
      .catch(()=>alert('⚠️ Error de red al cargar.'));
}

function saveFwl(){
    const file = document.getElementById('fwlFile').value;
    if(!file){ alert('Seleccione un archivo .fwl'); return; }
    let content;
    try {
        content = JSON.parse(document.getElementById('fwlEditor').value);
    } catch(e) {
        alert('JSON inválido: ' + e.message);
        return;
    }

    fetch('tools_fwl_api.php?action=write&file='+encodeURIComponent(file), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(content),
        credentials:'same-origin'
    })
    .then(r => r.json())
    .then(j => {
        if(j.ok){
            alert('✅ Archivo guardado correctamente.');
        } else {
            alert('❌ Error al guardar: ' + j.error);
        }
    })
    .catch(()=>alert('⚠️ Error de red al guardar.'));
}
</script>
