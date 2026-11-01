<?php
/**
 * config.php — Configuración general y funciones auxiliares (lado del servidor).
 * Galería de Imágenes · PHP + HTML + JavaScript
 */

declare(strict_types=1);

// ---------- Base de datos ----------
// Por defecto usa SQLite (no requiere configuración). Para MySQL/MariaDB
// cambie DB_DRIVER a 'mysql', complete los datos y ejecute install.sql.
define('DB_DRIVER', 'sqlite');                       // 'sqlite' | 'mysql'
define('DB_SQLITE_PATH', getenv('DB_SQLITE_PATH') ?: (is_dir('/var/data') ? '/var/data/galeria.sqlite' : __DIR__ . '/data/galeria.sqlite'));
define('DB_HOST', 'localhost');
define('DB_NAME', 'galeria');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---------- Archivos ----------
define('UPLOAD_DIR', getenv('UPLOAD_DIR') ?: (is_dir('/var/data') ? '/var/data/uploads' : __DIR__ . '/uploads'));
define('MAX_FILE_SIZE', 5 * 1024 * 1024);            // 5 MB
define('ALLOWED_MIME', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
]);

// Cuenta de administrador que se crea la primera vez (¡cambie la contraseña!)
define('ADMIN_DEFAULT_USER', 'admin');
define('ADMIN_DEFAULT_EMAIL', 'admin@galeria.local');
define('ADMIN_DEFAULT_PASS', 'Admin123!');

// ---------- Identidad de la aplicación ----------
define('APP_NAME', 'Guacamaya');
define('APP_TAGLINE', 'Tu galería privada de imágenes');
define('THUMB_WIDTH', 640);                           // ancho de las miniaturas (px)

/**
 * Imágenes de la portada. Guarde los archivos en assets/img/ con estos nombres;
 * mientras no existan, se cargan desde la URL de respaldo.
 */
define('BRAND_IMAGES', [
    'mascot' => ['assets/img/guacamaya.png', 'https://static.vecteezy.com/system/resources/thumbnails/052/645/972/small_2x/colorful-parrot-perched-on-a-branch-in-nature-isolated-transparent-png.png'],
    'photo1' => ['assets/img/foto-1.jpg', 'https://tse2.mm.bing.net/th/id/OIP.qBAvbCVwsTQm_RORwPVU5gHaHa?r=0&w=626&h=626&rs=1&pid=ImgDetMain&o=7&rm=3'],
    'photo2' => ['assets/img/foto-2.jpg', 'https://tse3.mm.bing.net/th/id/OIP.iwFhHHKPOqAJUDO-iSov_wHaE8?r=0&rs=1&pid=ImgDetMain&o=7&rm=3'],
]);

// ---------- Sesión segura ----------
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

require_once __DIR__ . '/db.php';

// ---------- Funciones auxiliares ----------

/** Escapa texto para imprimirlo en HTML (previene XSS). */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Token CSRF para formularios. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), (string)$sent)) {
        http_response_code(403);
        exit('Solicitud no válida (CSRF).');
    }
}

/** Usuario en sesión (array) o null. */
function current_user(): ?array
{
    if (empty($_SESSION['uid'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $st = db()->prepare('SELECT id, username, email, role, created_at FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $user = $st->fetch() ?: null;
        if ($user === null) {           // el usuario fue eliminado
            session_destroy();
        }
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        flash('Inicia sesión para continuar.', 'info');
        redirect('login.php');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        exit('Acceso restringido a administradores.');
    }
    return $u;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/** Mensajes flash (se muestran una vez). */
function flash(string $msg, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function human_size(int $bytes): string
{
    $u = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float)$bytes;
    while ($n >= 1024 && $i < 3) { $n /= 1024; $i++; }
    return round($n, 1) . ' ' . $u[$i];
}

/** Valida datos de usuario. Devuelve lista de errores. */
function validate_user(string $username, string $email, ?string $password, ?int $ignoreId = null): array
{
    $errors = [];
    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
        $errors[] = 'El usuario debe tener 3–30 caracteres (letras, números, punto o guion bajo).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo electrónico no es válido.';
    }
    if ($password !== null && (strlen($password) < 8 || !preg_match('/\d/', $password) || !preg_match('/[A-Za-z]/', $password))) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres e incluir letras y números.';
    }
    $st = db()->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?');
    $st->execute([$username, $email, $ignoreId ?? 0]);
    if ($st->fetch()) {
        $errors[] = 'El usuario o el correo ya están registrados.';
    }
    return $errors;
}

/** Borra del disco todas las imágenes de un usuario. */
function delete_user_files(int $userId): void
{
    $st = db()->prepare('SELECT stored_name FROM images WHERE user_id = ?');
    $st->execute([$userId]);
    foreach ($st->fetchAll() as $img) {
        delete_image_files($img['stored_name']);
    }
}

/** Borra el original y la miniatura de una imagen. */
function delete_image_files(string $stored): void
{
    foreach ([UPLOAD_DIR . '/' . basename($stored), thumb_path($stored)] as $p) {
        if (is_file($p)) { unlink($p); }
    }
}

function thumb_path(string $stored): string
{
    return UPLOAD_DIR . '/t_' . pathinfo(basename($stored), PATHINFO_FILENAME) . '.jpg';
}

/**
 * Genera una miniatura JPEG con GD (si está disponible) para que la galería
 * cargue rápido incluso con conexiones móviles. Los GIF se dejan sin miniatura
 * para conservar la animación.
 */
function make_thumb(string $src, string $mime, string $dest): bool
{
    if (!function_exists('imagecreatetruecolor') || $mime === 'image/gif') {
        return false;
    }
    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($src),
        'image/png'  => @imagecreatefrompng($src),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        default      => false,
    };
    if (!$img) {
        return false;
    }
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w <= THUMB_WIDTH) {
        imagedestroy($img);
        return false;                                   // ya es pequeña: se usa el original
    }
    $nh = (int)round($h * THUMB_WIDTH / $w);
    $t = imagecreatetruecolor(THUMB_WIDTH, $nh);
    imagefill($t, 0, 0, imagecolorallocate($t, 255, 255, 255));   // fondo blanco para PNG transparentes
    imagecopyresampled($t, $img, 0, 0, 0, 0, THUMB_WIDTH, $nh, $w, $h);
    $ok = imagejpeg($t, $dest, 82);
    imagedestroy($img);
    imagedestroy($t);
    return $ok;
}

