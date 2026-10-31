<?php
/** gallery.php — Galería privada del usuario en sesión. */
require __DIR__ . '/config.php';
$me = require_login();

// Búsqueda y orden (procesados en el servidor)
$q     = trim($_GET['q'] ?? '');
$sort  = $_GET['sort'] ?? 'new';
$orders = [
    'new'   => 'created_at DESC, id DESC',
    'old'   => 'created_at ASC, id ASC',
    'name'  => 'title ASC',
    'size'  => 'size DESC',
];
$orderSql = $orders[$sort] ?? $orders['new'];

$sql    = 'SELECT * FROM images WHERE user_id = ?';
$params = [$me['id']];
if ($q !== '') {
    $sql     .= ' AND title LIKE ?';
    $params[] = '%' . $q . '%';
}
$st = db()->prepare("$sql ORDER BY $orderSql");
$st->execute($params);
$images = $st->fetchAll();

$total = db()->prepare('SELECT COUNT(*) FROM images WHERE user_id = ?');
$total->execute([$me['id']]);
$total = (int)$total->fetchColumn();

$pageTitle = 'Mi galería';
$breadcrumb = 'Espacio de trabajo';
$pageActions = ($total ? '<button class="btn btn-ghost" id="select-mode" type="button">' . icon('select') . '<span class="hide-sm">Seleccionar</span></button>' : '')
             . '<button class="btn btn-primary" type="button" data-open-upload>' . icon('upload') . '<span class="hide-sm">Subir imágenes</span></button>';
require __DIR__ . '/partials/header.php';
?>

<?php if ($total): ?>
<form class="toolbar" method="get" role="search">
    <label class="search-box">
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título…" aria-label="Buscar por título" data-live-search>
    </label>
    <select name="sort" aria-label="Ordenar" onchange="this.form.submit()" class="select">
        <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Más recientes</option>
        <option value="old" <?= $sort === 'old' ? 'selected' : '' ?>>Más antiguas</option>
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nombre (A–Z)</option>
        <option value="size" <?= $sort === 'size' ? 'selected' : '' ?>>Tamaño</option>
    </select>
    <span class="result-count"><?= count($images) ?> de <?= $total ?> imágenes</span>
</form>
<?php endif; ?>

<?php if (!$total): ?>
    <section class="empty-state">
        <div class="empty-art"><?= brand_img('mascot', '', 'mascot') ?></div>
        <h2>Tu galería está esperando su primera imagen</h2>
        <p class="muted">Arrastra fotos a esta ventana o usa el botón para elegirlas desde tu dispositivo.</p>
        <button class="btn btn-primary btn-lg" data-open-upload><?= icon('upload') ?> Subir imágenes</button>
    </section>
<?php elseif (!$images): ?>
    <section class="empty-state small">
        <span class="empty-icon"><?= icon('search') ?></span>
        <h2>Sin resultados para “<?= e($q) ?>”</h2>
        <p class="muted">Prueba con otra palabra o <a href="gallery.php">ve todas tus imágenes</a>.</p>
    </section>
<?php else: ?>
    <ul class="masonry" id="grid">
        <?php foreach ($images as $i => $img):
            $id = (int)$img['id'];
            $meta = (int)$img['width'] . ' × ' . (int)$img['height'] . ' · ' . human_size((int)$img['size']);
        ?>
            <li class="tile" data-index="<?= $i ?>" data-src="image.php?id=<?= $id ?>"
                data-title="<?= e($img['title']) ?>" data-meta="<?= e($meta) ?>"
                data-date="<?= e(fecha($img['created_at'])) ?>" data-type="<?= e(strtoupper(ALLOWED_MIME[$img['mime']] ?? '')) ?>"
                data-id="<?= $id ?>">
                <label class="tile-check" title="Seleccionar">
                    <input type="checkbox" name="ids[]" value="<?= $id ?>" form="bulk-form" aria-label="Seleccionar <?= e($img['title']) ?>">
                    <span><?= icon('check') ?></span>
                </label>
                <button class="tile-img" type="button" aria-label="Ampliar <?= e($img['title']) ?>">
                    <img src="image.php?id=<?= $id ?>&amp;thumb=1" alt="<?= e($img['title']) ?>" loading="lazy" decoding="async"
                         width="<?= (int)$img['width'] ?>" height="<?= (int)$img['height'] ?>">
                </button>
                <div class="tile-info">
                    <div class="tile-text">
                        <strong title="<?= e($img['title']) ?>"><?= e($img['title']) ?></strong>
                        <small><?= e($meta) ?></small>
                    </div>
                    <div class="tile-actions">
                        <a class="icon-btn" href="image.php?id=<?= $id ?>&amp;download=1" title="Descargar" aria-label="Descargar <?= e($img['title']) ?>"><?= icon('download') ?></a>
                        <form action="delete.php" method="post" data-confirm="Se eliminará “<?= e($img['title']) ?>” de forma permanente.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="icon-btn danger" title="Eliminar" aria-label="Eliminar <?= e($img['title']) ?>"><?= icon('trash') ?></button>
                        </form>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- Barra de acciones para selección múltiple -->
