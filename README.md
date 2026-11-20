Nombre del alumno: Santiago Pellegrini
Año: 2026
Materia: Analisis de Sistemas

# Login App — PHP 8 + MySQL (nativo)

Sistema de autenticación con **registro, login y pantalla de bienvenida**.
Backend PHP 8 con **PDO + consultas preparadas**, base de datos **MySQL 8**, y front end en
**HTML5 / CSS3 / JavaScript vanilla** (sin librerías ni frameworks).

---

## 1. Arquitectura y diseño

```
Navegador                          Servidor Apache (XAMPP)                  MySQL
┌──────────────┐  fetch() JSON   ┌──────────────────────────┐   PDO       ┌───────────┐
│ index.php    │ ──────────────► │ api/login.php            │ ──────────► │ login_app │
│ registro.php │ ◄────────────── │ api/registro.php         │ ◄────────── │ usuarios  │
│ validaciones │   respuesta     │ src/ (db, sesion, config)│  prepared   └───────────┘
│ .js / .css   │                 └──────────────────────────┘  statements
└──────────────┘
```

**Flujo de login:** formulario (`required`) → `validaciones.js` revisa vacíos → `fetch` a
`api/login.php` → el servidor valida CSRF, busca el correo (consulta preparada), compara con
`password_verify()`, regenera el ID de sesión y responde JSON → el JS redirige a `bienvenida.php`.

**Flujo de alta:** formulario → JS revisa vacíos, largo mínimo y que las dos contraseñas coincidan →
`fetch` a `api/registro.php` → el servidor repite las validaciones, comprueba que el correo no exista,
guarda el hash con `password_hash()` (bcrypt) → vuelve al login con aviso de éxito.

