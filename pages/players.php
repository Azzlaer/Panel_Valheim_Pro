<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { http_response_code(403); exit('Acceso denegado'); }
$csrf = $_SESSION['csrf_token'] ?? '';
?>
<style>
.players-wrap .hero{background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(59,130,246,.10),rgba(17,24,39,.78));border:1px solid rgba(255,255,255,.08);border-radius:22px;padding:24px;margin-bottom:22px;box-shadow:0 18px 40px rgba(0,0,0,.24)}
.players-wrap .hero h2{color:#fff;font-weight:850;margin:0}.players-wrap .muted{color:#9ca3af}.players-wrap .stats{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:20px}.players-wrap .stat{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:17px;padding:16px}.players-wrap .label{font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.08em}.players-wrap .value{font-size:23px;font-weight:850;color:#fff;margin-top:6px}.players-wrap .cardx{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:20px;overflow:hidden;margin-bottom:20px}.players-wrap .head{padding:17px 19px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}.players-wrap .head h3{font-size:18px;color:#fff;margin:0;font-weight:800}.players-wrap .table-dark{--bs-table-bg:transparent;--bs-table-striped-bg:rgba(255,255,255,.02);--bs-table-color:#e5e7eb;--bs-table-border-color:rgba(255,255,255,.06);margin:0}.players-wrap .btn{border-radius:11px;font-weight:700}.players-wrap .raw{font-family:Consolas,monospace;font-size:12px;color:#94a3b8;word-break:break-word}.players-wrap .badge-soft{background:rgba(59,130,246,.14);color:#bfdbfe;border:1px solid rgba(59,130,246,.22)}.players-wrap .list-box{max-height:210px;overflow:auto;background:#0b1220;border-radius:12px;padding:12px;color:#cbd5e1;font-family:Consolas,monospace;font-size:12px;white-space:pre-wrap}@media(max-width:1100px){.players-wrap .stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.players-wrap .stats{grid-template-columns:1fr}}
</style>
<div class="container-fluid mt-4 players-wrap">
  <div class="hero d-flex justify-content-between align-items-start gap-3 flex-wrap">
    <div><h2>👥 Players Live Center</h2><div class="muted mt-2">Jugadores conectados, estadísticas del servidor y moderación mediante Tristan / ValheimRcon.</div></div>
    <div class="d-flex gap-2 flex-wrap"><button class="btn btn-outline-light" onclick="plRefresh(true)">↻ Actualizar</button><a class="btn btn-outline-info" href="https://thunderstore.io/c/valheim/p/Tristan/ValheimRcon/" target="_blank" rel="noopener">🧩 ValheimRcon</a></div>
  </div>

  <div class="stats">
    <div class="stat"><div class="label">RCON</div><div class="value" id="pl-rcon">⏳</div></div>
    <div class="stat"><div class="label">Online</div><div class="value" id="pl-online">0</div></div>
    <div class="stat"><div class="label">FPS servidor</div><div class="value" id="pl-fps">-</div></div>
    <div class="stat"><div class="label">Memoria Mono</div><div class="value" id="pl-mono">-</div></div>
    <div class="stat"><div class="label">Día mundo</div><div class="value" id="pl-day">-</div></div>
  </div>

  <div class="cardx">
    <div class="head"><div><h3>🎮 Jugadores conectados</h3><small class="muted">Actualización automática cada 8 segundos.</small></div><span id="pl-mod" class="badge badge-soft">Comprobando mod…</span></div>
    <div class="table-responsive"><table class="table table-dark table-striped align-middle text-center"><thead><tr><th>Jugador</th><th>SteamID / ID</th><th>Posición</th><th>Respuesta RCON</th><th style="min-width:330px">Acciones</th></tr></thead><tbody id="pl-body"><tr><td colspan="5" class="py-4">Cargando…</td></tr></tbody></table></div>
  </div>

  <div class="row g-3">
    <div class="col-lg-4"><div class="cardx h-100"><div class="head"><h3>👑 Administradores</h3></div><div class="p-3"><div id="pl-admins" class="list-box">-</div></div></div></div>
    <div class="col-lg-4"><div class="cardx h-100"><div class="head"><h3>✅ Permitidos</h3></div><div class="p-3"><div id="pl-permitted" class="list-box">-</div></div></div></div>
    <div class="col-lg-4"><div class="cardx h-100"><div class="head"><h3>🚫 Baneados</h3></div><div class="p-3"><div id="pl-bans" class="list-box">-</div></div></div></div>
  </div>

  <div class="cardx mt-3"><div class="head"><div><h3>📢 Mensaje global</h3><small class="muted">Envía un mensaje mediante <code>showMessage</code>.</small></div></div><div class="p-3"><div class="input-group"><input id="pl-message" class="form-control bg-dark text-light border-secondary" placeholder="Mensaje para todos los jugadores"><button class="btn btn-primary" onclick="plMessage()">📣 Enviar</button></div></div></div>
</div>
<script>
(function(){
const csrf=<?= json_encode($csrf, JSON_UNESCAPED_UNICODE) ?>;
let busy=false;
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
window.plRefresh=function(manual=false){if(busy)return;busy=true;fetch('api.php?action=rcon_live_status',{credentials:'same-origin'}).then(r=>r.json()).then(d=>{if(!d.ok)throw new Error(d.error||'RCON no disponible');document.getElementById('pl-rcon').textContent='🟢 Online';document.getElementById('pl-online').textContent=d.stats?.online ?? d.players.length;document.getElementById('pl-fps').textContent=d.stats?.fps ?? '-';document.getElementById('pl-mono').textContent=(d.stats?.mono_mb!=null?d.stats.mono_mb+' MB':'-');document.getElementById('pl-day').textContent=d.stats?.day ?? '-';document.getElementById('pl-mod').textContent=d.mod?.detected?'✅ ValheimRcon detectado':'⚠️ Mod no detectado por archivos';document.getElementById('pl-admins').textContent=(d.admins||[]).join('\n')||'Sin entradas';document.getElementById('pl-permitted').textContent=(d.permitted||[]).join('\n')||'Sin entradas';document.getElementById('pl-bans').textContent=(d.bans||[]).join('\n')||'Sin entradas';const tb=document.getElementById('pl-body');if(!d.players.length){tb.innerHTML='<tr><td colspan="5" class="py-4 text-secondary">📭 No hay jugadores conectados.</td></tr>';}else{tb.innerHTML=d.players.map(p=>`<tr><td class="fw-bold">${esc(p.name)}</td><td><code>${esc(p.steam_id||'-')}</code></td><td>${esc(p.position||'-')}</td><td class="text-start"><span class="raw">${esc(p.raw)}</span></td><td><div class="d-flex justify-content-center gap-1 flex-wrap"><button class="btn btn-sm btn-outline-info" onclick='plDetails(${JSON.stringify(p.steam_id||p.name)})'>🔎 Detalles</button><button class="btn btn-sm btn-outline-warning" onclick='plAct("kick",${JSON.stringify(p.steam_id||p.name)})'>🦵 Kick</button><button class="btn btn-sm btn-danger" onclick='plAct("ban",${JSON.stringify(p.steam_id||p.name)})'>🚫 Ban</button>${p.steam_id?`<button class="btn btn-sm btn-outline-success" onclick='plAct("add_admin",${JSON.stringify(p.steam_id)})'>👑 Admin</button><button class="btn btn-sm btn-outline-primary" onclick='plAct("add_permitted",${JSON.stringify(p.steam_id)})'>✅ Permitir</button>`:''}</div></td></tr>`).join('');}}).catch(e=>{document.getElementById('pl-rcon').textContent='🔴 Offline';if(manual)alert('⚠️ '+e.message);}).finally(()=>busy=false);};
window.plAct=function(action,target){if(['kick','ban'].includes(action)&&!confirm('¿Confirmas '+action+' para '+target+'?'))return;const b=new URLSearchParams({action_name:action,target:target,csrf:csrf});fetch('api.php?action=rcon_player_action',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:b.toString(),credentials:'same-origin'}).then(r=>r.json()).then(d=>{alert(d.ok?'✅ '+(d.response||'Operación realizada'):'❌ '+(d.error||'Error'));if(d.ok)setTimeout(()=>plRefresh(false),500);});};
window.plDetails=function(target){const b=new URLSearchParams({target:target});fetch('api.php?action=rcon_find_player',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:b.toString(),credentials:'same-origin'}).then(r=>r.json()).then(d=>alert(d.ok?'🔎 '+(d.response||'Sin respuesta'):'❌ '+(d.error||'Error')));};
window.plMessage=function(){const input=document.getElementById('pl-message');const msg=input.value.trim();if(!msg)return;const b=new URLSearchParams({action_name:'message',target:msg,csrf:csrf});fetch('api.php?action=rcon_player_action',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:b.toString(),credentials:'same-origin'}).then(r=>r.json()).then(d=>{alert(d.ok?'✅ Mensaje enviado':'❌ '+(d.error||'Error'));if(d.ok)input.value='';});};
plRefresh(false);window.playersLiveTimer&&clearInterval(window.playersLiveTimer);window.playersLiveTimer=setInterval(()=>{if(document.getElementById('pl-body'))plRefresh(false);else clearInterval(window.playersLiveTimer);},8000);
})();
</script>
