<?php
require_once __DIR__.'/../config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);exit('Acceso denegado');}
$csrf=$_SESSION['csrf_token']??'';
?>
<div class="container-fluid mt-4">
 <div class="p-4 rounded-4 border bg-dark mb-4"><h2>🌱 World Creation Wizard</h2><p class="text-secondary mb-0">Prepara parámetros oficiales y deja que Valheim cree el mundo correctamente al próximo arranque.</p></div>
 <div id="wcMsg" class="alert alert-info">Completa los datos.</div>
 <div class="card bg-dark border-secondary"><div class="card-body">
  <div class="row g-3">
   <div class="col-md-6"><label class="form-label">Nombre del mundo</label><input id="wcName" class="form-control" placeholder="LatinBattle_Season3"></div>
   <div class="col-md-6"><label class="form-label">Preset</label><select id="wcPreset" class="form-select"><option>Normal</option><option>Casual</option><option>Easy</option><option>Hard</option><option>Hardcore</option><option>Immersive</option><option>Hammer</option></select></div>
   <div class="col-md-4"><label class="form-label">Combat</label><select id="wcCombat" class="form-select"><option value="">Preset</option><option>veryeasy</option><option>easy</option><option>hard</option><option>veryhard</option></select></div>
   <div class="col-md-4"><label class="form-label">Death Penalty</label><select id="wcDeath" class="form-select"><option value="">Preset</option><option>casual</option><option>veryeasy</option><option>easy</option><option>hard</option><option>hardcore</option></select></div>
   <div class="col-md-4"><label class="form-label">Resources</label><select id="wcResources" class="form-select"><option value="">Preset</option><option>muchless</option><option>less</option><option>more</option><option>muchmore</option><option>most</option></select></div>
   <div class="col-md-6"><label class="form-label">Raids</label><select id="wcRaids" class="form-select"><option value="">Preset</option><option>none</option><option>muchless</option><option>less</option><option>more</option><option>muchmore</option></select></div>
   <div class="col-md-6"><label class="form-label">Portals</label><select id="wcPortals" class="form-select"><option value="">Preset</option><option>casual</option><option>hard</option><option>veryhard</option></select></div>
   <div class="col-12"><div class="d-flex gap-3 flex-wrap"><label><input type="checkbox" id="k1"> nobuildcost</label><label><input type="checkbox" id="k2"> playerevents</label><label><input type="checkbox" id="k3"> passivemobs</label><label><input type="checkbox" id="k4"> nomap</label></div></div>
   <div class="col-12 d-flex gap-2 flex-wrap"><button class="btn btn-success" onclick="wcPrepare(false)">🌱 Preparar mundo</button><button class="btn btn-primary" onclick="wcPrepare(true)">🚀 Preparar e iniciar</button></div>
  </div>
 </div></div>
</div>
<script>
(function(){
 var csrf=<?=json_encode($csrf)?>;
 function msg(t,c){var e=document.getElementById('wcMsg');e.className='alert alert-'+c;e.textContent=t;}
 window.wcPrepare=function(startAfter){
   var fd=new FormData();fd.append('csrf',csrf);fd.append('name',document.getElementById('wcName').value);fd.append('preset',document.getElementById('wcPreset').value.toLowerCase());
   fd.append('combat',document.getElementById('wcCombat').value);fd.append('deathpenalty',document.getElementById('wcDeath').value);fd.append('resources',document.getElementById('wcResources').value);fd.append('raids',document.getElementById('wcRaids').value);fd.append('portals',document.getElementById('wcPortals').value);
   if(document.getElementById('k1').checked)fd.append('key_nobuildcost','1');if(document.getElementById('k2').checked)fd.append('key_playerevents','1');if(document.getElementById('k3').checked)fd.append('key_passivemobs','1');if(document.getElementById('k4').checked)fd.append('key_nomap','1');
   fetch('world_create_api.php?action=prepare',{method:'POST',body:fd,credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);msg('✅ '+j.msg,'success');if(startAfter){var s=new FormData();s.append('op','start');s.append('id','1');return fetch('api.php?action=server',{method:'POST',body:s,credentials:'same-origin'}).then(r=>r.json()).then(x=>{if(!x.ok)throw new Error(x.error||'No se pudo iniciar');msg('🚀 Mundo preparado y servidor iniciado. Valheim generará la estructura del mundo.','success');});}}).catch(e=>msg('❌ '+e.message,'danger'));
 };
})();
</script>
