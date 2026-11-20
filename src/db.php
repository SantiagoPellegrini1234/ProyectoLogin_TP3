<?php
/**
 * ARCHIVO: login_app/src/db.php
 * ---------------------------------------------------------------------
 * Fábrica de conexión PDO hacia MySQL.
 * Se centraliza para que toda la app reutilice la MISMA conexión y las
 * MISMAS opciones de seguridad.
 */

declare(strict_types=1);

// Carga las constantes de configuración (host, puerto, usuario, etc.).
require_once __DIR__ . '/config.php';

/**
 * Devuelve una conexión PDO lista para usar.
 *
 * @return PDO Objeto de conexión configurado de forma segura.
 */
function obtenerConexion(): PDO
{
    // "static" conserva la variable entre llamadas dentro de la misma
    // petición: se abre UNA sola conexión aunque se invoque varias veces.
    // (PHP no permite tipar variables "static", por eso va sin tipo.)
    static $pdo = null;

    // Si ya existe una conexión abierta, se reutiliza.
    if ($pdo !== null) {
        return $pdo;
    }

    // DSN (Data Source Name) con el driver "mysql" de PDO.
    // charset=utf8mb4 asegura que el canal cliente-servidor use UTF-8 completo.
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    // Opciones de PDO:
    $opciones = [
        // Lanza excepciones ante errores SQL en lugar de fallar en silencio.
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Los resultados llegan como arreglos asociativos (['correo' => ...]).
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Desactiva la emulación de consultas preparadas: el motor MySQL
        // prepara las consultas de verdad, lo que blinda contra inyección SQL.
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Crea la conexión. Si falla, PDO lanza PDOException, que capturan
    // los endpoints de /api para responder un error genérico sin filtrar
    // credenciales ni detalles internos al navegador.
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);

    return $pdo;
}
