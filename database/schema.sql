-- =====================================================================
-- ARCHIVO: login_app/database/schema.sql
-- Motor objetivo: MySQL Server 8.0+ (también funciona en MariaDB de XAMPP)
-- =====================================================================

-- Crea la base solo si no existe. utf8mb4 soporta tildes, ñ y emojis.
-- Se usa utf8mb4_unicode_ci porque es válido tanto en MySQL 8 como en MariaDB
-- (utf8mb4_0900_ai_ci es exclusivo de MySQL 8 y fallaría en MariaDB).
CREATE DATABASE IF NOT EXISTS login_app
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Selecciona la base para que las siguientes sentencias se apliquen a ella.
USE login_app;

-- Tabla de usuarios. InnoDB: transacciones e integridad referencial.
CREATE TABLE IF NOT EXISTS usuarios (
    -- Clave primaria autoincremental; UNSIGNED duplica el rango positivo.
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    -- Datos personales obligatorios (NOT NULL).
    nombre        VARCHAR(80)  NOT NULL,
    apellido      VARCHAR(80)  NOT NULL,
    -- El correo es el "usuario" del login; UNIQUE impide duplicados a nivel BD.
    correo        VARCHAR(150) NOT NULL,
    -- Hash bcrypt/argon2 (nunca la contraseña en texto plano).
    -- 255 caracteres deja margen si PHP cambia el algoritmo por defecto.
    password_hash VARCHAR(255) NOT NULL,
    -- Fecha de alta, la completa MySQL automáticamente.
    creado_en     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
