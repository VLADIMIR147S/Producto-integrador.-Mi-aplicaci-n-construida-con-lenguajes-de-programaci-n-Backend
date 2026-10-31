<?php
/** admin_edit.php — El administrador modifica los datos de un usuario y modera sus imágenes. */
require __DIR__ . '/config.php';
$me = require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = db()->prepare('SELECT id, username, email, role, created_at FROM users WHERE id = ?');
$st->execute([$id]);
$user = $st->fetch();
if (!$user) {
    flash('Usuario no encontrado.', 'error');
    redirect('admin.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $role     = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $pass     = $_POST['password'] ?? '';

    $errors = validate_user($username, $email, $pass === '' ? null : $pass, $id);

    // Evita quedarse sin administradores
    if ($user['role'] === 'admin' && $role === 'user') {
        $admins = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        if ($admins <= 1) {
            $errors[] = 'Debe existir al menos un administrador.';
        }
    }

    if (!$errors) {
        if ($pass !== '') {
            $up = db()->prepare('UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?');
            $up->execute([$username, $email, $role, password_hash($pass, PASSWORD_DEFAULT), $id]);
        } else {
            $up = db()->prepare('UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?');
            $up->execute([$username, $email, $role, $id]);
        }
        flash("Datos de “{$username}” actualizados.");
        redirect('admin.php');
    }
    $user = array_merge($user, ['username' => $username, 'email' => $email, 'role' => $role]);
}

$im = db()->prepare('SELECT id, title, size FROM images WHERE user_id = ? ORDER BY created_at DESC, id DESC');
$im->execute([$id]);
$images = $im->fetchAll();

$pageTitle = 'Editar usuario';
$breadcrumb = '<a href="admin.php">Usuarios</a> / ' . e($user['username']);
require __DIR__ . '/partials/header.php';
?>
<section class="profile-hero card">
    <?= avatar($user['username'], 'avatar-xl') ?>
    <div>
        <h2><?= e($user['username']) ?></h2>
        <p class="muted"><?= e($user['email']) ?> · Alta: <?= e(fecha($user['created_at'])) ?> · <?= count($images) ?> imagen(es)</p>
    </div>
    <span class="pill <?= $user['role'] === 'admin' ? 'pill-brand' : '' ?>"><?= $user['role'] === 'admin' ? 'Administrador' : 'Usuario' ?></span>
</section>

<?php if ($errors): ?>
    <div class="alert alert-error" role="alert"><?= icon('alert') ?><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="two-col">
    <section class="card">
        <h2 class="card-title">Datos del usuario</h2>
        <form method="post" class="form" novalidate data-validate>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
            <label class="field">Nombre de usuario
                <input name="username" required pattern="[A-Za-z0-9_.]{3,30}" value="<?= e($user['username']) ?>">
            </label>
            <label class="field">Correo electrónico
                <input type="email" name="email" required value="<?= e($user['email']) ?>">
            </label>
            <fieldset class="field segmented">
                <legend>Rol</legend>
                <label><input type="radio" name="role" value="user" <?= $user['role'] === 'user' ? 'checked' : '' ?>><span><?= icon('user') ?> Usuario</span></label>
                <label><input type="radio" name="role" value="admin" <?= $user['role'] === 'admin' ? 'checked' : '' ?>><span><?= icon('crown') ?> Administrador</span></label>
            </fieldset>
            <label class="field"><span>Nueva contraseña <span class="muted">(opcional)</span></span>
                <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Déjalo vacío para no cambiarla">
            </label>
            <div class="form-actions">
                <a class="btn btn-ghost" href="admin.php">Cancelar</a>
                <button class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="card-title">Imágenes del usuario</h2>
        <p class="muted">Como administrador puedes revisar y retirar contenido.</p>
        <?php if (!$images): ?>
            <div class="empty-inline"><?= icon('image') ?> Este usuario aún no tiene imágenes.</div>
        <?php else: ?>
            <ul class="mini-grid">
                <?php foreach ($images as $img): ?>
                    <li>
                        <img src="image.php?id=<?= (int)$img['id'] ?>&amp;thumb=1" alt="<?= e($img['title']) ?>" loading="lazy" title="<?= e($img['title']) ?>">
                        <form action="admin_delete.php" method="post" data-confirm="Se eliminará la imagen “<?= e($img['title']) ?>”.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                            <input type="hidden" name="back" value="<?= (int)$user['id'] ?>">
                            <button class="icon-btn danger sm" aria-label="Eliminar <?= e($img['title']) ?>"><?= icon('trash') ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
