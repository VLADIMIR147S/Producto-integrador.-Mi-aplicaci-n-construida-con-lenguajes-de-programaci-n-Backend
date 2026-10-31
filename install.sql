-- install.sql — Esquema para MySQL / MariaDB (solo si DB_DRIVER = 'mysql').
-- La cuenta admin se crea automáticamente al abrir la aplicación por primera vez.

CREATE DATABASE IF NOT EXISTS galeria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE galeria;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(30)  NOT NULL UNIQUE,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS images (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    title         VARCHAR(100) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name   VARCHAR(64)  NOT NULL UNIQUE,
    mime          VARCHAR(30)  NOT NULL,
    size          INT UNSIGNED NOT NULL,
    width         INT UNSIGNED NULL,
    height        INT UNSIGNED NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_images_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