/** Ícono SVG en línea (trazos estilo Lucide). */
function icon(string $name, string $class = 'icon'): string
{
    static $paths = [
        'upload'   => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>',
        'image'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
        'images'   => '<path d="M18 22H4a2 2 0 0 1-2-2V6"/><rect x="6" y="2" width="16" height="16" rx="2"/><circle cx="12" cy="8" r="2"/><path d="m22 13-1.3-1.3a2 2 0 0 0-2.8 0L11 18.5"/>',
        'users'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
        'user'     => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'trash'    => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'search'   => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'lock'     => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'phone'    => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
        'zap'      => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'x'        => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'expand'   => '<path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'menu'     => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
        'left'     => '<path d="m15 18-6-6 6-6"/>',
        'right'    => '<path d="m9 18 6-6-6-6"/>',
        'arrow'    => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'plus'     => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'select'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="m9 12 2 2 4-4"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'eye'      => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'crown'    => '<path d="m2 6 4 12h12l4-12-6 5-4-7-4 7z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'info'     => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'alert'    => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** Logotipo de la marca. */
function logo(): string
{
    return '<span class="logo-mark" aria-hidden="true"><svg viewBox="0 0 32 32"><defs><linearGradient id="lg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ef4444"/><stop offset=".45" stop-color="#f59e0b"/><stop offset=".75" stop-color="#10b981"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs><rect width="32" height="32" rx="9" fill="url(#lg)"/><path d="M8 22l5.5-7 4 5 2.5-3 4 5z" fill="#fff"/><circle cx="21" cy="11" r="2.6" fill="#fff"/></svg></span>'
         . '<span class="logo-text">' . e(APP_NAME) . '</span>';
}

/** <img> de marca con respaldo remoto si el archivo local aún no existe. */
function brand_img(string $key, string $alt, string $class = ''): string
{
    [$local, $remote] = BRAND_IMAGES[$key];
    $src = is_file(__DIR__ . '/' . $local) ? $local : $remote;
    // Si la imagen remota no carga, se usa una foto incluida (o se oculta la ilustración)
    $fallback = ['photo1' => 'assets/img/respaldo-1.jpg', 'photo2' => 'assets/img/respaldo-2.jpg'][$key] ?? null;
    $onerror = $fallback
        ? "this.onerror=null;this.src='" . $fallback . "'"
        : "this.onerror=null;this.style.display='none'";
    return '<img src="' . e($src) . '" alt="' . e($alt) . '" class="' . e($class) . '" loading="eager" referrerpolicy="no-referrer" onerror="' . e($onerror) . '">';
}

/** Iniciales y color estable para el avatar de un usuario. */
function avatar(string $username, string $size = ''): string
{
    $colors = ['#0f766e', '#be123c', '#b45309', '#1d4ed8', '#7c3aed', '#047857', '#c2410c'];
    $c = $colors[crc32($username) % count($colors)];
    $ini = mb_strtoupper(mb_substr($username, 0, 2));
    return '<span class="avatar ' . e($size) . '" style="--av:' . $c . '" aria-hidden="true">' . e($ini) . '</span>';
}

/** Fecha en español: 31 oct 2026. */
function fecha(string $dt): string
{
    $m = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $t = strtotime($dt);
    return date('j', $t) . ' ' . $m[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}
