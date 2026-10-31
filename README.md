# Guacamaya — Aplicación web de gestión de imágenes

Aplicación web responsiva para crear una galería personal de imágenes.
**Cliente:** HTML5, CSS3 y JavaScript. **Servidor:** PHP 8 con PDO (SQLite o MySQL).

## Funciones
- Página de inicio pública con presentación del producto.
- Registro, inicio y cierre de sesión (contraseñas cifradas con `password_hash`), medidor de seguridad de contraseña.
- Galería privada en mosaico: cada usuario solo ve, sube, descarga y borra **sus** imágenes.
- Subida en ventana modal: arrastrar a cualquier parte de la pantalla, pegar (Ctrl+V), vista previa, títulos editables y barra de progreso.
- Miniaturas generadas en el servidor (GD) para que la galería cargue rápido en celulares.
- Selección múltiple para borrar varias imágenes a la vez.
- Búsqueda instantánea, orden por fecha, nombre o tamaño, y visor con detalles (teclado y deslizamiento táctil).
- "Mi cuenta": el usuario actualiza su correo y contraseña.
- Rol **administrador**: estadísticas, filtro por rol, modifica datos de cualquier usuario (nombre, correo, rol, contraseña), elimina usuarios con sus imágenes y modera imágenes.
- Diseño responsivo con barra lateral, modo oscuro automático y accesibilidad (teclado, foco visible, etiquetas).

## Imágenes de la portada
Guarde sus imágenes en `assets/img/` como `guacamaya.png`, `foto-1.jpg` y `foto-2.jpg`
(ver `assets/img/LEEME.txt`). Si no están, se cargan desde los enlaces de `config.php`.

## Instalación rápida (local)
```bash
cd galeria
php -S localhost:8000
```
Abrir http://localhost:8000. La base SQLite se crea sola en `data/`.

**Administrador inicial:** usuario `admin` · contraseña `Admin123!` (cámbiela desde *Administración → Editar*).

## Publicar gratis en Internet (InfinityFree u otro hosting PHP)
1. Crear cuenta en https://www.infinityfree.com y un sitio (subdominio gratuito).
2. Abrir el *File Manager* (o FileZilla) y subir **el contenido** de esta carpeta a `htdocs/`.
3. Opción A (recomendada si el hosting tiene `pdo_sqlite`): no hay que hacer nada más.
   Opción B (MySQL): crear la base en el panel, importar `install.sql` con phpMyAdmin
   (quitar las líneas `CREATE DATABASE`/`USE`), y en `config.php` poner
   `DB_DRIVER = 'mysql'` con el host, nombre, usuario y contraseña que da el panel.
4. Visitar la URL del sitio, entrar como `admin` y cambiar la contraseña.
5. Compartir el enlace en el foro.

## Estructura
| Archivo | Lado | Función |
|---|---|---|
| `config.php`, `db.php` | Servidor | Configuración, conexión PDO, sesión, CSRF, validaciones |
| `login.php`, `register.php`, `logout.php` | Servidor + HTML | Módulo de usuarios |
| `index.php` | Servidor + HTML | Página de inicio |
| `gallery.php`, `upload.php`, `delete.php`, `image.php` | Servidor + HTML | Módulo de imágenes |
| `profile.php` | Servidor + HTML | Datos de la propia cuenta |
| `admin.php`, `admin_edit.php`, `admin_delete.php` | Servidor + HTML | Módulo de administración |
| `assets/style.css` | Cliente | Diseño responsivo, modo oscuro, accesibilidad |
| `assets/app.js` | Cliente | Validación, vista previa, arrastrar/soltar, progreso, selección múltiple, filtro instantáneo, visor, confirmaciones, menú móvil |

## Seguridad aplicada
Consultas preparadas (inyección SQL), `htmlspecialchars` (XSS), tokens CSRF, `session_regenerate_id`,
validación real del tipo de archivo con `finfo` + `getimagesize`, nombres aleatorios,
carpeta `uploads/` bloqueada y entrega de imágenes solo tras comprobar al dueño.
