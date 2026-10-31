<?php
/** profile.php — El usuario consulta y actualiza sus propios datos. */
require __DIR__ . '/config.php';
$me = require_login();

$errors = [];
$section = $_POST['section'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $hash = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $hash->execute([$me['id']]);
    $hash = $hash->fetchColumn();

    if ($section === 'data') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $errors = validate_user($me['username'], $email, null, (int)$me['id']);
        if (!$errors) {
            db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $me['id']]);
            flash('Tus datos se actualizaron.');
            redirect('profile.php');
        }
    } elseif ($section === 'password') {
        $cur = $_POST['current'] ?? '';
        $new = $_POST['password'] ?? '';
        if (!password_verify($cur, $hash)) {
            $errors[] = 'La contraseña actual no es correcta.';
        }
        if (strlen($new) < 8 || !preg_match('/\d/', $new) || !preg_match('/[A-Za-z]/', $new)) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres e incluir letras y números.';
        }
        if ($new !== ($_POST['password2'] ?? '')) {
            $errors[] = 'Las contraseñas nuevas no coinciden.';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            session_regenerate_id(true);
            flash('Contraseña actualizada.');
            redirect('profile.php');
        }
    }
}

$st = db()->prepare('SELECT COUNT(*) n, COALESCE(SUM(size),0) s, MAX(created_at) last FROM images WHERE user_id = ?');
$st->execute([$me['id']]);
$stats = $st->fetch();

$pageTitle = 'Mi cuenta';
$breadcrumb = 'Espacio de trabajo';
require __DIR__ . '/partials/header.php';
?>
<section class="profile-hero card">
    <?= avatar($me['username'], 'avatar-xl') ?>
    <div>
        <h2><?= e($me['username']) ?></h2>
        <p class="muted"><?= e($me['email']) ?> · Miembro desde <?= e(fecha($me['created_at'])) ?></p>
    </div>
    <span class="pill <?= $me['role'] === 'admin' ? 'pill-brand' : '' ?>"><?= $me['role'] === 'admin' ? icon('crown') . ' Administrador' : 'Usuario' ?></span>
</section>

<div class="stat-grid">
    <div class="stat-card"><span class="stat-icon si-1"><?= icon('images') ?></span><div><small>Imágenes</small><strong><?= (int)$stats['n'] ?></strong></div></div>
    <div class="stat-card"><span class="stat-icon si-2"><?= icon('database') ?></span><div><small>Espacio usado</small><strong><?= e(human_size((int)$stats['s'])) ?></strong></div></div>
    <div class="stat-card"><span class="stat-icon si-3"><?= icon('calendar') ?></span><div><small>Última subida</small><strong><?= $stats['last'] ? e(fecha($stats['last'])) : '—' ?></strong></div></div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error" role="alert"><?= icon('alert') ?><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="two-col">
    <section class="card">
        <h2 class="card-title">Datos de la cuenta</h2>
        <p class="muted">El nombre de usuario solo lo puede cambiar un administrador.</p>
        <form method="post" class="form" novalidate data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="data">
            <label class="field">Nombre de usuario
                <input value="<?= e($me['username']) ?>" disabled>
            </label>
            <label class="field">Correo electrónico
                <input type="email" name="email" required value="<?= e($_POST['email'] ?? $me['email']) ?>">
            </label>
            <div class="form-actions"><button class="btn btn-primary">Guardar cambios</button></div>
        </form>
    </section>
    <section class="card">
        <h2 class="card-title">Cambiar contraseña</h2>
        <p class="muted">Por seguridad, confirma tu contraseña actual.</p>
        <form method="post" class="form" novalidate data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="password">
            <label class="field">Contraseña actual
                <input type="password" name="current" required autocomplete="current-password">
            </label>
            <label class="field">Nueva contraseña
                <span class="pw-wrap"><input type="password" name="password" required minlength="8" autocomplete="new-password" data-strength>
                <button type="button" class="pw-toggle" aria-label="Mostrar contraseña"><?= icon('eye') ?></button></span>
                <span class="meter" aria-hidden="true"><span></span></span>
                <small class="hint" data-strength-label>Usa letras y números.</small>
            </label>
            <label class="field">Confirmar nueva contraseña
                <input type="password" name="password2" required minlength="8" autocomplete="new-password" data-match="password">
            </label>
            <div class="form-actions"><button class="btn btn-primary">Actualizar contraseña</button></div>
        </form>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
