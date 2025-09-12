<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['logged_in'])) exit('Acceso denegado');
if (!isset($_GET['file'])) exit('Falta archivo');

$file = basename($_GET['file']);
$path = BACKUP_DIR . '/' . $file;
if (!file_exists($path)) exit('No existe');

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $file . '"');
readfile($path);
