<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
$csrf = $_SESSION['csrf_token'] ?? '';
?>

<style>
.bep-wrap .hero{background:linear-gradient(135deg,rgba(124,58,237,.18),rgba(59,130,246,.12),rgba(17,24,39,.72));border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:24px;margin-bottom:22px;box-shadow:0 18px 40px rgba(0,0,0,.28)}
.bep-wrap .hero h2{margin:0;color:#fff;font-weight:800}.bep-wrap .hero p{margin:8px 0 0;color:#9ca3af;max-width:900px}
.bep-wrap .grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}
.bep-wrap .cardx{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:18px;box-shadow:0 10px 24px rgba(0,0,0,.2)}
.bep-wrap .label{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:8px}.bep-wrap .value{font-size:22px;font-weight:800;color:#fff}
.bep-wrap .module{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:22px;overflow:hidden;margin-bottom:22px;box-shadow:0 18px 40px rgba(0,0,0,.22)}
.bep-wrap .module-head{padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.bep-wrap .module-head h3{margin:0;font-size:18px;font-weight:800}.bep-wrap .module-body{padding:20px}
.bep-wrap .status-row{display:grid;grid-template-columns:220px 1fr;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)}.bep-wrap .status-row:last-child{border-bottom:0}.bep-wrap .status-key{color:#9ca3af}.bep-wrap .status-val{color:#e5e7eb;word-break:break-word}
.bep-wrap .notice{border-radius:16px;padding:14px 16px;margin-bottom:18px;border:1px solid rgba(255,255,255,.08)}.bep-wrap .notice-info{background:rgba(59,130,246,.10);color:#bfdbfe}.bep-wrap .notice-warn{background:rgba(245,158,11,.10);color:#fde68a}.bep-wrap .notice-success{background:rgba(16,185,129,.10);color:#a7f3d0}.bep-wrap .notice-error{background:rgba(239,68,68,.10);color:#fecaca}
.bep-wrap .btn-lgx{border-radius:12px;font-weight:800;padding:11px 16px}.bep-wrap code{color:#c4b5fd}
@media(max-width:1100px){.bep-wrap .grid{grid-template-columns:1fr 1fr}}@media(max-width:700px){.bep-wrap .grid{grid-template-columns:1fr}.bep-wrap .status-row{grid-template-columns:1fr}}
</style>

<div class="container-fluid mt-4 bep-wrap">
  <div class="hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <h2>🧩 BepInEx Manager</h2>
        <p>Instala y mantiene <strong>denikson/BepInExPack_Valheim</strong> directamente desde Thunderstore. El panel compara la versión instalada con la última versión disponible y protege tu instalación actual con un backup antes de actualizar.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-info" href="https://old.thunderstore.io/c/valheim/p/denikson/BepInExPack_Valheim/" target="_blank" rel="noopener">🌐 Thunderstore</a>
        <button class="btn btn-outline-light" onclick="bepRefresh()">↻ Refrescar</button>
      </div>
    </div>
  </div>

  <div class="grid">
    <div class="cardx"><div class="label">Estado</div><div class="value" id="bep-status">Comprobando…</div></div>
    <div class="cardx"><div class="label">Versión instalada</div><div class="value" id="bep-installed">—</div></div>
    <div class="cardx"><div class="label">Última Thunderstore</div><div class="value" id="bep-latest">—</div></div>
    <div class="cardx"><div class="label">Servidor</div><div class="value" id="bep-server">—</div></div>
  </div>

  <div class="module">
    <div class="module-head">
      <div><h3>⚙️ Instalación y actualización</h3><small class="text-secondary">SERVER_BASE será el destino del contenido de BepInExPack_Valheim.</small></div>
      <button id="bep-action" class="btn btn-primary btn-lgx" onclick="bepInstall()" disabled>⏳ Comprobando…</button>
    </div>
    <div class="module-body">
      <div id="bep-message" class="notice notice-info">Consultando instalación local y Thunderstore…</div>

      <div class="status-row"><div class="status-key">Paquete oficial</div><div class="status-val"><code>denikson-BepInExPack_Valheim</code></div></div>
      <div class="status-row"><div class="status-key">BepInEx.dll</div><div class="status-val" id="bep-dll">—</div></div>
      <div class="status-row"><div class="status-key">BepInEx/</div><div class="status-val" id="bep-dir">—</div></div>
      <div class="status-row"><div class="status-key">Plugins</div><div class="status-val" id="bep-plugins">—</div></div>
      <div class="status-row"><div class="status-key">Config</div><div class="status-val" id="bep-config">—</div></div>
      <div class="status-row"><div class="status-key">Bootstrap Windows</div><div class="status-val" id="bep-bootstrap">—</div></div>
      <div class="status-row"><div class="status-key">Último backup</div><div class="status-val" id="bep-backup">—</div></div>
    </div>
  </div>

  <div class="module">
    <div class="module-head"><div><h3>🩺 Requisitos</h3><small class="text-secondary">Comprobaciones necesarias para instalación automática.</small></div></div>
    <div class="module-body">
      <div class="status-row"><div class="status-key">ZipArchive</div><div class="status-val" id="bep-zip">—</div></div>
      <div class="status-row"><div class="status-key">HTTP client</div><div class="status-val" id="bep-http">—</div></div>
      <div class="status-row"><div class="status-key">Thunderstore</div><div class="status-val" id="bep-remote">—</div></div>
    </div>
  </div>
</div>

<script>
(function(){
  var csrf = <?= json_encode($csrf, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;
  var snapshot = null;

  function setText(id,text){var e=document.getElementById(id);if(e)e.textContent=text;}
  function setMessage(text,type){
    var e=document.getElementById('bep-message'); if(!e)return;
    e.className='notice notice-'+(type||'info'); e.textContent=text;
  }
  function versionLabel(inst){
    if(inst.package_version)return inst.package_version;
    if(inst.dll_version)return 'DLL '+inst.dll_version+' (pack desconocido)';
    return 'No instalado';
  }
  function render(j){
    snapshot=j;
    var inst=j.installed||{}, latest=j.latest||null, cmp=j.comparison||'unknown';
    var installed=!!inst.installed;

    if(!installed){
      setText('bep-status','❌ No instalado');
    }else if(cmp==='current'){
      setText('bep-status','✅ Actualizado');
    }else if(cmp==='outdated'){
      setText('bep-status','⬆️ Update disponible');
    }else if(cmp==='newer'){
      setText('bep-status','🧪 Versión superior');
    }else{
      setText('bep-status','⚠️ Instalado / sin tracking');
    }

    setText('bep-installed',versionLabel(inst));
    setText('bep-latest',latest?latest.version:'No disponible');
    setText('bep-server',j.server_running?'🟢 Online':'⚫ Offline');
    setText('bep-dll',inst.dll_version||'No detectada');
    setText('bep-dir',inst.bepinex_dir||'—');
    setText('bep-plugins',inst.plugins_dir||'—');
    setText('bep-config',inst.config_dir||'—');
    setText('bep-bootstrap',(inst.doorstop_present?'doorstop ✅':'doorstop ❌')+' · '+(inst.winhttp_present?'winhttp ✅':'winhttp ❌'));
    setText('bep-backup',inst.last_backup||'Sin backup registrado');

    setText('bep-zip',j.requirements&&j.requirements.ziparchive?'✅ Disponible':'❌ No disponible');
    var http=(j.requirements&&j.requirements.curl)?'✅ cURL':((j.requirements&&j.requirements.allow_url_fopen)?'✅ allow_url_fopen':'❌ No disponible');
    setText('bep-http',http);
    setText('bep-remote',latest?'✅ Thunderstore conectado':'❌ '+(j.remote_error||'No disponible'));

    var btn=document.getElementById('bep-action');
    var can=!!latest && !!(j.requirements&&j.requirements.ziparchive) && !j.server_running;
    btn.disabled=!can;
    if(!installed){btn.textContent='📥 Instalar BepInExPack';}
    else if(cmp==='outdated'){btn.textContent='⬆️ Actualizar a '+latest.version;}
    else if(cmp==='current'){btn.textContent='♻️ Reinstalar '+latest.version;}
    else{btn.textContent='🔄 Instalar última versión '+(latest?latest.version:'');}

    if(j.server_running){
      setMessage('🛑 Detén Valheim antes de instalar o actualizar BepInEx. El panel bloquea la operación mientras valheim_server.exe esté activo.','warn');
    }else if(!latest){
      setMessage('No se pudo consultar Thunderstore: '+(j.remote_error||'error desconocido'),'error');
    }else if(!installed){
      setMessage('BepInEx no está instalado. Se instalará la versión oficial '+latest.version+' directamente en SERVER_BASE.','info');
    }else if(cmp==='current'){
      setMessage('✅ BepInExPack '+latest.version+' está actualizado. Puedes reinstalarlo si necesitas reparar archivos del framework.','success');
    }else if(cmp==='outdated'){
      setMessage('⬆️ Hay una actualización disponible: '+versionLabel(inst)+' → '+latest.version+'. Antes de actualizar se creará un backup.','warn');
    }else{
      setMessage('BepInEx fue detectado, pero el panel no conoce con certeza la versión del paquete Thunderstore instalado. Puedes instalar la última versión para comenzar a rastrearla.','warn');
    }
  }

  window.bepRefresh=function(){
    setMessage('Consultando instalación local y Thunderstore…','info');
    fetch('bepinex_api.php?action=status',{credentials:'same-origin'})
      .then(async function(r){var t=await r.text();var j;try{j=JSON.parse(t);}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida');}if(!j.ok)throw new Error(j.error||'Error');return j;})
      .then(render).catch(function(e){setMessage('❌ '+e.message,'error');});
  };

  window.bepInstall=function(){
    if(!snapshot||!snapshot.latest)return;
    var inst=snapshot.installed||{};
    var action=inst.installed?'instalar/actualizar':'instalar';
    if(!confirm('¿Deseas '+action+' BepInExPack '+snapshot.latest.version+'?\n\nEl servidor debe estar apagado. Si ya existe BepInEx, se creará un backup antes de sobrescribir el framework.'))return;

    var btn=document.getElementById('bep-action'); btn.disabled=true; btn.textContent='⏳ Instalando…';
    setMessage('Descargando y preparando BepInExPack desde Thunderstore…','info');

    var fd=new FormData();fd.append('csrf',csrf);fd.append('force','1');
    fetch('bepinex_api.php?action=install',{method:'POST',body:fd,credentials:'same-origin'})
      .then(async function(r){var t=await r.text();var j;try{j=JSON.parse(t);}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida');}if(!r.ok||!j.ok)throw new Error(j.error||'Instalación fallida');return j;})
      .then(function(j){setMessage('✅ '+j.message+(j.backup?' · Backup: '+j.backup:''),'success');setTimeout(bepRefresh,900);})
      .catch(function(e){setMessage('❌ '+e.message,'error');btn.disabled=false;});
  };

  bepRefresh();
})();
</script>
