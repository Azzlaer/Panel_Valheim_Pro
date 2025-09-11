<?php
require_once "config.php";

// Bloquea acceso si no hay sesión
if (empty($_SESSION['logged_in'])) {
    header("Location: index.php");
    exit;
}
?>
<?php include "header.php"; ?>

<!-- Contenido inicial opcional si no se ha cargado aún -->
<div class="p-4 text-center text-light">
  Cargando panel…
</div>

<?php include "footer.php"; ?>

<script>
// Cargar por defecto la sección de Servidores al entrar al dashboard
$(function () {
  const $default = $('.nav-link[data-section="pages/servers"]');
  if ($default.length) {
    $default.trigger('click');
  } else {
    // fallback: cargar manualmente por si cambió el selector
    $("#main").load("pages/servers.php");
  }
});
</script>
