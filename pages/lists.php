<?php
require_once __DIR__.'/../config.php';
if(empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true){http_response_code(403);exit('Acceso denegado');}
$csrf=$_SESSION['csrf_token']??'';
?>
<div class="container-fluid mt-4">
 <div class="p-4 rounded-4 border bg-dark mb-4"><h2>🛡️ Permissions Center V2</h2><p class="text-secondary mb-0">Administra Platform User IDs para admins, baneados y whitelist.</p></div>
 <div id="pvWarn" class="alert alert-warning d-none">⚠️ <strong>Whitelist activa:</strong> permittedlist.txt contiene usuarios; todos los demás quedan excluidos del servidor.</div>
 <div class="row g-4" id="pvCards"></div>
</div>
<script>
(function(){
 var csrf=<?=json_encode($csrf)?>;
 var defs={'adminlist.txt':['👑 Administradores','primary'],'bannedlist.txt':['🚫 Baneados','danger'],'permittedlist.txt':['✅ Permitidos','success']};
 function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
 function post(a,p){var f=new FormData();f.append('csrf',csrf);Object.keys(p).forEach(k=>f.append(k,p[k]));return fetch('permissions_api.php?action='+a,{method:'POST',body:f,credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);return j;});}
 function load(){fetch('permissions_api.php?action=status',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{if(!j.ok)throw new Error(j.error);document.getElementById('pvWarn').classList.toggle('d-none',!j.whitelist_active);var root=document.getElementById('pvCards');root.innerHTML=Object.keys(defs).map(f=>{var d=defs[f],rows=j.lists[f]||[];return '<div class="col-lg-4"><div class="card bg-dark border-secondary h-100"><div class="card-header"><strong>'+d[0]+'</strong><span class="badge bg-'+d[1]+' float-end">'+rows.length+'</span></div><div class="card-body"><div class="input-group mb-3"><input class="form-control" id="pv-'+f+'" placeholder="Steam_7656119..."><button class="btn btn-'+d[1]+'" onclick="pvAdd(\''+f+'\')">Agregar</button></div><div>'+ (rows.length?rows.map(x=>'<div class="d-flex justify-content-between align-items-center border-bottom py-2"><code>'+esc(x)+'</code><button class="btn btn-sm btn-outline-danger" onclick="pvDel(\''+f+'\',\''+encodeURIComponent(x)+'\')">✕</button></div>').join(''):'<span class="text-secondary">Sin entradas</span>') +'</div></div></div></div>';}).join('');}).catch(e=>alert(e.message));}
 window.pvAdd=function(f){var id=document.getElementById('pv-'+f).value.trim();post('add',{file:f,id:id}).then(load).catch(e=>alert('❌ '+e.message));};
 window.pvDel=function(f,id){id=decodeURIComponent(id);if(!confirm('¿Eliminar '+id+'?'))return;post('delete',{file:f,id:id}).then(load).catch(e=>alert('❌ '+e.message));};
 load();
})();
</script>