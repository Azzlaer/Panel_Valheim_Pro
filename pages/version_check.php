<?php
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Acceso denegado');
}
?>
<div class="container-fluid mt-4">
    <div class="p-4 rounded-4 border bg-dark mb-4">
        <h2 class="mb-2">🧾 Versiones del Servidor</h2>
        <p class="text-secondary mb-0">
            Centro de verificación para comparar la instalación local de Valheim Dedicated Server con la rama pública de Steam y la versión publicada por Iron Gate.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase mb-2">Valheim Dedicated Server</div>
                    <h4 class="mb-3">Steam App ID 896660</h4>
                    <div class="text-secondary">Build instalado</div>
                    <div class="fs-4 fw-bold">—</div>
                    <div class="text-secondary mt-3">Rama detectada</div>
                    <div class="fw-semibold">—</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase mb-2">Steam</div>
                    <h4 class="mb-3">Última versión pública</h4>
                    <div class="text-secondary">Build disponible</div>
                    <div class="fs-4 fw-bold">—</div>
                    <div class="text-secondary mt-3">Estado</div>
                    <span class="badge bg-secondary">Pendiente de consultar</span>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-body">
                    <div class="text-secondary small text-uppercase mb-2">Iron Gate</div>
                    <h4 class="mb-3">Versión de Valheim</h4>
                    <div class="text-secondary">Versión publicada</div>
                    <div class="fs-4 fw-bold">—</div>
                    <div class="text-secondary mt-3">Canal</div>
                    <div class="fw-semibold">Stable / Public</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark border-secondary mt-4">
        <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h5 class="mb-1">🔍 Verificador de versiones</h5>
                <p class="text-secondary mb-0">
                    Esta nueva sección queda preparada para leer el build local, consultar Steam y mostrar la versión oficial de Iron Gate.
                </p>
            </div>
            <button class="btn btn-primary" disabled>🔍 Buscar actualización</button>
        </div>
    </div>
</div>