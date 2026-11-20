<?php
/**
 * ARCHIVO: login_app/src/config.php
 * ---------------------------------------------------------------------
 * Configuración central de la aplicación.
 * Se aísla en un único archivo para que cambiar credenciales o puertos
 * NO obligue a tocar la lógica del resto del sistema (principio de
 * responsabilidad única / separación de configuración y código).
 *
 * En producción este archivo debería quedar FUERA de la carpeta pública
 * del servidor web o leer variables de entorno.
 */

// Activa el modo estricto de tipos: PHP no convertirá tipos en silencio
// (por ejemplo "5" a 5), lo que evita errores lógicos difíciles de depurar.
declare(strict_types=1);

// Dirección del servidor MySQL. Se usa 127.0.0.1 en lugar de "localhost"
// porque "localhost" puede intentar un socket UNIX y fallar en Windows/XAMPP
// cuando MySQL escucha solo por TCP.
const DB_HOST = '127.0.0.1';

// Puerto TCP del motor. El valor por defecto de MySQL y MariaDB es 3306.
// Si instalaste MySQL Server oficial junto a XAMPP y el puerto 3306 estaba
// ocupado por MariaDB, cambia este valor (por ejemplo a 3307).
const DB_PORT = '3306';

// Nombre de la base creada por database/schema.sql.
const DB_NAME = 'login_app';

// Usuario de la base. 'root' es solo para desarrollo local;
// en producción se crea un usuario con permisos mínimos (SELECT, INSERT).
const DB_USER = 'root';

// Contraseña del usuario. En XAMPP (MariaDB) root viene SIN contraseña;
// en MySQL Server oficial la defines durante la instalación.
const DB_PASS = '';

// Costo del algoritmo bcrypt: cada +1 duplica el tiempo de cálculo.
// 12 es un buen equilibrio entre seguridad y velocidad en 2026.
const BCRYPT_COST = 12;

// Longitud mínima aceptada para las contraseñas nuevas.
const PASSWORD_MIN = 8;
