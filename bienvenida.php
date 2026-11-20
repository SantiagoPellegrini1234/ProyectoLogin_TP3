<?php
/**
 * ARCHIVO: login_app/bienvenida.php
 * ---------------------------------------------------------------------
 * Pantalla privada: solo accesible con sesión iniciada.
 * Muestra el mensaje de bienvenida y el botón para cerrar sesión.
 */

declare(strict_types=1);

require_once __DIR__ . '/src/sesion.php';

iniciarSesionSegura();

// Guardia de acceso: sin sesión válida se redirige al login.
if (!usuarioLogueado()) {
    header('Location: index.php');
    exit;
}

// Evita que el botón "Atrás" del navegador muestre esta página en caché
// después de cerrar sesión.
header('Cache-Control: no-store, no-cache, must-revalidate');

// Datos del usuario guardados en la sesión durante el login.
$nombre   = $_SESSION['usuario_nombre'] ?? '';
$apellido = $_SESSION['usuario_apellido'] ?? '';
$csrf     = obtenerTokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <main class="contenedor">
        <section class="tarjeta centrado">
            <!-- Icono decorativo creado solo con CSS/emoji (sin librerías). -->
            <div class="insignia" aria-hidden="true">✔</div>
            <!-- e() escapa el contenido para impedir inyección de HTML/JS. -->
            <h1>¡Bienvenido, <?= e($nombre . ' ' . $apellido) ?>!</h1>
            <p class="subtitulo">Iniciaste sesión correctamente en el sistema.</p>

            <!-- Cierre de sesión por POST con token CSRF. -->
            <form action="api/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <button type="submit" class="boton secundario">Cerrar sesión</button>
            </form>
        </section>
    </main>
</body>
</html>
