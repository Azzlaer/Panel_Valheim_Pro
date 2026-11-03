<?php
$configPath=__DIR__.'/../config.php';
if(is_file($configPath)) require_once $configPath;
if(session_status()===PHP_SESSION_NONE) session_start();
$installed=defined('PANEL_INSTALLED')?PANEL_INSTALLED:false;
if($installed && (empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true)){
    http_response_code(403);exit('Acceso denegado');
}
if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_token'];
?>
<style>
.mig-wrap .hero{background:linear-gradient(135deg,rgba(59,130,246,.17),rgba(124,58,237,.12),rgba(17,24,39,.78));border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:24px;margin-bottom:22px}
.mig-wrap .hero h2{font-weight:800;margin:0}.mig-wrap .hero p{color:#9ca3af;margin:8px 0 0;max-width:920px}
.mig-wrap .cardx{background:rgba(17,24,39,.96);border:1px solid rgba(255,255,255,.08);border-radius:20px;overflow:hidden;margin-bottom:20px}
.mig-wrap .head{padding:17px 20px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}.mig-wrap .body{padding:20px}
.mig-wrap .drop{border:2px dashed rgba(96,165,250,.35);background:rgba(59,130,246,.06);border-radius:18px;padding:28px;text-align:center}
.mig-wrap .summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}.mig-wrap .pill{padding:14px;border:1px solid rgba(255,255,255,.08);border-radius:14px;background:#0b1220}.mig-wrap .pill b{display:block;font-size:22px}
.mig-wrap .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.mig-wrap .full{grid-column:1/-1}
.mig-wrap label{font-size:12px;color:#cbd5e1;font-weight:700;margin-bottom:5px}.mig-wrap .form-control,.mig-wrap .form-select{background:#0b1220;color:#e5e7eb;border-color:rgba(255,255,255,.1)}
.mig-wrap .field-note{font-size:11px;color:#94a3b8;margin-top:5px}.mig-wrap code{color:#93c5fd}
.mig-wrap .status-ok{color:#6ee7b7}.mig-wrap .status-missing{color:#fca5a5}.mig-wrap .status-warning{color:#fde68a}.mig-wrap .status-optional{color:#94a3b8}
@media(max-width:800px){.mig-wrap .summary{grid-template-columns:1fr 1fr}.mig-wrap .form-grid{grid-template-columns:1fr}.mig-wrap .full{grid-column:auto}}
</style>

<div class="container-fluid mt-4 mig-wrap">
 <div class="hero">
  <h2>🧭 Migration & Configuration Import Center</h2>
  <p>Importa un <code>config.php</code> antiguo o un JSON de configuración. El archivo PHP se analiza como texto: nunca se ejecuta. El asistente recupera valores compatibles, deriva rutas nuevas y te muestra qué falta antes de tocar la configuración actual.</p>
 </div>

 <div id="migMsg" class="alert alert-info">Selecciona una configuración antigua para comenzar.</div>

 <div class="cardx">
  <div class="head"><div><strong>1. Importar configuración</strong><div class="text-secondary small">Compatible con config.php legacy y JSON.</div></div></div>
  <div class="body">
   <form id="migUpload" class="drop">
    <input type="file" class="form-control mb-3" id="migFile" accept=".php,.json" required>
    <button class="btn btn-primary">📥 Analizar configuración</button>
    <div class="small text-secondary mt-3">Máximo 2 MB. No se modifica ningún archivo durante el análisis.</div>
   </form>
  </div>
 </div>

 <div class="cardx d-none" id="migReportCard">
  <div class="head"><div><strong>2. Informe de compatibilidad</strong><div class="text-secondary small" id="migFilename"></div></div><button class="btn btn-sm btn-outline-light" onclick="migReset()">↺ Cancelar staging</button></div>
  <div class="body">
   <div class="summary">
    <div class="pill"><span class="text-secondary">Correctos</span><b class="text-success" id="sumOk">0</b></div>
    <div class="pill"><span class="text-secondary">Faltantes</span><b class="text-danger" id="sumMissing">0</b></div>
    <div class="pill"><span class="text-secondary">Inválidos</span><b class="text-danger" id="sumInvalid">0</b></div>
    <div class="pill"><span class="text-secondary">Campos actuales</span><b id="sumTotal">0</b></div>
   </div>
   <div id="migLegacyWarn" class="alert alert-warning d-none"></div>
   <div id="migUnknown" class="alert alert-secondary d-none"></div>
   <div class="table-responsive">
    <table class="table table-dark table-striped align-middle"><thead><tr><th>Campo</th><th>Estado</th><th>Origen</th><th>Detalle</th></tr></thead><tbody id="migReportRows"></tbody></table>
   </div>
  </div>
 </div>

 <div class="cardx d-none" id="migEditCard">
  <div class="head"><div><strong>3. Completar y aplicar</strong><div class="text-secondary small">Los campos nuevos o faltantes pueden completarse aquí.</div></div></div>
  <div class="body">
   <div class="form-grid" id="migFields"></div>
   <hr class="border-secondary my-4">
   <div class="form-grid">
    <div class="full">
     <label>Nueva contraseña de administrador (opcional si ya existe ADMIN_PASS_HASH)</label>
     <input type="password" class="form-control" id="migAdminNew" minlength="8" autocomplete="new-password" placeholder="Dejar vacío para conservar hash importado/actual">
     <div class="field-note">Si el config antiguo usaba <code>ADMIN_PASS</code> en texto plano, define una nueva contraseña aquí.</div>
    </div>
   </div>
   <div class="d-flex gap-2 mt-4 flex-wrap">
    <button class="btn btn-success" onclick="migApply()">✅ Aplicar migración</button>
    <button class="btn btn-outline-light" onclick="migValidateLocal()">🩺 Revisar campos</button>
   </div>
  </div>
 </div>
</div>

<script>
(function(){
 var csrf=<?=json_encode($csrf,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
 var schema={}, values={}, report={}, derived={};

 function msg(t,c){var e=document.getElementById('migMsg');e.className='alert alert-'+(c||'info');e.textContent=t;}
 function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
 function statusIcon(s){return s==='ok'?'✅':s==='missing'?'❌':s==='invalid'?'❌':s==='warning'?'⚠️':'○';}
 function statusClass(s){return 'status-'+(s||'optional');}

 function render(j){
   schema=j.schema||schema; values=j.values||values; report=j.report||report; derived=j.derived||derived;
   document.getElementById('migReportCard').classList.remove('d-none');
   document.getElementById('migEditCard').classList.remove('d-none');
   document.getElementById('migFilename').textContent=j.filename||'Configuración en staging';

   var s=(report.summary||{});
   document.getElementById('sumOk').textContent=s.ok||0;
   document.getElementById('sumMissing').textContent=s.missing||0;
   document.getElementById('sumInvalid').textContent=s.invalid||0;
   document.getElementById('sumTotal').textContent=s.total||0;

   var legacy=document.getElementById('migLegacyWarn');
   legacy.classList.toggle('d-none',!j.legacy_plain_admin_pass);
   if(j.legacy_plain_admin_pass)legacy.textContent='🔐 Se detectó ADMIN_PASS legacy en texto plano. Por seguridad no fue importado. Debes establecer una contraseña nueva o conservar un ADMIN_PASS_HASH válido.';

   var u=j.unknown_keys||[], ub=document.getElementById('migUnknown');
   ub.classList.toggle('d-none',!u.length);
   if(u.length)ub.textContent='ℹ️ Claves antiguas/no reconocidas preservadas solo en el informe: '+u.join(', ');

   var rows=document.getElementById('migReportRows');
   rows.innerHTML=Object.keys(schema).map(function(k){
      var r=(report.fields||{})[k]||{status:'optional',message:'—',label:k};
      var origin=derived[k]?'🧠 '+derived[k]:(values[k+'_present']?'🔐 Secreto conservado':(values[k]!==undefined&&values[k]!==''?'📥 Importado/actual':'—'));
      return '<tr><td><b>'+esc(r.label||k)+'</b><br><code>'+esc(k)+'</code></td><td class="'+statusClass(r.status)+'">'+statusIcon(r.status)+' '+esc(r.status)+'</td><td>'+esc(origin)+'</td><td>'+esc(r.message||'')+'</td></tr>';
   }).join('');

   var root=document.getElementById('migFields');
   root.innerHTML=Object.keys(schema).filter(k=>schema[k].type!=='secret'&&k!=='ADMIN_PASS_HASH').map(function(k){
      var s=schema[k],v=values[k]??'';
      var input;
      if(s.type==='enum'){
        input='<select class="form-select mig-field" data-key="'+esc(k)+'">'+(s.values||[]).map(x=>'<option value="'+esc(x)+'"'+(String(v)===String(x)?' selected':'')+'>'+esc(x)+'</option>').join('')+'</select>';
      }else{
        var type=s.type==='int'?'number':'text';
        input='<input type="'+type+'" class="form-control mig-field" data-key="'+esc(k)+'" value="'+esc(v)+'">';
      }
      var note=derived[k]?'🧠 '+derived[k]:((report.fields||{})[k]?.message||'');
      return '<div class="'+(k==='SERVER_BASE'||k==='SERVER_PATH'?'full':'')+'"><label>'+esc(s.label||k)+' <code>'+esc(k)+'</code></label>'+input+'<div class="field-note">'+esc(note)+'</div></div>';
   }).join('');

   // RCON password is intentionally blank; entering a value replaces staged secret.
   root.insertAdjacentHTML('beforeend','<div><label>RCON contraseña <code>RCON_PASS</code></label><input type="password" id="migRconPass" class="form-control" placeholder="'+(values.RCON_PASS_present?'Secreto importado/conservado':'No configurado')+'"><div class="field-note">Déjalo vacío para conservar el valor en staging.</div></div>');
 }

 document.getElementById('migUpload').addEventListener('submit',function(e){
   e.preventDefault();var file=document.getElementById('migFile').files[0];if(!file)return;
   var fd=new FormData();fd.append('csrf',csrf);fd.append('config_file',file);
   msg('Analizando configuración sin ejecutarla…','info');
   fetch('migration_api.php?action=import',{method:'POST',body:fd,credentials:'same-origin'}).then(async r=>{var t=await r.text(),j;try{j=JSON.parse(t)}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida')}if(!r.ok||!j.ok)throw new Error(j.error||'Error');return j;})
     .then(function(j){schema=j.schema||schema;render(j);msg('✅ '+j.message,'success');})
     .catch(e=>msg('❌ '+e.message,'danger'));
 });

 window.migApply=function(){
   var fd=new FormData();fd.append('csrf',csrf);
   document.querySelectorAll('.mig-field').forEach(function(el){fd.append(el.dataset.key,el.value);});
   var rp=document.getElementById('migRconPass');if(rp&&rp.value!=='')fd.append('RCON_PASS',rp.value);
   var ap=document.getElementById('migAdminNew').value;if(ap!=='')fd.append('ADMIN_PASSWORD_NEW',ap);
   if(!confirm('¿Aplicar esta configuración? Se creará un backup de config.php antes de reemplazarlo.'))return;
   msg('Aplicando migración…','info');
   fetch('migration_api.php?action=apply',{method:'POST',body:fd,credentials:'same-origin'}).then(async r=>{var t=await r.text(),j;try{j=JSON.parse(t)}catch(e){throw new Error(t.slice(0,300)||'Respuesta inválida')}if(!r.ok||!j.ok)throw new Error(j.error||'No se pudo aplicar');return j;})
     .then(function(j){msg('✅ '+j.message+(j.backup?' · Backup: '+j.backup:''),'success');setTimeout(()=>location.reload(),1200);})
     .catch(e=>msg('❌ '+e.message,'danger'));
 };

 window.migReset=function(){
   var fd=new FormData();fd.append('csrf',csrf);fetch('migration_api.php?action=reset',{method:'POST',body:fd,credentials:'same-origin'}).then(()=>location.reload());
 };
 window.migValidateLocal=function(){msg('ℹ️ Los estados se recalculan al volver a analizar o al aplicar. Los campos rojos deben completarse antes de guardar.','info');};

 fetch('migration_api.php?action=status',{credentials:'same-origin'}).then(r=>r.json()).then(function(j){schema=j.schema||{};if(j.staged){msg('Hay una migración en staging de esta sesión. Vuelve a importar el archivo para refrescar el informe.','warning');}});
})();
</script>
