<?php
/**
 * db.php — Conexión PDO y creación automática del esquema.
 */

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'mysql') {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
    } else {
        if (!is_dir(dirname(DB_SQLITE_PATH))) {
            mkdir(dirname(DB_SQLITE_PATH), 0775, true);
        }
        $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user','admin')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            title TEXT NOT NULL,
            original_name TEXT NOT NULL,
            stored_name TEXT NOT NULL UNIQUE,
            mime TEXT NOT NULL,
            size INTEGER NOT NULL,
            width INTEGER, height INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // Crea la cuenta de administrador si no existe ninguna
    $hasAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if (!$hasAdmin) {
        $st = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $st->execute([ADMIN_DEFAULT_USER, ADMIN_DEFAULT_EMAIL, password_hash(ADMIN_DEFAULT_PASS, PASSWORD_DEFAULT), 'admin']);
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    return $pdo;
}
