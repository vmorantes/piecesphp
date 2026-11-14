# Archivos protegidos

PiecesPHP decide quién descarga los archivos que suben los módulos **por el nombre del archivo en
disco**, no por su carpeta. Así funciona igual con Apache solo que con nginx delante (HestiaCP),
y lo público se sirve a toda velocidad, sin PHP.

**Toda carpeta de subidas tiene que estar protegida o declarada pública.** `bin/cli
verify-integrity` (comprobación 29) falla si un módulo declara un `UPLOAD_DIR` que no está en
ninguno de los dos sitios.

---

## Cómo funciona

- **Un archivo privado se guarda con un sufijo AL FINAL de su nombre:** `foto.jpg.protected`. El
  sufijo se configura en `protected_uploads_suffix` (por defecto `.protected`).
- **Su URL no cambia:** sigue siendo `…/foto.jpg`. El sufijo solo existe en disco, así que las
  rutas guardadas en la base y en el HTML no se tocan nunca.
- **Pedir `…/foto.jpg`:**
  - si existe `foto.jpg`, es público: lo sirven Apache o nginx directamente;
  - si no existe, la petición llega a PHP. `ServerStatics` busca `foto.jpg.protected`, aplica la
    política de su carpeta y, si procede, lo sirve con el tipo de `foto.jpg`, con
    `Cache-Control: private` y `Vary: Cookie, Authorization`.
- **Pedir `…/foto.jpg.protected` no funciona nunca:** lo niega `statics/uploads/.htaccess`, que
  escribe el subsistema, y PHP responde 404. Nginx no reconoce `.protected` como extensión
  estática, así que se lo pasa a Apache.
- Todo esto vive en `PiecesPHP\Core\Statics` (`ServerStatics`, `ProtectFileMiddleware` y
  `ProtectedUploads`). Los nombres viejos, `PiecesPHP\Core\ServerStatics` y
  `PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware`, ya no existen desde la 9: se usa el de
  `PiecesPHP\Core\Statics`.
- El registro de las carpetas se hace en cada arranque: las de los módulos del framework en
  `src/app/core/extensions/protected-files.php` y las tuyas en `src/app/config/extensions/protected-files.php`. Las
  dos las carga también `bin/cli`.
- La sesión llega por la cabecera `JWTAuth` o por la cookie del mismo nombre, así que un
  `<img src>` del panel también la lleva.

---

## Los tres casos, con el módulo de referencia

### 1. Privado: solo con sesión

Lo que únicamente muestra la zona de administración (documentos, organizaciones, categorías de
noticias). Sus subidas **nacen** con el sufijo:

```php
use Documents\Controllers\DocumentsController;
use PiecesPHP\Core\Statics\ProtectFileMiddleware;

$uploadsDir = get_config('upload_dir');

ProtectFileMiddleware::protectWithSession(
    append_to_path_system($uploadsDir, DocumentsController::UPLOAD_DIR)
);
```

Al guardar una subida, el módulo usa el nombre privado (`ProtectedUploads::privatePath()`). Al
reemplazarla, busca la anterior con `ProtectedUploads::resolve()` para borrarla aunque sea privada.

**«Con sesión» es la misma regla que el panel** (desde la 10.17.0): no basta con una sesión abierta. No se le sirve a un
usuario inactivo, ni al de una organización que no admite sesiones, ni al pendiente o rechazado, que en el panel solo
ve lo suyo; a root, siempre. Lo decide `UserSessions::jwtOpensProtectedFiles()`, con las mismas piezas que usa el panel.

### 2. Mixto: depende del registro al que pertenece el archivo (el arquetipo)

**Publications** es la referencia. Sus archivos viven en `publications/<folder>/…` (los
adjuntos, en `<folder>/attachments/…`), y `folder` es una columna de la publicación.

- **La carpeta se protege o se libera con la visibilidad de la publicación**
  (`PublicationMapper::isVisibleToPublic()`: activa, en fecha y aprobada si las aprobaciones están
  activas):
  - al crear y al editar;
  - al aprobar o rechazar;
  - por fecha, con una tarea del cron que sincroniza las programadas y las caducadas.
- Una publicación visible tiene sus archivos con el nombre real: se sirven directos.
- Una no visible los tiene con el sufijo:
  - con sesión se sirven por PHP, solo a quien puede ver la publicación o editarla
    (`PublicationMapper::canBePreviewedBy()` o el permiso de su formulario);
  - sin sesión, el validador (`PublicationsController::uploadedFileValidator()` y
    `publicFileIsServable()`) vuelve a comprobar la visibilidad y falla cerrado.
