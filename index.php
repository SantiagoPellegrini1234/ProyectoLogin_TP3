<?php
/**
 * ARCHIVO: login_app/index.php
 * ---------------------------------------------------------------------
 * Página de inicio: formulario de Login.
 * Si el usuario ya está autenticado se lo envía directo a la bienvenida.
 */

declare(strict_types=1);

require_once __DIR__ . '/src/sesion.php';

iniciarSesionSegura();

// Evita mostrar el login a quien ya inició sesión.
if (usuarioLogueado()) {
    header('Location: bienvenida.php');
    exit;
}

// Token CSRF que se incrusta en el formulario.
$csrf = obtenerTokenCsrf();

// Mensajes informativos que llegan por la URL desde otras pantallas.
$mensajeInicial = '';
if (($_GET['registro'] ?? '') === 'ok') {
    $mensajeInicial = 'Usuario creado con éxito. Ya podés iniciar sesión.';
} elseif (($_GET['salida'] ?? '') === 'ok') {
    $mensajeInicial = 'Cerraste la sesión correctamente.';
}
?>
<!DOCTYPE html>
<!-- Idioma del documento: ayuda a lectores de pantalla y buscadores. -->
<html lang="es">
<head>
    <!-- Codificación de caracteres: imprescindible para tildes y ñ. -->
    <meta charset="UTF-8">
    <!-- Hace que el diseño sea responsive en móviles. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <!-- Hoja de estilos propia (sin frameworks). -->
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
    <!-- Contenedor que centra la tarjeta con flexbox. -->
    <main class="contenedor">
        <section class="tarjeta">
            <h1>Iniciar sesión</h1>
            <p class="subtitulo">Ingresá tus credenciales para continuar</p>

            <!-- Zona de mensajes: JS la completa con errores o avisos.
                 role="alert" y aria-live hacen que se anuncie a lectores de pantalla. -->
            <div id="mensaje" class="mensaje <?= $mensajeInicial !== '' ? 'exito visible' : '' ?>"
                 role="alert" aria-live="polite"><?= e($mensajeInicial) ?></div>

            <!-- Formulario de login. data-tipo le indica a validaciones.js cómo tratarlo.
                 "required" provee la verificación nativa de campos vacíos desde HTML. -->
            <form id="form-login" data-tipo="login" action="api/login.php" method="post">
                <!-- Token CSRF oculto: el servidor lo exige en el POST. -->
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?= e($csrf) ?>">

                <div class="campo">
                    <label for="correo">Usuario (correo electrónico)</label>
                    <input type="email" id="correo" name="correo" required
                           autocomplete="username" placeholder="tucorreo@ejemplo.com" maxlength="150">
                </div>

                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required
                           autocomplete="current-password" placeholder="Tu contraseña">
                </div>

                <button type="submit" class="boton">Ingresar</button>
            </form>

            <!-- Enlace hacia el formulario de alta de usuarios. -->
            <p class="enlace-pie">¿No tenés cuenta? <a href="registro.php">Crear nuevo usuario</a></p>
        </section>
    </main>

    <!-- Script de validación y envío; "defer" lo ejecuta tras cargar el HTML. -->
    <script src="assets/js/validaciones.js" defer></script>
</body>
</html>
