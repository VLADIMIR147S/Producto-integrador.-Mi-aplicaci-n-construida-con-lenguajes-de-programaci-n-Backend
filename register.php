<?php
/** register.php — Alta de usuarios nuevos (rol "user"). */
require __DIR__ . '/config.php';

if (current_user()) {
    redirect('gallery.php');
}

$errors = [];
$username = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    $errors = validate_user($username, $email, $pass);
    if ($pass !== $pass2) {
        $errors[] = 'Las contraseñas no coinciden.';
    }
    if (!$errors) {
        $st = db()->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $st->execute([$username, $email, password_hash($pass, PASSWORD_DEFAULT), 'user']);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)db()->lastInsertId();
        flash('Tu cuenta está lista. ¡Sube tu primera imagen!');
        redirect('gallery.php');
    }
}

$pageTitle = 'Crear cuenta';
$layout = 'bare';
require __DIR__ . '/partials/header.php';
?>
<div class="auth">
    <?php require __DIR__ . '/partials/auth_aside.php'; ?>
    <section class="auth-panel">
        <div class="auth-box">
            <a class="brand auth-mobile-brand" href="index.php"><?= logo() ?></a>
            <h1>Crea tu cuenta</h1>
            <p class="muted">Tu galería es privada: solo tú puedes ver tus imágenes.</p>
            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert"><?= icon('alert') ?><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post" class="form" novalidate data-validate>
                <?= csrf_field() ?>
                <label class="field">Nombre de usuario
                    <span class="input-icon"><?= icon('user') ?><input name="username" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_.]{3,30}" autocomplete="username" value="<?= e($username) ?>" autofocus placeholder="maria.lopez"></span>
                </label>
                <label class="field">Correo electrónico
                    <span class="input-icon"><?= icon('info') ?><input type="email" name="email" required autocomplete="email" value="<?= e($email) ?>" placeholder="nombre@correo.com"></span>
                </label>
                <label class="field">Contraseña
                    <span class="input-icon pw-wrap"><?= icon('lock') ?>
                        <input type="password" name="password" required minlength="8" autocomplete="new-password" data-strength placeholder="Mínimo 8 caracteres">
                        <button type="button" class="pw-toggle" aria-label="Mostrar contraseña"><?= icon('eye') ?></button>
                    </span>
                    <span class="meter" aria-hidden="true"><span></span></span>
                    <small class="hint" data-strength-label>Usa letras y números.</small>
                </label>
                <label class="field">Confirmar contraseña
                    <span class="input-icon"><?= icon('lock') ?><input type="password" name="password2" required minlength="8" autocomplete="new-password" data-match="password" placeholder="Repite la contraseña"></span>
                </label>
                <button class="btn btn-primary btn-block btn-lg">Crear cuenta</button>
            </form>
            <p class="auth-switch">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
        </div>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
