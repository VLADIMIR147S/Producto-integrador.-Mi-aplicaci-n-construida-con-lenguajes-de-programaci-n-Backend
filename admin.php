<?php
/** admin.php — Panel de administración: estadísticas y lista de usuarios. */
require __DIR__ . '/config.php';
$me = require_admin();

$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';
$sql = 'SELECT u.id, u.username, u.email, u.role, u.created_at,
               COUNT(i.id) AS n_images, COALESCE(SUM(i.size),0) AS used
        FROM users u LEFT JOIN images i ON i.user_id = u.id WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (u.username LIKE ? OR u.email LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
if (in_array($role, ['user', 'admin'], true)) {
    $sql .= ' AND u.role = ?';
    $params[] = $role;
}
$sql .= ' GROUP BY u.id, u.username, u.email, u.role, u.created_at ORDER BY u.created_at DESC, u.id DESC';
$st = db()->prepare($sql);
$st->execute($params);
$users = $st->fetchAll();

$tot = db()->query("SELECT (SELECT COUNT(*) FROM users) u,
                           (SELECT COUNT(*) FROM users WHERE role = 'admin') a,
                           (SELECT COUNT(*) FROM images) i,
                           (SELECT COALESCE(SUM(size),0) FROM images) s")->fetch();

$pageTitle = 'Usuarios';
$breadcrumb = 'Administración';
require __DIR__ . '/partials/header.php';
?>
<div class="stat-grid four">
    <div class="stat-card"><span class="stat-icon si-1"><?= icon('users') ?></span><div><small>Usuarios</small><strong><?= (int)$tot['u'] ?></strong></div></div>
    <div class="stat-card"><span class="stat-icon si-4"><?= icon('crown') ?></span><div><small>Administradores</small><strong><?= (int)$tot['a'] ?></strong></div></div>
    <div class="stat-card"><span class="stat-icon si-2"><?= icon('images') ?></span><div><small>Imágenes</small><strong><?= (int)$tot['i'] ?></strong></div></div>
    <div class="stat-card"><span class="stat-icon si-3"><?= icon('database') ?></span><div><small>Almacenamiento</small><strong><?= e(human_size((int)$tot['s'])) ?></strong></div></div>
</div>

<section class="card table-card">
    <form class="toolbar inside" method="get" role="search">
        <label class="search-box">
            <?= icon('search') ?>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por usuario o correo…" aria-label="Buscar usuario">
        </label>
        <select name="role" class="select" onchange="this.form.submit()" aria-label="Filtrar por rol">
            <option value="">Todos los roles</option>
            <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Usuarios</option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administradores</option>
        </select>
        <span class="result-count"><?= count($users) ?> resultado(s)</span>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead>
            <tr><th>Usuario</th><th>Rol</th><th>Imágenes</th><th>Espacio</th><th>Alta</th><th class="right"><span class="sr-only">Acciones</span></th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): $isMe = (int)$u['id'] === (int)$me['id']; ?>
                <tr>
                    <td data-label="Usuario">
                        <div class="user-cell">
                            <?= avatar($u['username']) ?>
                            <div><strong><?= e($u['username']) ?></strong><?= $isMe ? ' <span class="tag">tú</span>' : '' ?><small><?= e($u['email']) ?></small></div>
                        </div>
                    </td>
                    <td data-label="Rol"><span class="pill <?= $u['role'] === 'admin' ? 'pill-brand' : '' ?>"><?= $u['role'] === 'admin' ? 'Administrador' : 'Usuario' ?></span></td>
                    <td data-label="Imágenes"><?= (int)$u['n_images'] ?></td>
                    <td data-label="Espacio"><?= e(human_size((int)$u['used'])) ?></td>
                    <td data-label="Alta"><?= e(fecha($u['created_at'])) ?></td>
                    <td class="right">
                        <div class="row-actions">
                            <a class="icon-btn" href="admin_edit.php?id=<?= (int)$u['id'] ?>" title="Editar" aria-label="Editar a <?= e($u['username']) ?>"><?= icon('edit') ?></a>
                            <?php if (!$isMe): ?>
                                <form action="admin_delete.php" method="post"
                                      data-confirm="Se eliminará a “<?= e($u['username']) ?>” y sus <?= (int)$u['n_images'] ?> imagen(es). Esta acción no se puede deshacer.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button class="icon-btn danger" title="Eliminar" aria-label="Eliminar a <?= e($u['username']) ?>"><?= icon('trash') ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="6" class="center muted pad">No hay usuarios que coincidan con la búsqueda.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
