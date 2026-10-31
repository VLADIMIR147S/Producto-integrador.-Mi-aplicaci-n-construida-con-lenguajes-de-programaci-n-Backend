<?php
/**
 * delete.php — Elimina una o varias imágenes SOLO si pertenecen al usuario en sesión.
 * Acepta id (una imagen) o ids[] (selección múltiple).
 */
require __DIR__ . '/config.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('gallery.php');
}
csrf_check();

$ids = array_map('intval', (array)($_POST['ids'] ?? []));
if (!empty($_POST['id'])) {
    $ids[] = (int)$_POST['id'];
}
$ids = array_values(array_unique(array_filter($ids)));

$deleted = [];
if ($ids) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $st = db()->prepare("SELECT id, stored_name, title FROM images WHERE user_id = ? AND id IN ($in)");
    $st->execute([$me['id'], ...$ids]);
    foreach ($st->fetchAll() as $img) {
        db()->prepare('DELETE FROM images WHERE id = ? AND user_id = ?')->execute([$img['id'], $me['id']]);
        delete_image_files($img['stored_name']);
        $deleted[] = $img['title'];
    }
}

if (!$deleted) {
    flash('No se encontró la imagen o no te pertenece.', 'error');
} elseif (count($deleted) === 1) {
    flash('Se eliminó “' . $deleted[0] . '”.');
} else {
    flash('Se eliminaron ' . count($deleted) . ' imágenes.');
}
redirect('gallery.php');
