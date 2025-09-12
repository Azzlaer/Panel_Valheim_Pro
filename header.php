<?php
require_once "config.php";

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>⚔️ Panel Valheim</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body { background: #121212; color: #eee; }
    .sidebar { min-height: 100vh; background: #1e1e1e; }
    .nav-link { color: #bbb; cursor:pointer; }
    .nav-link.active { background: #0d6efd; color: #fff; }
    main { padding: 20px; }
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3">
      <h3 class="text-light mb-4">⚔️ Valheim Panel</h3>
      <div class="nav flex-column nav-pills">
        <a class="nav-link active" data-page="servers.php">🖥️ Servidores</a>
        <a class="nav-link" data-page="metrics.php">📊 Métricas en vivo</a>
        <a class="nav-link" data-page="backups.php">🗂️ Respaldos</a>
        <a class="nav-link" data-page="maps.php">🗺️ Mapas</a>
        <a class="nav-link" data-page="plugins.php">📊 Archivos DB</a>
        <a class="nav-link" data-page="cfg.php">⚙️ Archivos CFG</a>
        <a class="nav-link" data-page="lists.php">📂 Listas</a>
        <a class="nav-link" data-page="logs.php">📜 Logs</a>
        <a class="nav-link" data-page="update.php">🔄 Actualización</a>
        <a class="nav-link" data-page="rcon.php">🖥️ RCON</a>
        <a class="nav-link" data-page="crons.php">⏱️ Cron Jobs</a>
        <a class="nav-link" data-page="alerts.php">📢 Alerts</a>
        <a class="nav-link" data-page="soporte.php">🆘 Soporte</a>
        <a href="logout.php" class="nav-link text-danger">🚪 Cerrar Sesión</a>
      </div>
    </nav>

    <!-- Main content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="main">
      <div class="text-center p-5 text-light">
        👋 Bienvenido al Panel de Administración de Valheim
      </div>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
function loadPage(page){
  fetch('pages/' + page, {credentials:'same-origin'})
    .then(r => {
      if(!r.ok) throw new Error('Error al cargar ' + page);
      return r.text();
    })
    .then(html => { document.getElementById('main').innerHTML = html; })
    .catch(e => { document.getElementById('main').innerHTML = '<div class="text-danger">'+e+'</div>'; });
}

// Navegación lateral
document.querySelectorAll('.nav-link[data-page]').forEach(link=>{
  link.addEventListener('click', e=>{
    e.preventDefault();
    document.querySelectorAll('.nav-link').forEach(l=>l.classList.remove('active'));
    link.classList.add('active');
    const page = link.getAttribute('data-page');
    loadPage(page);
  });
});

// Cargar la sección inicial
loadPage('servers.php');
</script>
</body>
</html>
