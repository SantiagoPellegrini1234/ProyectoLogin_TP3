<?php
/**
 * ARCHIVO: login_app/registro.php
 * ---------------------------------------------------------------------
 * Formulario de alta de nuevos usuarios:
 * nombre, apellido, correo, contraseña y confirmación de contraseña.
 */

declare(strict_types=1);

require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/sesion.php';

iniciarSesionSegura();

// Un usuario ya logueado no necesita registrarse de nuevo.
if (usuarioLogueado()) {
    header('Location: bienvenida.php');
    exit;
}

// Token CSRF para el formulario.
$csrf = obtenerTokenCsrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear usuario</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <main class="contenedor">
        <section class="tarjeta">
            <h1>Crear nuevo usuario</h1>
            <p class="subtitulo">Completá los datos para registrarte</p>

            <!-- Zona de mensajes (errores de validación y respuesta del servidor). -->
            <div id="mensaje" class="mensaje" role="alert" aria-live="polite"></div>

            <form id="form-registro" data-tipo="registro" action="api/registro.php" method="post">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?= e($csrf) ?>">

                <!-- En pantallas anchas, nombre y apellido van lado a lado (grid). -->
                <div class="fila">
                    <div class="campo">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" required
                               autocomplete="given-name" maxlength="80" placeholder="Tu nombre">
                    </div>
                    <div class="campo">
                        <label for="apellido">Apellido</label>
                        <input type="text" id="apellido" name="apellido" required
                               autocomplete="family-name" maxlength="80" placeholder="Tu apellido">
                    </div>
                </div>

                <div class="campo">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" required
                           autocomplete="email" maxlength="150" placeholder="tucorreo@ejemplo.com">
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <!-- minlength se alinea con PASSWORD_MIN del servidor. -->
                    <input type="password" id="password" name="password" required
                           minlength="<?= PASSWORD_MIN ?>" autocomplete="new-password"
                           placeholder="Mínimo <?= PASSWORD_MIN ?> caracteres">
                </div>

                <div class="campo">
                    <label for="confirmar">Confirmar contraseña</label>
                    <input type="password" id="confirmar" name="confirmar" required
                           minlength="<?= PASSWORD_MIN ?>" autocomplete="new-password"
                           placeholder="Repetí la contraseña">
                </div>

                <button type="submit" class="boton">Crear usuario</button>
            </form>

            <!-- Vuelta al login. -->
            <p class="enlace-pie">¿Ya tenés cuenta? <a href="index.php">Volver al inicio de sesión</a></p>
        </section>
    </main>

    <script src="assets/js/validaciones.js" defer></script>
</body>
</html>
