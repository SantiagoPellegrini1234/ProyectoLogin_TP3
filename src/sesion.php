<?php
/**
 * ARCHIVO: login_app/src/sesion.php
 * ---------------------------------------------------------------------
 * Utilidades de sesión segura, protección CSRF y respuestas JSON.
 * Todas las páginas y endpoints incluyen este archivo antes de usar $_SESSION.
 */

declare(strict_types=1);

/**
 * Inicia la sesión con cookies endurecidas.
 * Debe llamarse ANTES de enviar cualquier salida al navegador.
 */
function iniciarSesionSegura(): void
{
    // Si ya hay una sesión activa no se vuelve a iniciar (evita avisos).
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Detecta si la petición llegó por HTTPS para marcar la cookie "Secure".
    $esHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    // Parámetros de la cookie de sesión:
    session_set_cookie_params([
        'lifetime' => 0,          // Expira al cerrar el navegador.
        'path'     => '/',        // Disponible en todo el sitio.
        'secure'   => $esHttps,   // Solo por HTTPS cuando está disponible.
        'httponly' => true,       // JavaScript NO puede leer la cookie (mitiga XSS).
        'samesite' => 'Lax',      // Mitiga CSRF: no se envía en peticiones cruzadas.
    ]);

    // Obliga a usar únicamente cookies (nunca el ID en la URL).
    ini_set('session.use_only_cookies', '1');
    // Rechaza IDs de sesión que el servidor no generó (anti "session fixation").
    ini_set('session.use_strict_mode', '1');

    session_start();
}

/**
 * Devuelve el token CSRF de la sesión; lo crea si todavía no existe.
 * El token viaja en un campo oculto del formulario y debe coincidir
 * con el guardado en el servidor para aceptar el POST.
 */
function obtenerTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // 32 bytes aleatorios criptográficamente seguros, en hexadecimal.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Comprueba el token recibido contra el de la sesión.
 * hash_equals() compara en tiempo constante (evita ataques de temporización).
 */
function validarTokenCsrf(?string $tokenRecibido): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($tokenRecibido)
        && hash_equals($_SESSION['csrf_token'], $tokenRecibido);
}

/**
 * Indica si hay un usuario autenticado en la sesión actual.
 */
function usuarioLogueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Atajo para escapar texto antes de imprimirlo en HTML (defensa contra XSS).
 */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Envía una respuesta JSON con su código HTTP y termina la ejecución.
 *
 * @param int   $codigo  Código HTTP (200, 400, 401, 409, 500...).
 * @param array $cuerpo  Datos que se serializan como JSON.
 */
function responderJson(int $codigo, array $cuerpo): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    // Evita que el navegador o proxies guarden en caché respuestas de autenticación.
    header('Cache-Control: no-store');
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Lee y decodifica el cuerpo JSON enviado por fetch().
 * Devuelve un arreglo vacío si el cuerpo no es JSON válido.
 */
function leerCuerpoJson(): array
{
    $crudo = file_get_contents('php://input');
    $datos = json_decode($crudo ?: '', true);
    return is_array($datos) ? $datos : [];
}
