<?php
/**
 * upload.php — Recibe una o varias imágenes, las valida en el servidor
 * y las guarda con un nombre aleatorio fuera del alcance público.
 * Responde JSON si la petición viene de JavaScript (fetch); si no, redirige.
 */
require __DIR__ . '/config.php';
$me = require_login();

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('gallery.php');
}
csrf_check();

$ok = 0;
$errors = [];

// Si el archivo supera post_max_size, PHP vacía $_FILES
if (empty($_FILES['files'])) {
    $errors[] = 'No se recibió ningún archivo (puede que exceda el tamaño permitido por el servidor).';
} else {
    $f = $_FILES['files'];
    $count = is_array($f['name']) ? count($f['name']) : 0;
    $titles = $_POST['titles'] ?? [];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    for ($i = 0; $i < $count; $i++) {
        $name = $f['name'][$i];
        $tmp  = $f['tmp_name'][$i];

        if ($f['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "$name: error al subir (código {$f['error'][$i]}).";
            continue;
        }
        if ($f['size'][$i] > MAX_FILE_SIZE) {
            $errors[] = "$name: supera " . human_size(MAX_FILE_SIZE) . '.';
            continue;
        }
        // Validación real del contenido (no confiar en la extensión ni en el MIME del navegador)
        $mime = $finfo->file($tmp);
        $info = @getimagesize($tmp);
        if (!isset(ALLOWED_MIME[$mime]) || $info === false) {
            $errors[] = "$name: formato no permitido.";
            continue;
        }

        $stored = bin2hex(random_bytes(16)) . '.' . ALLOWED_MIME[$mime];
        if (!move_uploaded_file($tmp, UPLOAD_DIR . '/' . $stored)) {
            $errors[] = "$name: no se pudo guardar.";
            continue;
        }
        // Miniatura para que la galería cargue rápido
        make_thumb(UPLOAD_DIR . '/' . $stored, $mime, thumb_path($stored));

        $title = trim((string)($titles[$i] ?? ''));
        if ($title === '') {
            $title = pathinfo($name, PATHINFO_FILENAME);
        }
        $title = mb_substr($title, 0, 100);

        $st = db()->prepare('INSERT INTO images (user_id, title, original_name, stored_name, mime, size, width, height)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $st->execute([$me['id'], $title, mb_substr($name, 0, 255), $stored, $mime, $f['size'][$i], $info[0], $info[1]]);
        $ok++;
    }
}

if ($ok) {
    flash($ok === 1 ? 'Imagen subida correctamente.' : "$ok imágenes subidas correctamente.");
}
foreach ($errors as $er) {
    flash($er, 'error');
}

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'errors' => $errors]);
    exit;
}
redirect('gallery.php');
