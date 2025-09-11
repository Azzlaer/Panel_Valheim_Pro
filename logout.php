<?php
require_once __DIR__ . '/config.php';

// Vaciar variables de sesión
$_SESSION = [];

// Borrar cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),           // nombre de la cookie de sesión
        '',                       // valor vacío
        time() - 42000,           // expirada
        $params['path'] ?? '/',   // usa el mismo path que definiste (p.ej. /valheim)
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: index.php');
exit;
