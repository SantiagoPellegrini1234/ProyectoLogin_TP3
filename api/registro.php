<?php
/**
 * ARCHIVO: login_app/api/registro.php
 * ---------------------------------------------------------------------
 * Endpoint de alta de usuarios. Recibe JSON por POST desde fetch() y
 * responde JSON. Aquí se repite la validación del navegador porque el
 * JavaScript del cliente NUNCA es una barrera de seguridad confiable.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/sesion.php';

iniciarSesionSegura();

// Solo se acepta el método POST; cualquier otro recibe 405.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

// Lee los datos enviados por el cliente.
$datos = leerCuerpoJson();

// Verifica el token CSRF (campo del cuerpo o cabecera personalizada).
$token = $datos['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
if (!validarTokenCsrf(is_string($token) ? $token : null)) {
    responderJson(403, ['ok' => false, 'mensaje' => 'Token de seguridad inválido. Recargá la página.']);
}

// Normaliza las entradas: trim() quita espacios sobrantes en los extremos.
// Las contraseñas NO se recortan: los espacios pueden ser parte de la clave.
$nombre    = trim((string)($datos['nombre'] ?? ''));
$apellido  = trim((string)($datos['apellido'] ?? ''));
$correo    = mb_strtolower(trim((string)($datos['correo'] ?? '')));
$password  = (string)($datos['password'] ?? '');
$confirmar = (string)($datos['confirmar'] ?? '');

// ----- Validaciones del lado servidor -----

// Todos los campos son obligatorios.
if ($nombre === '' || $apellido === '' || $correo === '' || $password === '' || $confirmar === '') {
    responderJson(400, ['ok' => false, 'mensaje' => 'Completá todos los campos.']);
}

// Límite de longitud: coincide con las columnas VARCHAR de la tabla.
if (mb_strlen($nombre) > 80 || mb_strlen($apellido) > 80 || mb_strlen($correo) > 150) {
    responderJson(400, ['ok' => false, 'mensaje' => 'Alguno de los campos supera el largo permitido.']);
}

// Formato de correo válido según el filtro nativo de PHP.
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responderJson(400, ['ok' => false, 'mensaje' => 'El correo electrónico no es válido.']);
}

// Largo mínimo de la contraseña.
if (mb_strlen($password) < PASSWORD_MIN) {
    responderJson(400, ['ok' => false, 'mensaje' => 'La contraseña debe tener al menos ' . PASSWORD_MIN . ' caracteres.']);
}

// Las dos contraseñas deben ser idénticas.
if ($password !== $confirmar) {
    responderJson(400, ['ok' => false, 'mensaje' => 'Las contraseñas no coinciden.']);
}

try {
    $pdo = obtenerConexion();

    // Consulta preparada: el correo viaja como parámetro (:correo), nunca
    // concatenado al SQL, por lo que no existe inyección posible.
    $existe = $pdo->prepare('SELECT id FROM usuarios WHERE correo = :correo LIMIT 1');
    $existe->execute([':correo' => $correo]);

    if ($existe->fetch()) {
        // 409 Conflict: el recurso ya existe.
        responderJson(409, ['ok' => false, 'mensaje' => 'Ya existe un usuario con ese correo.']);
    }

    // Hash bcrypt con salt aleatorio incluido; jamás se guarda el texto plano.
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

    // Inserción preparada del nuevo usuario.
    $insertar = $pdo->prepare(
        'INSERT INTO usuarios (nombre, apellido, correo, password_hash)
         VALUES (:nombre, :apellido, :correo, :hash)'
    );
    $insertar->execute([
        ':nombre'   => $nombre,
        ':apellido' => $apellido,
        ':correo'   => $correo,
        ':hash'     => $hash,
    ]);

    // 201 Created: alta exitosa.
    responderJson(201, ['ok' => true, 'mensaje' => 'Usuario creado correctamente.']);

} catch (PDOException $ex) {
    // 23000 = violación de integridad (carrera: otro registro idéntico entró
    // entre el SELECT y el INSERT; la clave UNIQUE de la BD lo detiene).
    if ($ex->getCode() === '23000') {
        responderJson(409, ['ok' => false, 'mensaje' => 'Ya existe un usuario con ese correo.']);
    }
    // Se registra el detalle técnico en el log del servidor (no en pantalla).
    error_log('[registro] ' . $ex->getMessage());
    responderJson(500, ['ok' => false, 'mensaje' => 'Error interno del servidor. Intentá más tarde.']);
}
