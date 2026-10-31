<?php
/** logout.php — Cierra la sesión (solo por POST con CSRF). */
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION = [];
    session_destroy();
    session_start();
    flash('Sesión cerrada correctamente.', 'info');
}
redirect('login.php');
