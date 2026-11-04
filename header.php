<?php
require_once "config.php";
require_once __DIR__ . "/lib/panel_settings.php";
$panelSettings = panel_load_settings();
if (!empty($panelSettings['timezone'])) { @date_default_timezone_set($panelSettings['timezone']); }

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($panelSettings['panel_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{
            --bg-body:#0b1220;
            --bg-sidebar:#111827;
            --bg-sidebar-2:#0f172a;
            --bg-main:#0f172a;
            --bg-card:#111827;
            --border:rgba(255,255,255,0.08);
            --text-main:#f3f4f6;
            --text-soft:#9ca3af;
            --accent:<?= htmlspecialchars($panelSettings['accent']) ?>;
            --accent-hover:#2563eb;
            --danger:#ef4444;
            --success:#10b981;
            --warning:#f59e0b;
            --shadow:0 18px 40px rgba(0,0,0,0.35);
        }

        *{
            box-sizing:border-box;
        }

        html, body{
            height:100%;
        }

        body{
            margin:0;
            background:
                radial-gradient(circle at top left, rgba(59,130,246,0.08), transparent 20%),
                radial-gradient(circle at bottom right, rgba(16,185,129,0.05), transparent 18%),
                linear-gradient(135deg, #09111d 0%, #0b1220 40%, #0e1727 100%);
            color:var(--text-main);
            font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            overflow-x:hidden;
        }

        .app-shell{
            min-height:100vh;
        }

        .sidebar{
            height:100vh;
            min-height:100vh;
            background:linear-gradient(180deg, var(--bg-sidebar), var(--bg-sidebar-2));
            border-right:1px solid var(--border);
            box-shadow:var(--shadow);
            position:sticky;
            top:0;
            display:flex;
            flex-direction:column;
            overflow:hidden;
        }

        .brand-box{
            padding:22px 18px 18px;
            border-bottom:1px solid var(--border);
            margin-bottom:10px;
        }

        .brand-eyebrow{
            font-size:11px;
            text-transform:uppercase;
            letter-spacing:.14em;
            color:#93c5fd;
            font-weight:700;
            margin-bottom:8px;
        }

        .brand-title{
            font-size:24px;
            font-weight:800;
            line-height:1.1;
            margin:0;
            color:#fff;
        }

        .brand-sub{
            font-size:13px;
            color:var(--text-soft);
            margin-top:8px;
            margin-bottom:0;
        }

        .sidebar-search{
            position:relative;
            margin:4px 0 12px;
        }

        .sidebar-search input{
            width:100%;
            border:1px solid var(--border);
            background:rgba(2,6,23,.55);
            color:#e5e7eb;
            border-radius:12px;
            padding:10px 12px 10px 36px;
            font-size:13px;
            outline:none;
            transition:border-color .18s ease, background .18s ease;
        }

        .sidebar-search input:focus{
            border-color:rgba(96,165,250,.55);
            background:rgba(2,6,23,.8);
        }

        .sidebar-search-icon{
            position:absolute;
            left:12px;
            top:50%;
            transform:translateY(-50%);
            color:#64748b;
            pointer-events:none;
        }

        .sidebar-menu{
            flex:1;
            min-height:0;
            overflow-y:auto;
            padding-right:4px;
            scrollbar-width:thin;
            scrollbar-color:rgba(148,163,184,.28) transparent;
        }

        .sidebar-menu::-webkit-scrollbar{
            width:6px;
        }

        .sidebar-menu::-webkit-scrollbar-thumb{
            background:rgba(148,163,184,.25);
            border-radius:99px;
        }

        .menu-group{
            margin-bottom:8px;
            border:1px solid transparent;
            border-radius:14px;
            transition:background .18s ease, border-color .18s ease;
        }

        .menu-group.open{
            background:rgba(255,255,255,.018);
            border-color:rgba(255,255,255,.045);
        }

        .menu-group-toggle{
            width:100%;
            border:0;
            background:transparent;
            color:#94a3b8;
            display:flex;
            align-items:center;
            gap:10px;
            padding:10px 11px;
            border-radius:12px;
            font-size:11px;
            font-weight:800;
            text-transform:uppercase;
            letter-spacing:.08em;
            text-align:left;
            transition:all .18s ease;
        }

        .menu-group-toggle:hover{
            background:rgba(255,255,255,.035);
            color:#e2e8f0;
        }

        .menu-group-icon{
            width:22px;
            height:22px;
            display:grid;
            place-items:center;
            font-size:14px;
        }

        .menu-group-label{
            flex:1;
        }

        .menu-group-chevron{
            font-size:10px;
            transition:transform .18s ease;
            opacity:.7;
        }

        .menu-group.open .menu-group-chevron{
            transform:rotate(90deg);
        }

        .menu-group-items{
            display:grid;
            grid-template-rows:0fr;
            transition:grid-template-rows .22s ease;
        }

        .menu-group.open .menu-group-items{
            grid-template-rows:1fr;
        }

        .menu-group-items-inner{
            min-height:0;
            overflow:hidden;
            padding:0 4px;
        }

        .menu-group.open .menu-group-items-inner{
            padding-bottom:6px;
        }

        .sidebar .nav-link{
            color:#cbd5e1;
            border-radius:11px;
            padding:9px 10px;
            font-weight:650;
            font-size:13px;
            transition:all .18s ease;
            border:1px solid transparent;
            display:flex;
            align-items:center;
            gap:9px;
            margin:2px 0;
            min-height:39px;
        }

        .sidebar .nav-link .menu-icon{
            width:22px;
            height:22px;
            display:grid;
            place-items:center;
            flex:0 0 22px;
            font-size:15px;
        }

        .sidebar .nav-link .menu-text{
            flex:1;
            min-width:0;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        .sidebar .nav-link:hover{
            background:rgba(255,255,255,0.045);
            color:#fff;
            border-color:rgba(255,255,255,0.05);
            transform:translateX(2px);
        }

        .sidebar .nav-link.active{
            background:linear-gradient(135deg, var(--accent), #1d4ed8);
            color:#fff;
            box-shadow:0 7px 18px rgba(37,99,235,.28);
            border-color:rgba(147,197,253,.16);
        }

        .sidebar .nav-link.active::after{
            content:'';
            width:5px;
            height:5px;
            border-radius:50%;
            background:#dbeafe;
            box-shadow:0 0 10px rgba(219,234,254,.9);
        }

        .sidebar .nav-link.text-danger{
            color:#fca5a5 !important;
        }

        .sidebar .nav-link.text-danger:hover{
            background:rgba(239,68,68,.08);
            border-color:rgba(239,68,68,.14);
        }

        .sidebar-empty-search{
            display:none;
            color:#64748b;
            text-align:center;
            font-size:12px;
            padding:18px 10px;
        }

        .sidebar-footer{
            flex:0 0 auto;
            margin-top:10px;
            padding-top:12px;
            border-top:1px solid var(--border);
            color:var(--text-soft);
            font-size:11px;
        }

        .main-area{
            min-height:100vh;
            display:flex;
            flex-direction:column;
        }

        .topbar{
            background:rgba(17,24,39,0.76);
            backdrop-filter:blur(12px);
            border-bottom:1px solid var(--border);
            padding:16px 24px;
            position:sticky;
            top:0;
            z-index:20;
        }

        .topbar-title{
            font-size:20px;
            font-weight:800;
            margin:0;
            color:#fff;
        }

        .topbar-sub{
            font-size:13px;
            color:var(--text-soft);
            margin:2px 0 0;
        }

        .topbar-badge{
            background:rgba(16,185,129,0.12);
            color:#a7f3d0;
            border:1px solid rgba(16,185,129,0.2);
            border-radius:999px;
            padding:8px 12px;
            font-size:12px;
            font-weight:700;
        }

        #main{
            padding:24px;
        }

        .welcome-card{
            background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0));
            border:1px solid var(--border);
            border-radius:22px;
            box-shadow:var(--shadow);
            padding:42px 28px;
            text-align:center;
        }

        .welcome-icon{
            font-size:44px;
            margin-bottom:14px;
        }

        .welcome-title{
            font-size:30px;
            font-weight:800;
            margin-bottom:8px;
        }

        .welcome-text{
            color:var(--text-soft);
            max-width:700px;
            margin:0 auto;
            line-height:1.7;
        }

        .loading-box{
            background:rgba(255,255,255,0.02);
            border:1px solid var(--border);
            border-radius:18px;
            padding:48px 24px;
            text-align:center;
            color:var(--text-soft);
            font-weight:600;
        }

        .loading-spinner{
            width:42px;
            height:42px;
            border-radius:50%;
            border:3px solid rgba(255,255,255,0.08);
            border-top-color:var(--accent);
            animation:spin 0.8s linear infinite;
            margin:0 auto 14px;
        }

        @keyframes spin{
            to { transform:rotate(360deg); }
        }

        @media (max-width: 991.98px){
            .sidebar{
                height:auto;
                min-height:auto;
                max-height:none;
                position:relative;
                overflow:visible;
            }

            .sidebar-menu{
                max-height:62vh;
                overflow-y:auto;
            }

            .brand-box{
                padding:18px 14px;
            }

            .topbar{
                padding:14px 16px;
            }

            #main{
                padding:16px;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid app-shell">
    <div class="row g-0">

        <!-- Sidebar -->
        <nav class="col-lg-2 col-md-3 sidebar p-3">
            <div class="brand-box">
                <div class="brand-eyebrow"><?= htmlspecialchars($panelSettings['brand_name']) ?></div>
                <h1 class="brand-title">⚔️ Valheim Panel</h1>
                <p class="brand-sub"><?= htmlspecialchars($panelSettings['brand_subtitle']) ?></p>
            </div>

            <div class="sidebar-search">
                <span class="sidebar-search-icon">⌕</span>
                <input type="search" id="sidebar-search" placeholder="Buscar módulo..." autocomplete="off">
            </div>

            <div class="sidebar-menu" id="sidebar-menu">
                <div class="menu-group open" data-group="principal">
                    <button class="menu-group-toggle" type="button">
                        <span class="menu-group-icon">🏠</span>
                        <span class="menu-group-label">Principal</span>
                        <span class="menu-group-chevron">▶</span>
                    </button>
                    <div class="menu-group-items"><div class="menu-group-items-inner">
                        <a href="#" class="nav-link active" data-section="pages/overview"><span class="menu-icon">🏠</span><span class="menu-text">Overview</span></a>
                        <a href="#" class="nav-link" data-section="pages/servers"><span class="menu-icon">🖥️</span><span class="menu-text">Servidores</span></a>
                    </div></div>
                </div>

                <div class="menu-group open" data-group="mundos">
                    <button class="menu-group-toggle" type="button">
                        <span class="menu-group-icon">🌍</span>
                        <span class="menu-group-label">Mundos & Backups</span>
                        <span class="menu-group-chevron">▶</span>
                    </button>
                    <div class="menu-group-items"><div class="menu-group-items-inner">
                        <a href="#" class="nav-link" data-section="pages/backups"><span class="menu-icon">🗂️</span><span class="menu-text">Respaldos</span></a>
                        <a href="#" class="nav-link" data-section="pages/world_create"><span class="menu-icon">🌱</span><span class="menu-text">Crear Mundo</span></a>
                        <a href="#" class="nav-link" data-section="pages/maps"><span class="menu-icon">🌐</span><span class="menu-text">Mundos</span></a>
                    </div></div>
                </div>

                <div class="menu-group" data-group="mods">
                    <button class="menu-group-toggle" type="button">
                        <span class="menu-group-icon">🧩</span>
                        <span class="menu-group-label">Mods & BepInEx</span>
                        <span class="menu-group-chevron">▶</span>
                    </button>
                    <div class="menu-group-items"><div class="menu-group-items-inner">
                        <a href="#" class="nav-link" data-section="pages/bepinex"><span class="menu-icon">🧩</span><span class="menu-text">BepInEx Manager</span></a>
                        <a href="#" class="nav-link" data-section="pages/mod_market"><span class="menu-icon">🛒</span><span class="menu-text">Thunderstore</span></a>
                        <a href="#" class="nav-link" data-section="pages/plugins"><span class="menu-icon">📦</span><span class="menu-text">Mods Locales</span></a>
                        <a href="#" class="nav-link" data-section="pages/cfg"><span class="menu-icon">⚙️</span><span class="menu-text">Archivos CFG</span></a>
                    </div></div>
                </div>

                <div class="menu-group" data-group="administracion">
                    <button class="menu-group-toggle" type="button">
                        <span class="menu-group-icon">🛡️</span>
                        <span class="menu-group-label">Administración</span>
                        <span class="menu-group-chevron">▶</span>
                    </button>
                    <div class="menu-group-items"><div class="menu-group-items-inner">
                        <a href="#" class="nav-link" data-section="pages/lists"><span class="menu-icon">🛡️</span><span class="menu-text">Permisos</span></a>
                        <a href="#" class="nav-link" data-section="pages/rcon"><span class="menu-icon">⌨️</span><span class="menu-text">RCON</span></a>
                        <a href="#" class="nav-link" data-section="pages/players"><span class="menu-icon">👥</span><span class="menu-text">Players Live</span></a>
                        <a href="#" class="nav-link" data-section="pages/logs"><span class="menu-icon">📜</span><span class="menu-text">Logs</span></a>
                        <a href="#" class="nav-link" data-section="pages/network"><span class="menu-icon">🌐</span><span class="menu-text">Red & Crossplay</span></a>
                        <a href="#" class="nav-link" data-section="pages/update"><span class="menu-icon">🔄</span><span class="menu-text">Actualización</span></a>
                    </div></div>
                </div>

                <div class="menu-group" data-group="sistema">
                    <button class="menu-group-toggle" type="button">
                        <span class="menu-group-icon">⚙️</span>
                        <span class="menu-group-label">Sistema & Automatización</span>
                        <span class="menu-group-chevron">▶</span>
                    </button>
                    <div class="menu-group-items"><div class="menu-group-items-inner">
                        <a href="#" class="nav-link" data-section="pages/procesos_valheim"><span class="menu-icon">⚙️</span><span class="menu-text">Procesos</span></a>
                        <a href="#" class="nav-link" data-section="pages/automation"><span class="menu-icon">⏱️</span><span class="menu-text">Automatización</span></a>
                        <a href="#" class="nav-link" data-section="pages/events"><span class="menu-icon">📡</span><span class="menu-text">Eventos</span></a>
                        <a href="#" class="nav-link" data-section="pages/health"><span class="menu-icon">🩺</span><span class="menu-text">Health Center</span></a>
                        <a href="#" class="nav-link" data-section="pages/platform"><span class="menu-icon">🐧</span><span class="menu-text">Linux & Docker</span></a>
                    </div></div>
                </div>

                <div class="menu-group" data-group="panel">
                    <button class="menu-group-toggle" type="button">
                        <span class="menu-group-icon">🎛️</span>
                        <span class="menu-group-label">Panel</span>
                        <span class="menu-group-chevron">▶</span>
                    </button>
                    <div class="menu-group-items"><div class="menu-group-items-inner">
                        <a href="#" class="nav-link" data-section="pages/settings"><span class="menu-icon">🎛️</span><span class="menu-text">Configuración</span></a>
                        <a href="#" class="nav-link" data-section="pages/migration"><span class="menu-icon">🧭</span><span class="menu-text">Migración</span></a>
                        <a href="#" class="nav-link" data-section="pages/donaciones"><span class="menu-icon">📢</span><span class="menu-text">Donaciones</span></a>
                        <a href="#" class="nav-link" data-section="pages/soporte"><span class="menu-icon">🆘</span><span class="menu-text">Soporte</span></a>
                        <a href="logout.php" class="nav-link text-danger"><span class="menu-icon">🚪</span><span class="menu-text">Cerrar Sesión</span></a>
                    </div></div>
                </div>

                <div class="sidebar-empty-search" id="sidebar-empty-search">Sin módulos coincidentes.</div>
            </div>

            <div class="sidebar-footer">
                Powered by Azzlaer & ChatGPT OpenAI<br><span style="opacity:.75">v<?= htmlspecialchars($panelSettings['version']) ?></span>
            </div>
        </nav>

        <!-- Main content -->
        <div class="col-lg-10 col-md-9 main-area">
            <div class="topbar d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="topbar-title">Panel de Administración</h2>
                    <p class="topbar-sub">Gestión centralizada del ecosistema Valheim dedicado</p>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="topbar-badge" id="global-server-badge">● Panel activo</span>
                    <button class="btn btn-sm btn-outline-light position-relative" onclick="openEventCenter()">🔔 <span id="event-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span></button>
                    <span class="topbar-badge" style="background:rgba(59,130,246,.12);color:#bfdbfe;border-color:rgba(59,130,246,.2)">v<?= htmlspecialchars($panelSettings['version']) ?></span>
                </div>
            </div>

            <main id="main">
                <div class="welcome-card">
                    <div class="welcome-icon">👋</div>
                    <div class="welcome-title">Bienvenido al Panel de Valheim</div>
                    <p class="welcome-text">
                        Desde aquí puedes administrar tu servidor dedicado, revisar su estado,
                        actualizar archivos, gestionar configuraciones, respaldos, mapas, plugins
                        y operaciones avanzadas en un entorno más limpio y profesional.
                    </p>
                </div>
            </main>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(function(){
    const storageKey = 'vh_sidebar_groups_v2';
    let savedGroups = {};
    try { savedGroups = JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch(e) {}

    function saveGroups(){
        const state = {};
        $('.menu-group').each(function(){ state[$(this).data('group')] = $(this).hasClass('open'); });
        localStorage.setItem(storageKey, JSON.stringify(state));
    }

    $('.menu-group').each(function(){
        const group = $(this).data('group');
        if (Object.prototype.hasOwnProperty.call(savedGroups, group)) {
            $(this).toggleClass('open', !!savedGroups[group]);
        }
    });

    $('.menu-group-toggle').on('click', function(){
        const group = $(this).closest('.menu-group');
        group.toggleClass('open');
        saveGroups();
    });

    function activateLink(link){
        $('.sidebar .nav-link').removeClass('active');
        link.addClass('active');
        link.closest('.menu-group').addClass('open');
        saveGroups();
        const page = link.data('section') || link.data('page');
        if (page) localStorage.setItem('vh_sidebar_last_page', page);
    }

    $('.sidebar .nav-link[data-section], .sidebar .nav-link[data-page]').on('click', function(e){
        const link = $(this);
        const page = link.data('section') || link.data('page');
        if (!page) return;

        e.preventDefault();
        activateLink(link);

        $('#main').html(
            '<div class="loading-box">' +
                '<div class="loading-spinner"></div>' +
                '<div>Cargando módulo...</div>' +
            '</div>'
        );

        const path = page.startsWith('pages/') ? page : 'pages/' + page;
        $('#main').load(path + '.php', function(response, status){
            if (status === 'error') {
                $('#main').html(
                    '<div class="loading-box text-danger">' +
                        '<div style="font-size:28px; margin-bottom:10px;">⚠️</div>' +
                        '<div>No se pudo cargar la sección solicitada.</div>' +
                    '</div>'
                );
            }
        });
    });

    $('#sidebar-search').on('input', function(){
        const q = ($(this).val() || '').toString().trim().toLowerCase();
        let totalVisible = 0;

        $('.menu-group').each(function(){
            const group = $(this);
            let visibleInGroup = 0;

            group.find('.nav-link').each(function(){
                const link = $(this);
                const text = link.text().toLowerCase();
                const match = !q || text.includes(q);
                link.toggle(match);
                if (match) visibleInGroup++;
            });

            group.toggle(visibleInGroup > 0);
            if (q && visibleInGroup > 0) group.addClass('open');
            totalVisible += visibleInGroup;
        });

        $('#sidebar-empty-search').toggle(totalVisible === 0);

        if (!q) {
            $('.menu-group').show();
            $('.menu-group').each(function(){
                const group = $(this).data('group');
                if (Object.prototype.hasOwnProperty.call(savedGroups, group)) {
                    $(this).toggleClass('open', !!savedGroups[group]);
                }
            });
            const active = $('.sidebar .nav-link.active').first();
            if (active.length) active.closest('.menu-group').addClass('open');
        }
    });

    const lastPage = localStorage.getItem('vh_sidebar_last_page');
    if (lastPage && lastPage !== 'pages/overview') {
        const lastLink = $('.sidebar .nav-link[data-section="' + lastPage + '"]');
        if (lastLink.length) {
            activateLink(lastLink);
            lastLink.trigger('click');
        }
    }
});
</script>

<script>
window.openEventCenter=function(){
  const link=document.querySelector('[data-section="pages/events"]'); if(link) link.click();
};
(function(){
  let lastSeen=parseInt(localStorage.getItem('vh_event_seen')||'0',10);
  function poll(){
    fetch('api.php?action=events_list',{credentials:'same-origin'}).then(r=>r.json()).then(j=>{
      if(!j.ok)return; const items=j.items||[]; const fresh=items.filter(e=>(e.ts||0)>lastSeen);
      const badge=document.getElementById('event-count'); if(badge){badge.textContent=fresh.length;badge.classList.toggle('d-none',fresh.length===0);}
      if(items.length){ const newest=Math.max(...items.map(e=>e.ts||0)); if(document.querySelector('[data-section="pages/events"].active')){localStorage.setItem('vh_event_seen',String(newest));lastSeen=newest;if(badge)badge.classList.add('d-none');}}
    }).catch(()=>{});
  }
  setInterval(()=>{fetch('automation_worker.php',{credentials:'same-origin'}).catch(()=>{});poll();},60000);
  poll();
})();
</script>
</body>
</html>