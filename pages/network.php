<?php
require_once __DIR__.'/../config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);exit('Acceso denegado');}
$csrf=$_SESSION['csrf_token']??'';
?>
<div class="container-fluid mt-4">
 <div class="p-4 rounded-4 border bg-dark mb-4"><h2>🌐 Network & Crossplay Center</h2><p class="text-secondary mb-0">Configura backend Steam/PlayFab, puertos y visibilidad usando las reglas oficiales de Valheim.</p></div>
 <div id="netMsg" class="alert alert-info">Cargando…</div>
 <div class="row g-4">
  <div class="col-lg-7"><div class="card bg-dark border-secondary h-100"><div class="card-body">
   <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Puerto base</label><input id="netPort" type="number" class="form-control"></div>
    <div class="col-md-6"><label class="form-label">Puerto adicional</label><input id="netPort2" class="form-control" disabled></div>
    <div class="col-md-6"><label class="form-label">Instance ID</label><input id="netInstance" class="form-control" placeholder="server-1"></div>
    <div class="col-md-6 d-flex align-items-end"><div><label class="me-3"><input type="checkbox" id="netPublic"> Público</label><label><input type="checkbox" id="netCross"> Crossplay</label></div></div>
    <div class="col-12"><button class="btn btn-primary" onclick="netSave()">💾 Guardar</button></div>
   </div>
  </div></div></div>
  <div class="col-lg-5"><div class="card bg-dark border-secondary h-100"><div class="card-body">
   <h5>Diagnóstico</h5><div id="netDiag" class="text-secondary">—</div>
  </div></div></div>
 </div>
</div>
<script>
(function(){
 var csrf=<?=json_encode($csrf)?>;
 function msg(t,c){var e=document.getElementById('netMsg');e.className='alert alert-'+c;e.textContent=t;}
 function load(){fetch('network_api.php?action=status',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);document.getElementById('netPort').value=j.port;document.getElementById('netPort2').value=j.port_plus_one;document.getElementById('netCross').checked=j.crossplay;document.getElementById('netPublic').checked=j.public;document.getElementById('netInstance').value=j.instanceid||'';document.getElementById('netDiag').innerHTML='<p><b>Backend:</b> '+j.backend+'</p><p><b>Puertos:</b> '+j.port+'-'+j.port_plus_one+'</p><p><b>Firewall Windows:</b> '+(j.firewall.rule_found===true?'✅ Regla por ejecutable detectada':(j.firewall.windows?'⚠️ No se detectó regla específica':'N/A'))+'</p><p>'+ (j.crossplay?'Crossplay usa relay PlayFab; no requiere port forwarding externo.':'Steam backend requiere que ambos puertos sean accesibles desde Internet.') +'</p>';msg('✅ Configuración cargada','success');}).catch(e=>msg(e.message,'danger'));}
 document.getElementById('netPort').addEventListener('input',function(){document.getElementById('netPort2').value=(parseInt(this.value||0,10)+1)||'';});
 window.netSave=function(){var f=new FormData();f.append('csrf',csrf);f.append('port',document.getElementById('netPort').value);if(document.getElementById('netCross').checked)f.append('crossplay','1');if(document.getElementById('netPublic').checked)f.append('public','1');f.append('instanceid',document.getElementById('netInstance').value);fetch('network_api.php?action=save',{method:'POST',body:f,credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);msg('✅ '+j.msg,'success');load();}).catch(e=>msg(e.message,'danger'));};
 load();
})();
</script>