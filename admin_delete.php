<?php
/** admin_delete.php — El administrador elimina un usuario (con sus imágenes) o una imagen. */
require __DIR__ . '/config.php';
$me = require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}
csrf_check();

// Eliminar una imagen concreta (moderación)
if (!empty($_POST['image_id'])) {
    $st = db()->prepare('SELECT stored_name FROM images WHERE id = ?');
    $st->execute([(int)$_POST['image_id']]);
    if ($img = $st->fetch()) {
        db()->prepare('DELETE FROM images WHERE id = ?')->execute([(int)$_POST['image_id']]);
        delete_image_files($img['stored_name']);
        flash('Imagen eliminada.');
    }
    redirect('admin_edit.php?id=' . (int)($_POST['back'] ?? 0));
}

// Eliminar un usuario completo
$id = (int)($_POST['id'] ?? 0);
if ($id === (int)$me['id']) {
    flash('No puedes eliminar tu propia cuenta de administrador.', 'error');
    redirect('admin.php');
}
$st = db()->prepare('SELECT username FROM users WHERE id = ?');
$st->execute([$id]);
$u = $st->fetch();
if ($u) {
    delete_user_files($id);                                       // archivos en disco
    db()->prepare('DELETE FROM images WHERE user_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    flash("Usuario “{$u['username']}” eliminado junto con sus imágenes.");
}
redirect('admin.php');
