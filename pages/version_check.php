<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
$csrf = $_SESSION['csrf_token'] ?? '';
?>
<style>
.vc-card{background:rgba(17,24,39,.94);border:1px solid rgba(255,255,255,.08);border-radius:18px;min-height:230px}
.vc-label{font-size:12px;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em}
.vc-value{font-size:28px;font-weight:800;color:#fff;line-height:1.2}
.vc-sub{font-size:13px;color:#94a3b8}
.vc-status{border-radius:999px;padding:7px 11px;font-weight:700;font-size:12px}
.vc-path{font-family:Consolas,monospace;font-size:11px;word-break:break-all;color:#94a3b8}
</style>

<div class="container-fluid mt-4">
    <div class="p-4 rounded-4 border bg-dark mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h2 class="mb-2">🧾 Versiones del Servidor</h2>
                <p class="text-secondary mb-0">
                    Compara tu instalación local de Valheim Dedicated Server con la rama pública de Steam y la última versión publicada por Iron Gate.
                </p>
            </div>
            <button id="vcCheckBtn" class="btn btn-primary" onclick="vcCheck()">🔍 Buscar actualización</button>
        </div>
    </div>

    <div id="vcMsg" class="alert alert-info">Leyendo versión local…</div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card vc-card h-100">
                <div class="card-body">
                    <div class="vc-label mb-3">Valheim Dedicated Server</div>
                    <div class="vc-sub">Steam App ID</div>
                    <div class="fw-bold mb-3">896660</div>

                    <div class="vc-sub">Build instalado</div>
                    <div id="vcLocalBuild" class="vc-value">—</div>

                    <div class="vc-sub mt-3">Rama detectada</div>
                    <div id="vcLocalBranch" class="fw-semibold">—</div>

                    <div class="vc-sub mt-3">Manifest</div>
                    <div id="vcManifest" class="vc-path">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card vc-card h-100">
                <div class="card-body">
                    <div class="vc-label mb-3">Steam</div>
                    <div class="vc-sub">Build disponible · rama consultada</div>
                    <div id="vcSteamBuild" class="vc-value">—</div>

                    <div class="vc-sub mt-3">Rama comparada</div>
                    <div id="vcSteamBranch" class="fw-semibold">—</div>

                    <div class="vc-sub mt-3">Build public (referencia)</div>
                    <div id="vcPublicBuild" class="fw-semibold">—</div>

                    <div class="vc-sub mt-3">Estado</div>
                    <div class="mt-2">
                        <span id="vcSteamStatus" class="badge bg-secondary vc-status">Pendiente de consultar</span>
                    </div>

                    <div id="vcSteamError" class="small text-warning mt-3"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card vc-card h-100">
                <div class="card-body">
                    <div class="vc-label mb-3">Iron Gate</div>
                    <div class="vc-sub">Última versión publicada</div>
                    <div id="vcIronVersion" class="vc-value">—</div>

                    <div class="vc-sub mt-3">Canal</div>
                    <div class="fw-semibold">Stable / Public</div>

                    <div class="mt-3">
                        <a id="vcIronLink" href="https://valheim.com/news/" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light">
                            🌐 Ver noticias oficiales
                        </a>
                    </div>

                    <div id="vcIronError" class="small text-warning mt-3"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark border-secondary mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h5 class="mb-1">📡 Última comprobación</h5>
                    <p id="vcCheckedAt" class="text-secondary mb-0">Todavía no se consultó Steam/Iron Gate.</p>
                </div>
                <div class="small text-secondary">
                    Consultar versiones no inicia, detiene ni modifica el servidor.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const csrf = <?=json_encode($csrf)?>;

    function esc(s){
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
        }[c]));
    }

    function msg(text, type){
        const box = document.getElementById('vcMsg');
        if (!box) return;
        box.className = 'alert alert-' + (type || 'info');
        box.textContent = text;
    }

    async function parseJsonResponse(r){
        const text = await r.text();
        let j;
        try { j = JSON.parse(text); }
        catch(e){
            const clean = text.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim().slice(0,350);
            throw new Error(clean || 'Respuesta inválida del servidor');
        }
        if (!r.ok || !j.ok) throw new Error(j.error || 'Error de consulta');
        return j;
    }

    function renderLocal(local){
        document.getElementById('vcLocalBuild').textContent = local?.buildid || '—';
        document.getElementById('vcLocalBranch').textContent = local?.branch || '—';
        document.getElementById('vcManifest').textContent = local?.manifest || 'No encontrado';

        if (!local?.ok && local?.error) {
            msg('⚠️ ' + local.error, 'warning');
        }
    }

    function renderComparison(cmp){
        const el = document.getElementById('vcSteamStatus');
        const state = cmp?.state || 'unknown';
        const cfg = {
            current:['bg-success','✅ Servidor actualizado'],
            outdated:['bg-warning text-dark','⚠️ Actualización disponible'],
            different:['bg-info text-dark','ℹ️ Build diferente a public'],
            unknown:['bg-secondary','❔ No se pudo comparar']
        };
        const x = cfg[state] || cfg.unknown;
        el.className = 'badge vc-status ' + x[0];
        el.textContent = x[1];
    }

    async function loadLocal(){
        try{
            const j = await fetch('version_check_api.php?action=local',{
                credentials:'same-origin',
                cache:'no-store'
            }).then(parseJsonResponse);

            renderLocal(j.local || {});
            if (j.local?.ok) msg('✅ Build local detectado. Puedes buscar la última versión disponible.', 'success');
        }catch(e){
            msg('❌ ' + e.message, 'danger');
        }
    }

    window.vcCheck = async function(){
        const btn = document.getElementById('vcCheckBtn');
        btn.disabled = true;
        btn.textContent = '⏳ Consultando…';
        msg('Consultando SteamCMD e Iron Gate. Esto puede tardar unos segundos…', 'info');

        const body = new URLSearchParams();
        body.set('csrf', csrf);

        try{
            const j = await fetch('version_check_api.php?action=check',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:body.toString(),
                credentials:'same-origin',
                cache:'no-store'
            }).then(parseJsonResponse);

            renderLocal(j.local || {});

            document.getElementById('vcSteamBuild').textContent = j.steam?.buildid || '—';
            document.getElementById('vcSteamBranch').textContent = j.steam?.branch || j.local?.branch || '—';
            document.getElementById('vcPublicBuild').textContent = j.steam?.public_buildid || '—';
            document.getElementById('vcSteamError').textContent = j.steam?.ok ? '' : (j.steam?.error || '');
            renderComparison(j.comparison || {});

            document.getElementById('vcIronVersion').textContent = j.irongate?.version || '—';
            document.getElementById('vcIronError').textContent = j.irongate?.ok ? '' : (j.irongate?.error || '');
            if (j.irongate?.url) document.getElementById('vcIronLink').href = j.irongate.url;

            if (j.checked_at) {
                const d = new Date(j.checked_at);
                document.getElementById('vcCheckedAt').textContent = 'Comprobado: ' + d.toLocaleString();
            }

            const steamOk = !!j.steam?.ok;
            const ironOk = !!j.irongate?.ok;
            if (steamOk && ironOk) msg('✅ Comprobación completada correctamente.', 'success');
            else if (steamOk || ironOk) msg('⚠️ Comprobación parcial: una fuente no respondió correctamente.', 'warning');
            else msg('❌ No se pudo consultar Steam ni Iron Gate.', 'danger');
        }catch(e){
            msg('❌ ' + e.message, 'danger');
        }finally{
            btn.disabled = false;
            btn.textContent = '🔍 Buscar actualización';
        }
    };

    loadLocal();
})();
</script>
