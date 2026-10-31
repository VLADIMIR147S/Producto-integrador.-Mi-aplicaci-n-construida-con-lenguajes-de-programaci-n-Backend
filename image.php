<?php
/**
 * image.php — Entrega el archivo de imagen tras verificar que pertenece
 * al usuario en sesión (o que quien lo pide es administrador).
 * Así, aunque alguien adivine un id, no puede ver galerías ajenas.
 */
require __DIR__ . '/config.php';
$me = current_user();
if (!$me) {
    http_response_code(401);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($me['role'] === 'admin') {
    $st = db()->prepare('SELECT * FROM images WHERE id = ?');
    $st->execute([$id]);
} else {
    $st = db()->prepare('SELECT * FROM images WHERE id = ? AND user_id = ?');
    $st->execute([$id, $me['id']]);
}
$img  = $st->fetch();
$path = $img ? UPLOAD_DIR . '/' . basename($img['stored_name']) : '';

if (!$img || !is_file($path)) {
    http_response_code(404);
    exit;
}

// Miniatura si se pide y existe; si no, el original
if (!empty($_GET['thumb']) && is_file(thumb_path($img['stored_name']))) {
    $path = thumb_path($img['stored_name']);
    $img['mime'] = 'image/jpeg';
}

session_write_close();   // libera la sesión para cargar varias imágenes en paralelo
header('Content-Type: ' . $img['mime']);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=604800, immutable');
if (!empty($_GET['download'])) {
    $safe = preg_replace('/[^\w.\- ]+/u', '_', $img['original_name']);
    header('Content-Disposition: attachment; filename="' . $safe . '"');
}
readfile($path);
