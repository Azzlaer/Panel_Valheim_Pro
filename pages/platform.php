<?php
require_once __DIR__.'/../config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);exit('Acceso denegado');}
?>
<div class="container-fluid mt-4">
 <div class="p-4 rounded-4 border bg-dark mb-4"><h2>🐧 Linux & Docker Readiness</h2><p class="text-secondary mb-0">Diagnóstico multiplataforma para dependencias oficiales y scripts Docker de Valheim.</p></div>
 <div id="plMsg" class="alert alert-info">Analizando plataforma…</div>
 <div class="row g-4">
  <div class="col-lg-6"><div class="card bg-dark border-secondary h-100"><div class="card-header"><strong>🐧 Linux</strong></div><div class="card-body" id="plLinux">—</div></div></div>
  <div class="col-lg-6"><div class="card bg-dark border-secondary h-100"><div class="card-header"><strong>🐳 Docker</strong></div><div class="card-body" id="plDocker">—</div></div></div>
 </div>
</div>
<script>
(function(){
 fetch('platform_api.php',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);document.getElementById('plMsg').className='alert alert-success';document.getElementById('plMsg').textContent='✅ Plataforma detectada: '+j.os+' · PHP '+j.php;
 var l=document.getElementById('plLinux'),d=document.getElementById('plDocker');
 if(j.linux){l.innerHTML='<p><b>GLIBC:</b> '+j.linux.glibc+'</p>'+Object.keys(j.linux.packages).map(k=>'<p>'+ (j.linux.packages[k]?'✅':'❌') +' '+k+'</p>').join('')+'<p>'+(j.linux.start_server_sh?'✅':'❌')+' start_server.sh</p><p>'+(j.linux.docker_script?'✅':'❌')+' docker_start_server.sh</p>';}else{l.innerHTML='<p class="text-secondary">Este host no es Linux. El módulo queda disponible para futuras instalaciones Linux.</p>';}
 d.innerHTML='<p><b>Instalado:</b> '+(j.docker.installed?'✅ Sí':'❌ No')+'</p><p><b>Daemon:</b> '+(j.docker.daemon?'✅ Activo':'❌ Inactivo')+'</p><pre class="text-secondary">'+(j.docker.version||j.docker.note||'')+'</pre>';
 }).catch(e=>{document.getElementById('plMsg').className='alert alert-danger';document.getElementById('plMsg').textContent='❌ '+e.message;});
})();
</script>