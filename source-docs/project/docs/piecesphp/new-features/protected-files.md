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
  `PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware`, siguen funcionando con
  `class_alias`.
- El registro de las carpetas se hace en cada arranque, en
  `src/app/config/final-configurations-includes/protected-files.php`, que también carga `bin/cli`.
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
  - con sesión se sirven por PHP;
  - sin sesión, el validador (`PublicationsController::uploadedFileValidator()` y
    `publicFileIsServable()`) vuelve a comprobar la visibilidad y falla cerrado.
- Las carpetas sin publicación que las nombre son privadas.

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

- **Apache**, con o sin nginx delante (HestiaCP): no hace falta nada más.
- **nginx sin Apache:** añade una regla que niegue `\.protected$` y que pase a PHP lo que no
  existe.

Los archivos subidos desde el editor enriquecido (FileManager/elFinder) no se pueden renombrar ni
borrar desde el panel si llevan el sufijo.
