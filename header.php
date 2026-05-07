<?php
require_once "config.php";

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
    <title>Valheim Enterprise Panel</title>
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
            --accent:#3b82f6;
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
            min-height:100vh;
            background:linear-gradient(180deg, var(--bg-sidebar), var(--bg-sidebar-2));
            border-right:1px solid var(--border);
            box-shadow:var(--shadow);
            position:sticky;
            top:0;
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

        .sidebar .nav{
            gap:6px;
        }

        .sidebar .nav-link{
            color:#cbd5e1;
            border-radius:12px;
            padding:11px 14px;
            font-weight:600;
            font-size:14px;
            transition:all .18s ease;
            border:1px solid transparent;
        }

        .sidebar .nav-link:hover{
            background:rgba(255,255,255,0.04);
            color:#fff;
            border-color:rgba(255,255,255,0.05);
        }

        .sidebar .nav-link.active{
            background:linear-gradient(135deg, var(--accent), #1d4ed8);
            color:#fff;
            box-shadow:0 8px 20px rgba(59,130,246,0.25);
        }

        .sidebar .nav-link.text-danger{
            color:#fca5a5 !important;
        }

        .sidebar-footer{
            margin-top:18px;
            padding-top:14px;
            border-top:1px solid var(--border);
            color:var(--text-soft);
            font-size:12px;
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
                min-height:auto;
                position:relative;
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
                <div class="brand-eyebrow">LatinBattle.com</div>
                <h1 class="brand-title">⚔️ Valheim Panel</h1>
                <p class="brand-sub">Administración empresarial de servidor dedicado</p>
            </div>

            <div class="nav flex-column nav-pills">
                <a href="#" class="nav-link" data-section="install">🖥️ Instalar</a>
                <a href="#" class="nav-link active" data-section="pages/servers">🖥️ Servidores</a>
                <a href="#" class="nav-link" data-section="pages/backups">🗂️ Respaldos</a>
                <a href="#" class="nav-link" data-section="pages/maps">🗺️ Mapas</a>
                <a href="#" class="nav-link" data-section="pages/plugins">📊 Mods</a>
                <a href="#" class="nav-link" data-section="pages/cfg">⚙️ Archivos CFG</a>
                <a href="#" class="nav-link" data-section="pages/lists">📂 Listas</a>
                <a href="#" class="nav-link" data-section="pages/logs">📜 Logs</a>
                <a href="#" class="nav-link" data-section="pages/update">🔄 Actualización</a>
                <a href="#" class="nav-link" data-section="pages/rcon">🖥️ RCON</a>
                <!-- <a href="#" class="nav-link" data-section="pages/crons">⏱️ Cron Jobs</a> -->
                <a href="#" class="nav-link" data-section="pages/donaciones">📢 Donaciones</a>
                <a href="#" class="nav-link" data-section="pages/procesos_valheim">⚙️ Procesos</a>
                <a href="#" class="nav-link" data-section="pages/soporte">🆘 Soporte</a>
                <a href="logout.php" class="nav-link text-danger">🚪 Cerrar Sesión</a>
            </div>

            <div class="sidebar-footer">
                Powered by Azzlaer & ChatGPT OpenAI
            </div>
        </nav>

        <!-- Main content -->
        <div class="col-lg-10 col-md-9 main-area">
            <div class="topbar d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="topbar-title">Panel de Administración</h2>
                    <p class="topbar-sub">Gestión centralizada del ecosistema Valheim dedicado</p>
                </div>
                <div class="topbar-badge">● Sesión activa</div>
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
    $('.sidebar .nav-link').on('click', function(e){
        const page = $(this).data('section') || $(this).data('page');
        if (!page) return;

        e.preventDefault();

        $('.sidebar .nav-link').removeClass('active');
        $(this).addClass('active');

        $('#main').html(`
            <div class="loading-box">
                <div class="loading-spinner"></div>
                <div>Cargando módulo...</div>
            </div>
        `);

        const path = page.startsWith('pages/') ? page : 'pages/' + page;
        $('#main').load(path + '.php', function(response, status){
            if (status === 'error') {
                $('#main').html(`
                    <div class="loading-box text-danger">
                        <div style="font-size:28px; margin-bottom:10px;">⚠️</div>
                        <div>No se pudo cargar la sección solicitada.</div>
                    </div>
                `);
            }
        });
    });
});
</script>
</body>
</html>