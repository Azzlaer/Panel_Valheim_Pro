<?php
require_once __DIR__ . "/../config.php";   // ✅ Ruta corregida

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit("Acceso denegado");
}

// Función para listar los .dll en la carpeta de plugins
function listDBFiles($dir) {
    $files = glob($dir . DIRECTORY_SEPARATOR . "*.dll");
    return $files ? $files : [];
}

$dbFiles = listDBFiles(PLUGINS_DIR);
?>
<div class="container mt-4">
    <h2>📊 Archivos DB en Plugins</h2>
    <p>Total encontrados: <b><?= count($dbFiles) ?></b></p>

    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle text-center">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Ruta Completa</th>
                    <th>Tamaño</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dbFiles)): ?>
                    <tr><td colspan="3">⚠️ No se encontraron archivos .dll en la carpeta de plugins.</td></tr>
                <?php else: ?>
                    <?php foreach ($dbFiles as $file): ?>
                        <tr>
                            <td><?= htmlspecialchars(basename($file)) ?></td>
                            <td class="text-start"><?= htmlspecialchars($file) ?></td>
                            <td><?= filesize($file) ?> bytes</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
