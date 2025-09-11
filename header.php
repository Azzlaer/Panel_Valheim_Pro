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
    <title>⚔️ Panel Valheim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #121212; color: #eee; }
        .sidebar { min-height: 100vh; background: #1e1e1e; }
        .nav-link { color: #bbb; }
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
        <a href="#" class="nav-link active" data-section="pages/servers">🖥️ Servidores</a>
        <a href="#" class="nav-link" data-section="pages/plugins">📊 Archivos DB</a>
        <a href="#" class="nav-link" data-section="pages/cfg">⚙️ Archivos CFG</a>
        <a href="#" class="nav-link" data-section="pages/lists">📂 Listas</a>
        <a href="#" class="nav-link" data-section="pages/logs">📜 Logs</a>
        <a href="#" class="nav-link" data-section="pages/update">🔄 Actualización</a>
        <a href="#" class="nav-link" data-section="pages/rcon">🖥️ RCON</a>
		<a href="#" class="nav-link" data-section="pages/crons">⏱️ Cron Jobs</a>
		<a href="#" class="nav-link" data-section="pages/alerts">📢 Alerts</a>		
        <a href="logout.php" class="nav-link text-danger">🚪 Cerrar Sesión</a>
      </div>
    </nav>

    <!-- Main content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="main">
      <div class="text-center p-5 text-light">
        👋 Bienvenido al Panel de Administración de Valheim
      </div>
	  
	  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>


<script>
$(function(){
  $('.sidebar .nav-link').on('click', function(e){
    const page = $(this).data('page');
    if (!page) {
      // Enlaces sin data-page (p.ej. logout) -> navegación normal
      return;
    }
    e.preventDefault(); // Solo prevenimos en los que cargan AJAX

    $('.sidebar .nav-link').removeClass('active');
    $(this).addClass('active');
    $('#main').html('<div class="p-5 text-center">Cargando…</div>');
    $('#main').load('pages/' + page);
  });
});
</script>