### Medidas de seguridad implementadas
| Medida | Dónde |
|---|---|
| Consultas preparadas (anti inyección SQL), `EMULATE_PREPARES = false` | `src/db.php`, `api/*.php` |
| `password_hash(PASSWORD_BCRYPT)` + `password_verify()` + `password_needs_rehash()` | `api/registro.php`, `api/login.php` |
| `session_regenerate_id(true)` tras el login (anti session fixation) | `api/login.php` |
| Cookie de sesión `HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS | `src/sesion.php` |
| Token CSRF con `hash_equals()` | `src/sesion.php`, formularios |
| Mensaje genérico de error y hash señuelo (anti enumeración de usuarios) | `api/login.php` |
| Escape de salida con `htmlspecialchars()` (anti XSS) | `src/sesion.php` → `e()` |
| Validación duplicada: navegador (UX) **y** servidor (seguridad real) | `validaciones.js`, `api/*.php` |

---

## 2. Estructura de archivos

Ruta de destino en XAMPP: `C:/xampp/htdocs/login_app/`

```
login_app/
├── index.php                 ← Login (página de inicio)
├── registro.php              ← Formulario de alta de usuario
├── bienvenida.php            ← Pantalla privada (requiere sesión)
├── api/
│   ├── login.php             ← Endpoint de autenticación (JSON)
│   ├── registro.php          ← Endpoint de alta (JSON)
│   └── logout.php            ← Cierre de sesión (POST + CSRF)
├── src/
│   ├── config.php            ← Credenciales de BD y constantes
│   ├── db.php                ← Conexión PDO
│   └── sesion.php            ← Sesión segura, CSRF y helpers
├── assets/
│   ├── css/estilos.css       ← Estilos responsive (flexbox + grid)
│   └── js/validaciones.js    ← Validación de campos y fetch
├── database/
│   └── schema.sql            ← Script de la base de datos (MySQL 8)
└── README.md
```

---

## 3. Guía de implementación paso a paso

### 3.1 Copiar el proyecto
1. Descomprimí el ZIP.
2. Copiá la carpeta `login_app` dentro de `C:/xampp/htdocs/`.
3. Abrí la carpeta en **Visual Studio Code** (`Archivo → Abrir carpeta…`).

### 3.2 Crear la base de datos

**Opción A — phpMyAdmin (viene con XAMPP)**
1. En el Panel de XAMPP iniciá **Apache** y **MySQL** → botón *Admin* de MySQL abre phpMyAdmin
   (`http://localhost/phpmyadmin`).
2. Pestaña **SQL** → pegá el contenido de `database/schema.sql` → **Continuar**.
   (O pestaña **Importar** → elegí `schema.sql`).
3. Verificá que aparezca la base `login_app` con la tabla `usuarios`.

**Opción B — MySQL Workbench**
1. Conectate a tu instancia (`127.0.0.1:3306`).
2. `File → Open SQL Script…` → elegí `database/schema.sql`.
3. Ejecutá todo con el rayo ⚡ (`Ctrl + Shift + Enter`).

**Opción C — consola**
```bash
mysql -u root -p < C:/xampp/htdocs/login_app/database/schema.sql
```

### 3.3 MySQL oficial vs. MariaDB de XAMPP
XAMPP incluye **MariaDB**, no MySQL. El script y el código son compatibles con ambos
(se usó `utf8mb4_unicode_ci`, válido en los dos motores). Si usás **MySQL Server 8.0+ oficial**:

1. **Conflicto de puerto:** si MariaDB de XAMPP y MySQL Server corren a la vez, ambos quieren el `3306`.
   - Opción 1: detené el servicio MySQL de XAMPP y dejá solo MySQL Server.
   - Opción 2: cambiá el puerto de uno (en XAMPP: `Config → my.ini → port=3307`) y ajustá
     `DB_PORT` en `src/config.php`.
2. **Credenciales:** MySQL Server pide la contraseña de `root` definida en la instalación →
   completá `DB_PASS` en `src/config.php`. (En XAMPP/MariaDB `root` no tiene contraseña.)
3. **Usuario dedicado (recomendado):**
   ```sql
   CREATE USER 'login_user'@'localhost' IDENTIFIED BY 'UnaClaveSegura_2026!';
   GRANT SELECT, INSERT, UPDATE ON login_app.* TO 'login_user'@'localhost';
   FLUSH PRIVILEGES;
   ```
   y poné `DB_USER = 'login_user'` / `DB_PASS = 'UnaClaveSegura_2026!'`.
4. **Autenticación:** MySQL 8 usa `caching_sha2_password`; PHP 8 con `mysqlnd` lo soporta sin cambios.
5. **Extensión PHP:** verificá que `extension=pdo_mysql` esté activa en `C:/xampp/php/php.ini`
   (viene activa por defecto).

### 3.4 Ejecutar y probar
1. Con **Apache** y **MySQL** en verde en el Panel de XAMPP, abrí: **http://localhost/login_app/**
2. Pulsá **Crear nuevo usuario**, completá los datos y creá la cuenta.
3. Volvé al login, ingresá con el correo y la contraseña → verás **«¡Bienvenido, Nombre Apellido!»**.
4. Pruebas sugeridas:
   - Enviar vacío → bloquea el atributo `required` (o el mensaje de JS si solo hay espacios).
   - Contraseñas distintas en el alta → mensaje «Las contraseñas no coinciden».
   - Correo repetido → «Ya existe un usuario con ese correo».
   - Clave incorrecta → «Usuario o contraseña incorrectos».
   - Entrar a `bienvenida.php` sin sesión → redirige al login.
   - En phpMyAdmin, comprobá que `password_hash` empieza con `$2y$12$` (nunca texto plano).

### 3.5 Depuración de errores comunes
| Síntoma | Causa probable | Solución |
|---|---|---|
| `SQLSTATE[HY000] [2002]` conexión rechazada | MySQL apagado o puerto distinto | Iniciar MySQL en XAMPP; revisar `DB_PORT` |
| `SQLSTATE[HY000] [1045]` acceso denegado | Usuario/clave incorrectos | Corregir `DB_USER` / `DB_PASS` en `config.php` |
| `SQLSTATE[HY000] [1049]` base desconocida | No se ejecutó `schema.sql` | Importar el script (paso 3.2) |
| `could not find driver` | `pdo_mysql` desactivado | Activarlo en `php.ini` y reiniciar Apache |
| Mensaje «Error interno del servidor» | Excepción de PDO | Ver `C:/xampp/apache/logs/error.log` (se registra ahí) |
| «Token de seguridad inválido» | Página abierta mucho tiempo o cookies bloqueadas | Recargar la página con `Ctrl + F5` |
| Pantalla en blanco | Error de sintaxis PHP | Revisar `error.log`; en desarrollo activar `display_errors=On` en `php.ini` |
| «No se pudo conectar con el servidor» | Apache apagado o URL incorrecta | Iniciar Apache; usar `http://localhost/login_app/` |
| `Port 80 in use` en Apache | Skype/IIS/otro servicio usa el puerto | Cambiar el puerto en `httpd.conf` (`Listen 8080`) y abrir `http://localhost:8080/login_app/` |

> **Nota de producción:** usar HTTPS (la cookie pasa a `Secure`), mover `src/` fuera del directorio público,
> usar un usuario de BD con permisos mínimos y desactivar `display_errors`.
