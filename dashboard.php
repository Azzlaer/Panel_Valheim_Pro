<?php
require_once "config.php";

// Bloquea acceso si no hay sesión
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

include "header.php";
?>

<script>
$(function () {
  const $main = $("#main");

  $main.html(`
    <div class="loading-box">
      <div class="loading-spinner"></div>
      <div>Cargando módulo principal...</div>
    </div>
  `);

  const $default = $('.nav-link[data-section="pages/servers"]');

  if ($default.length) {
    $('.nav-link').removeClass('active');
    $default.addClass('active');
    $default.trigger('click');
  } else {
    $main.load("pages/servers.php", function(response, status){
      if (status === 'error') {
        $main.html(`
          <div class="welcome-card">
            <div class="welcome-icon">⚠️</div>
            <div class="welcome-title">No se pudo cargar el panel</div>
            <p class="welcome-text">
              Ocurrió un problema al intentar abrir la sección principal del sistema.
              Revisa que el archivo <code>pages/servers.php</code> exista y que no tenga errores.
            </p>
          </div>
        `);
      }
    });
  }
});
</script>