- Las carpetas sin publicación que las nombre son privadas: con sesión, sus archivos solo los recibe quien puede ver
  todas las publicaciones (`PublicationMapper::CAN_VIEW_ALL`).

```php
ProtectFileMiddleware::protect(
    append_to_path_system($uploadsDir, PublicationsController::UPLOAD_DIR),
    [PublicationsController::class, 'uploadedFileValidator']
);
```

Para un módulo nuevo que se clona de Publications: cambia el mapper y el criterio de
visibilidad, conserva la forma y llama a `ProtectedUploads::setFolderVisibility()` donde cambie la
visibilidad. `protect()` sin validador **falla cerrado**.

### 3. Público a propósito: se declara

Lo que la zona pública muestra siempre, como la portada (banners) o la imagen de inicio, no se
protege: se **declara** en `files/dev/upload-dirs.json`, en `publicas`, con su motivo. El registro
solo puede encoger: una entrada que ya no casa con ningún `UPLOAD_DIR` hace fallar la
comprobación 29.

---

## Al crear un módulo con subidas

1. Declara `const UPLOAD_DIR = '<carpeta>'` en su controlador, como literal: la comprobación 29
   no resuelve expresiones.
2. Protégela en `protected-files.php` (caso 1 o 2), o declárala pública con su motivo (caso 3).
3. Guarda las subidas privadas con `ProtectedUploads::privatePath()`, y las mixtas según la
   visibilidad de su registro.
4. Ejecuta `bin/cli verify-integrity`: la comprobación 29 dice cuántas carpetas hay protegidas,
   públicas y sin archivos.

## Migrar una instalación existente

Tras actualizar, con una copia de `src/statics/uploads` hecha antes:

```bash
bin/cli statics-protect-migrate            # simulacro: dice qué renombraría
bin/cli statics-protect-migrate --run      # renombra lo privado y, al final, retira los .htaccess viejos
bin/cli statics-protect-migrate --revert   # vuelta atrás
```

- **El orden importa, y la tarea lo respeta:** primero renombra todo lo privado y después retira
  los `.htaccess` de cada carpeta. Al revés, lo privado quedaría servido directamente.
- **`--revert` deja con su nombre público** también los archivos que ya nacieron privados antes
  de migrar.

## Servidores

- **Apache solo:** no hace falta nada más.
- **nginx delante de Apache** (HestiaCP, Plesk y otros paneles): ver «Lo que puede ejecutar código», abajo.
- **nginx sin Apache:** añade una regla que niegue `\.protected$` y que pase a PHP lo que no
  existe, la de «Las subidas no ejecutan PHP» y la de «Lo que puede ejecutar código».

### Las subidas no ejecutan PHP

El `.htaccess` que el subsistema escribe en `statics/uploads/` y en la carpeta del Gestor de archivos niega servir y
ejecutar cualquier archivo PHP (`php`, `php3` a `php9`, `phtml`, `phar` y `phps`, sin distinguir mayúsculas;
`ProtectedUploads::SERVER_SCRIPT_EXTENSIONS`). Con Apache no hay que hacer nada: el arranque lo pone al día.
«Salud de la instalación» lo comprueba en su pestaña «Seguridad».

**Con nginx**, que no lee `.htaccess`, pon la misma regla en el bloque del sitio, antes del que pasa a PHP (ajusta la
ruta a donde esté `src/` en tu servidor):

```nginx
location ~* /statics/(uploads|filemanager)/.*\.(php[0-9]?|phtml|phar|phps)$ {
    deny all;
}
```

### Lo que puede ejecutar código: SVG, HTML y XML subidos

Un `.svg`, un `.html` o un `.xml` pueden llevar código activo. Por eso el framework sirve
los archivos de **subidas** con estas extensiones (`ProtectedUploads::ACTIVE_CONTENT_EXTENSIONS`: `svg`, `svgz`,
`html`, `htm`, `xhtml`, `xml`, `xsl`, `xslt`) con `Content-Security-Policy: sandbox`, que le prohíbe al navegador
ejecutar nada, y los que no son imagen además con `Content-Disposition: attachment` (se descargan). Un SVG se sigue
viendo como imagen. Lo hace por las dos vías:

