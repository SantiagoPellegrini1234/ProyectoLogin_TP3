<?php
/**
 * ARCHIVO: login_app/api/login.php
 * ---------------------------------------------------------------------
 * Endpoint de autenticación. Recibe correo y contraseña por POST (JSON),
 * verifica el hash con password_verify() y abre la sesión.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/sesion.php';

iniciarSesionSegura();

// Solo POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

$datos = leerCuerpoJson();

// Protección CSRF.
$token = $datos['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
if (!validarTokenCsrf(is_string($token) ? $token : null)) {
    responderJson(403, ['ok' => false, 'mensaje' => 'Token de seguridad inválido. Recargá la página.']);
}

// El "usuario" del sistema es el correo electrónico (único en la tabla).
$correo   = mb_strtolower(trim((string)($datos['correo'] ?? '')));
$password = (string)($datos['password'] ?? '');

if ($correo === '' || $password === '') {
    responderJson(400, ['ok' => false, 'mensaje' => 'Ingresá usuario y contraseña.']);
}

try {
    $pdo = obtenerConexion();

    // Busca al usuario con consulta preparada.
    $consulta = $pdo->prepare(
        'SELECT id, nombre, apellido, password_hash FROM usuarios WHERE correo = :correo LIMIT 1'
    );
    $consulta->execute([':correo' => $correo]);
    $usuario = $consulta->fetch();

    // Hash "señuelo" REAL de bcrypt (cost 12): si el correo no existe igual se
    // ejecuta password_verify con un costo equivalente, de modo que el tiempo
    // de respuesta no revele si el usuario existe. No corresponde a ninguna clave útil.
    $hashAComparar = $usuario['password_hash']
        ?? '$2y$12$9VsxBomTgtIIJ0R6V2YIfedlggMy3JIpHqAvUDP5hHlk6nFGXyerK';

    $passwordOk = password_verify($password, $hashAComparar);

    // Mensaje único y genérico: no se distingue "usuario inexistente"
    // de "contraseña incorrecta" (evita enumeración de usuarios).
    if (!$usuario || !$passwordOk) {
        responderJson(401, ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.']);
    }

    // Si el algoritmo o el costo cambiaron desde que se guardó el hash,
    // se recalcula y se actualiza de forma transparente.
    if (password_needs_rehash($usuario['password_hash'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])) {
        $nuevoHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $actualiza = $pdo->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
        $actualiza->execute([':hash' => $nuevoHash, ':id' => $usuario['id']]);
    }

    // Regenera el ID de sesión y destruye el anterior (anti "session fixation").
    session_regenerate_id(true);

    // Guarda en sesión solo lo necesario (nunca el hash de la contraseña).
    $_SESSION['usuario_id']       = (int)$usuario['id'];
    $_SESSION['usuario_nombre']   = $usuario['nombre'];
    $_SESSION['usuario_apellido'] = $usuario['apellido'];

    responderJson(200, ['ok' => true, 'mensaje' => 'Ingreso correcto.', 'redirigir' => 'bienvenida.php']);

} catch (PDOException $ex) {
    error_log('[login] ' . $ex->getMessage());
    responderJson(500, ['ok' => false, 'mensaje' => 'Error interno del servidor. Intentá más tarde.']);
}