<form id="bulk-form" class="bulkbar" action="delete.php" method="post" hidden data-confirm="Se eliminarán las imágenes seleccionadas de forma permanente.">
    <?= csrf_field() ?>
    <span class="bulk-count"><strong>0</strong> seleccionadas</span>
    <button type="button" class="btn btn-ghost btn-sm" data-select-all>Seleccionar todo</button>
    <button type="button" class="btn btn-ghost btn-sm" data-cancel-select>Cancelar</button>
    <button class="btn btn-danger-solid btn-sm" disabled><?= icon('trash') ?> Eliminar</button>
</form>

<!-- Ventana de carga -->
<dialog id="upload-dialog" class="modal" aria-labelledby="upload-title">
    <form id="upload-form" action="upload.php" method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <header class="modal-head">
            <div>
                <h2 id="upload-title">Subir imágenes</h2>
                <p class="muted">JPG, PNG, GIF o WEBP · hasta <?= e(human_size(MAX_FILE_SIZE)) ?> cada una</p>
            </div>
            <button type="button" class="icon-btn" data-close-upload aria-label="Cerrar"><?= icon('x') ?></button>
        </header>
        <div class="dropzone" id="dropzone">
            <input type="file" id="files" name="files[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
            <label for="files" class="dz-label">
                <span class="dz-icon"><?= icon('upload') ?></span>
                <span><strong>Arrastra tus imágenes aquí</strong> o <u>búscalas en tu dispositivo</u></span>
                <small>También puedes pegarlas con Ctrl + V</small>
            </label>
        </div>
        <div id="previews" class="previews" hidden></div>
        <footer class="modal-foot">
            <div class="progress" hidden><span></span></div>
            <span class="upload-summary muted" id="upload-summary"></span>
            <button type="button" class="btn btn-ghost" data-close-upload>Cancelar</button>
            <button class="btn btn-primary" id="upload-btn" disabled>Subir</button>
        </footer>
        <noscript><button class="btn btn-primary">Subir</button></noscript>
    </form>
</dialog>

<!-- Capa al arrastrar archivos sobre la ventana -->
<div class="drop-overlay" id="drop-overlay" hidden>
    <div><?= icon('upload') ?><p>Suelta las imágenes para subirlas</p></div>
</div>

<!-- Visor -->
<dialog id="lightbox" class="lightbox" aria-label="Visor de imagen">
    <div class="lb-stage">
        <button class="lb-nav lb-prev" aria-label="Anterior"><?= icon('left') ?></button>
        <img alt="">
        <button class="lb-nav lb-next" aria-label="Siguiente"><?= icon('right') ?></button>
    </div>
    <aside class="lb-panel">
        <div class="lb-top">
            <span class="lb-counter"></span>
            <button class="icon-btn lb-close" aria-label="Cerrar visor"><?= icon('x') ?></button>
        </div>
        <h2 class="lb-title"></h2>
        <dl class="lb-meta">
            <div><dt>Dimensiones y peso</dt><dd data-f="meta"></dd></div>
            <div><dt>Formato</dt><dd data-f="type"></dd></div>
            <div><dt>Subida</dt><dd data-f="date"></dd></div>
        </dl>
        <div class="lb-actions">
            <a class="btn btn-ghost btn-block lb-download" href="#"><?= icon('download') ?> Descargar original</a>
        </div>
        <p class="lb-hint muted">Usa ← → para navegar · Esc para cerrar</p>
    </aside>
</dialog>

<?php require __DIR__ . '/partials/footer.php'; ?>