- **Lo privado** lo sirve PHP (`ServerStatics`), y lleva las cabeceras con cualquier servidor. Por PHP salen dos
  políticas CSP, la `sandbox` y la general del sitio: el navegador aplica las dos.
- **Lo público** lo sirve el servidor web directamente, y las cabeceras las pone el `.htaccess` de
  `statics/uploads/`, que escribe el subsistema en cada arranque. **nginx no lee `.htaccess`**: si nginx sirve esas
  extensiones por su cuenta, lo público sale **sin** las cabeceras.

**Qué hacer con nginx delante de Apache.** La mayoría de los paneles tienen una lista de extensiones que nginx sirve él
mismo sin pasar por Apache. **Quita de esa lista** `svg`, `svgz`, `html`, `htm`, `xhtml`, `xml`, `xsl` y `xslt`; lo
demás (imágenes, `css`, `js`, fuentes, comprimidos) puede quedarse. Así esos archivos los sirve Apache, con el
`.htaccess`. El coste: cada SVG del sitio, también los del propio framework, pasa por Apache (un estático por Apache
tarda del orden de medio milisegundo; medido en local, SIN VERIFICAR en cada servidor).

- **HestiaCP:** en la edición del dominio web, el campo «Proxy Extensions» (dónde está en el menú cambia con la
  versión del panel). La lista que trae, por ejemplo:

  ```text
  jpg, jpeg, gif, png, ico, svg, css, zip, tgz, gz, rar, bz2, doc, xls, exe, pdf, ppt, txt, odt, ods, odp, odf, tar,
  wav, bmp, rtf, js, mp3, avi, mpeg, flv, html, htm
  ```

  queda así:

  ```text
  jpg, jpeg, gif, png, ico, css, zip, tgz, gz, rar, bz2, doc, xls, exe, pdf, ppt, txt, odt, ods, odp, odf, tar,
  wav, bmp, rtf, js, mp3, avi, mpeg, flv
  ```

- **Otros paneles y nginx a mano:** busca el `location` de estáticos (suele ser
  `location ~* \.(jpg|jpeg|gif|png|svg|css|js|…)$`) y quita de él esas extensiones.

**La alternativa de coste cero**, para quien quiera que nginx lo siga sirviendo todo: dejar la lista como está y poner
en nginx las mismas cabeceras, solo para las subidas (ajusta la ruta a la de tu instalación):

```nginx
location ~* /statics/uploads/.*\.(svg|svgz)$ {
    add_header Content-Security-Policy "sandbox" always;
    add_header X-Content-Type-Options "nosniff" always;
    try_files $uri @fallback;
}
location ~* /statics/uploads/.*\.(html|htm|xhtml|xml|xsl|xslt)$ {
    add_header Content-Security-Policy "sandbox" always;
    add_header Content-Disposition "attachment" always;
    add_header X-Content-Type-Options "nosniff" always;
    try_files $uri @fallback;
}
```

- **El `try_files $uri @fallback` es imprescindible.** Un archivo privado se pide por su nombre público
  (`…/foo.svg`), pero en disco es `foo.svg.protected`: nginx no lo encuentra, y sin `try_files` respondería 404 en vez
  de pasárselo a Apache y a PHP, que comprueban el acceso y lo sirven ya con las cabeceras. Todo SVG, HTML o XML
  privado dejaría de verse. `@fallback` es el nombre que usa HestiaCP para su `location` que pasa a Apache; con nginx a
  mano, pon el tuyo (el que pase a Apache o a PHP). SIN VERIFICAR el nombre en cada versión de HestiaCP.
- **Va antes del `location` de estáticos**, porque nginx usa el primer `location` de expresión regular que casa.
- **Un `add_header` dentro de un `location` anula los `add_header` del nivel `server`** para ese `location`: si tu
  servidor pone ahí otras cabeceras de seguridad, repítelas en estos dos bloques.
- Va en la configuración propia del dominio (en HestiaCP, una plantilla o un archivo `nginx.conf_*` del dominio: SIN
  VERIFICAR la ruta exacta en cada versión del panel).
- **Sin probar en un nginx real**: en esta máquina no hay nginx. Si eliges esta forma, pide un archivo SVG público y
  uno privado y mira las cabeceras antes de darla por buena.

Los archivos subidos desde el editor enriquecido (FileManager/elFinder) no se pueden renombrar ni
borrar desde el panel si llevan el sufijo.
