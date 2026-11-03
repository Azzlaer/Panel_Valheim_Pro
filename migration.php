<?php
$configPath=__DIR__.'/config.php';
if(is_file($configPath)) require_once $configPath;
if(session_status()===PHP_SESSION_NONE) session_start();
$installed=defined('PANEL_INSTALLED')?PANEL_INSTALLED:false;
if($installed && (empty($_SESSION['logged_in'])||$_SESSION['logged_in']!==true)){
    header('Location: index.php');
    exit;
}
if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Migration & Configuration Import Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{margin:0;background:linear-gradient(135deg,#07101c,#0b1220 50%,#111827);color:#e5e7eb;min-height:100vh;font-family:Segoe UI,Arial,sans-serif}
.top{padding:18px 24px;border-bottom:1px solid rgba(255,255,255,.08);background:rgba(17,24,39,.9);position:sticky;top:0;z-index:10}
.top a{text-decoration:none}.shell{padding:18px}
</style></head><body>
<div class="top d-flex justify-content-between align-items-center gap-3 flex-wrap">
  <div><strong>⚔️ Valheim Enterprise Panel</strong><div class="text-secondary small">Migration & Configuration Import Center</div></div>
  <div class="d-flex gap-2">
    <?php if($installed):?><a href="dashboard.php" class="btn btn-outline-light btn-sm">← Volver al panel</a><?php else:?><a href="install.php" class="btn btn-outline-light btn-sm">← Volver al instalador</a><?php endif;?>
  </div>
</div>
<div class="shell"><?php require __DIR__.'/pages/migration.php'; ?></div>
</body></html>