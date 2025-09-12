<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['logged_in'])) exit('Acceso denegado');

if (!empty($_POST['file'])) {
    $file = basename($_POST['file']);
    $path = BACKUP_DIR . '/' . $file;
    if (file_exists($path)) unlink($path);
}
header('Location: pages/backups.php');
