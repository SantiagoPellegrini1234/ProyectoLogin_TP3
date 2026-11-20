<?php
/**
 * ARCHIVO: login_app/api/logout.php
 * ---------------------------------------------------------------------
 * Cierra la sesión del usuario. Solo acepta POST con token CSRF para que
 * un sitio ajeno no pueda cerrar la sesión mediante un simple enlace.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/sesion.php';

iniciarSesionSegura();

// Solo POST con token válido; si no, vuelve al inicio sin tocar la sesión.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validarTokenCsrf($_POST['csrf_token'] ?? null)) {
    header('Location: ../index.php');
    exit;
}

// Vacía todas las variables de sesión.
$_SESSION = [];

// Borra la cookie de sesión en el navegador usando los mismos parámetros.
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// Destruye la sesión en el servidor.
session_destroy();

// Redirige al login.
header('Location: ../index.php?salida=ok');
exit;
