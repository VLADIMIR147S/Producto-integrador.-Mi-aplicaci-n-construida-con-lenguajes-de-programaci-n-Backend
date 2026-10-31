<?php
/** login.php — Inicio de sesión. */
require __DIR__ . '/config.php';

if (current_user()) {
    redirect('gallery.php');
}

$error = '';
$login = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';

    // Límite simple de intentos por sesión (mitiga fuerza bruta)
    $_SESSION['tries'] = ($_SESSION['tries'] ?? 0);
    if ($_SESSION['tries'] >= 5 && time() - ($_SESSION['last_try'] ?? 0) < 60) {
        $error = 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.';
    } else {
        $st = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?');
        $st->execute([$login, strtolower($login)]);
        $u = $st->fetch();
        if ($u && password_verify($pass, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            unset($_SESSION['tries']);
            flash('¡Hola de nuevo, ' . $u['username'] . '!');
            redirect('gallery.php');
        }
        $_SESSION['tries']++;
        $_SESSION['last_try'] = time();
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$pageTitle = 'Iniciar sesión';
$layout = 'bare';
require __DIR__ . '/partials/header.php';
?>
<div class="auth">
    <?php require __DIR__ . '/partials/auth_aside.php'; ?>
    <section class="auth-panel">
        <div class="auth-box">
            <a class="brand auth-mobile-brand" href="index.php"><?= logo() ?></a>
            <h1>Bienvenido de nuevo</h1>
            <p class="muted">Ingresa a tu galería personal.</p>
            <?php if ($error): ?><div class="alert alert-error" role="alert"><?= icon('alert') ?><span><?= e($error) ?></span></div><?php endif; ?>
            <form method="post" class="form" novalidate data-validate>
                <?= csrf_field() ?>
                <label class="field">Usuario o correo
                    <span class="input-icon"><?= icon('user') ?><input name="login" required autocomplete="username" value="<?= e($login) ?>" autofocus placeholder="tu_usuario"></span>
                </label>
                <label class="field">Contraseña
                    <span class="input-icon pw-wrap"><?= icon('lock') ?>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                        <button type="button" class="pw-toggle" aria-label="Mostrar contraseña"><?= icon('eye') ?></button>
                    </span>
                </label>
                <button class="btn btn-primary btn-block btn-lg">Iniciar sesión</button>
            </form>
            <p class="auth-switch">¿Aún no tienes cuenta? <a href="register.php">Crea una gratis</a></p>
        </div>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
