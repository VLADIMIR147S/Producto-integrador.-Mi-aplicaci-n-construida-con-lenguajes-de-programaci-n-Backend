<?php
/** index.php — Página de inicio pública. Si ya hay sesión, va directo a la galería. */
require __DIR__ . '/config.php';
if (current_user()) {
    redirect('gallery.php');
}
$pageTitle = APP_TAGLINE;
$layout = 'bare';
require __DIR__ . '/partials/header.php';
?>
<div class="landing">
    <header class="land-nav">
        <a class="brand" href="index.php"><?= logo() ?></a>
        <nav>
            <a href="#funciones" class="land-link">Funciones</a>
            <a href="#como-funciona" class="land-link">Cómo funciona</a>
            <a href="login.php" class="btn btn-ghost">Iniciar sesión</a>
            <a href="register.php" class="btn btn-primary">Crear cuenta</a>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow"><?= icon('shield') ?> Privada por diseño</span>
            <h1>Tus imágenes, <span class="grad-text">organizadas y a salvo</span> en un solo lugar.</h1>
            <p class="lead">Crea tu galería personal, sube fotos desde el celular o la computadora y encuéntralas al instante. Solo tú puedes verlas.</p>
            <div class="hero-cta">
                <a href="register.php" class="btn btn-primary btn-lg">Comenzar gratis <?= icon('arrow') ?></a>
                <a href="login.php" class="btn btn-ghost btn-lg">Ya tengo cuenta</a>
            </div>
            <ul class="hero-points">
                <li><?= icon('check') ?> Sin instalar nada</li>
                <li><?= icon('check') ?> JPG, PNG, GIF y WEBP</li>
                <li><?= icon('check') ?> Funciona en cualquier dispositivo</li>
            </ul>
        </div>
        <div class="hero-art" aria-hidden="true">
            <div class="blob"></div>
            <figure class="photo-card pc-1"><?= brand_img('photo1', '') ?></figure>
            <figure class="photo-card pc-2"><?= brand_img('photo2', '') ?></figure>
            <?= brand_img('mascot', '', 'mascot') ?>
            <div class="float-chip fc-1"><?= icon('upload') ?> 3 imágenes subidas</div>
            <div class="float-chip fc-2"><?= icon('lock') ?> Solo tú tienes acceso</div>
        </div>
    </section>

    <section class="features" id="funciones">
        <h2 class="section-title">Todo lo que necesitas para tu galería</h2>
        <p class="section-sub">Diseñada para ser simple, rápida y segura.</p>
        <div class="feature-grid">
            <article class="feature">
                <span class="f-icon fi-1"><?= icon('lock') ?></span>
                <h3>Galería privada</h3>
                <p>Cada cuenta ve únicamente sus imágenes. El servidor verifica al dueño antes de mostrar cualquier archivo.</p>
            </article>
            <article class="feature">
                <span class="f-icon fi-2"><?= icon('zap') ?></span>
                <h3>Subida rápida</h3>
                <p>Arrastra varias imágenes a la vez, pégalas con Ctrl+V y sigue el avance con una barra de progreso.</p>
            </article>
            <article class="feature">
                <span class="f-icon fi-3"><?= icon('phone') ?></span>
                <h3>Cualquier dispositivo</h3>
                <p>Interfaz adaptable a celular, tableta y computadora, con modo oscuro y navegación por teclado.</p>
            </article>
            <article class="feature">
                <span class="f-icon fi-4"><?= icon('users') ?></span>
                <h3>Administración</h3>
                <p>Un administrador puede modificar o eliminar los datos de cualquier usuario y moderar contenido.</p>
            </article>
        </div>
    </section>

    <section class="steps" id="como-funciona">
        <h2 class="section-title">Empieza en tres pasos</h2>
        <ol class="step-list">
            <li><span class="step-n">1</span><div><h3>Crea tu cuenta</h3><p>Solo necesitas un nombre de usuario, correo y contraseña.</p></div></li>
            <li><span class="step-n">2</span><div><h3>Sube tus imágenes</h3><p>Ponles título y obsérvalas en una cuadrícula ordenada.</p></div></li>
            <li><span class="step-n">3</span><div><h3>Organiza y comparte</h3><p>Busca, amplía, descarga o elimina cuando quieras.</p></div></li>
        </ol>
        <div class="cta-band">
            <div>
                <h2>¿Listo para crear tu galería?</h2>
                <p>Es gratis y tarda menos de un minuto.</p>
            </div>
            <a href="register.php" class="btn btn-light btn-lg">Crear cuenta <?= icon('arrow') ?></a>
        </div>
    </section>

    <footer class="land-footer">
        <span class="brand"><?= logo() ?></span>
        <p>© <?= date('Y') ?> <?= e(APP_NAME) ?> · Aplicación web con HTML, CSS, JavaScript y PHP.</p>
        <p class="credit">Ilustración: Vecteezy · Fotos de respaldo: vultilion y danielbuechele (CC BY 2.0).</p>
    </footer>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
