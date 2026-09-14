# Archivos protegidos

PiecesPHP puede restringir quién descarga los archivos que suben los módulos. Una carpeta
protegida deja de servirla Apache: cada petición pasa por PHP y por un *validador* que decide si
el archivo se entrega (200) o no (403).

**Toda carpeta de subidas tiene que estar protegida o declarada pública.** `bin/cli
verify-integrity` (comprobación 29) falla si un módulo declara un `UPLOAD_DIR` que no está en
ninguno de los dos sitios.

---

## Cómo funciona

- `ProtectFileMiddleware::protect($directorio, $validador)`:
  - escribe en la carpeta un `.htaccess` que manda las peticiones a `index.php`;
  - registra el validador.
  Si la carpeta no existe, la crea. Si no puede crearla, lanza una excepción: una carpeta que no
  se puede proteger no se deja servible en silencio.
- `ServerStatics` no delega a Apache un archivo protegido, llama al validador y responde 403 si
  devuelve `false`.
- **Sin el `.htaccess`, no hay protección**: `src/.htaccess` hace que Apache sirva
  directamente todo archivo que exista. Por eso el registro se hace en cada arranque, en
  `src/app/config/final-configurations-includes/protected-files.php`, que también carga
  `bin/cli`.
- La comparación exige el separador: proteger `…/publications` no protege `…/publications-x`.
- El validador recibe `(Request $request, string $filePath)` y devuelve `bool`. La sesión llega
  por la cabecera `JWTAuth` o por la cookie del mismo nombre, así que un `<img src>` del panel
  también la lleva.

---

## Los tres casos, con el módulo de referencia

### 1. Privado: solo con sesión

Lo que únicamente muestra la zona de administración (documentos, organizaciones, categorías de
noticias):

```php
use Documents\Controllers\DocumentsController;
use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Routing\RequestRoute as Request;

$uploadsDir = get_config('upload_dir');

ProtectFileMiddleware::protect(
    append_to_path_system($uploadsDir, DocumentsController::UPLOAD_DIR),
    function (Request $request, string $filePath) {
        return SessionToken::isActiveSession(SessionToken::getJWTReceived());
    }
);
```

### 2. Mixto: depende del registro al que pertenece el archivo (el arquetipo)

**Publications** es la referencia. Sus archivos viven en `publications/<folder>/…` (los
adjuntos, en `<folder>/attachments/…`), y `folder` es una columna de la publicación:

- con sesión → se sirve;
- sin sesión → se busca la publicación por su `folder` y se sirve solo si
  `PublicationMapper::isVisibleToPublic()` lo permite. Es el mismo criterio que su vista
  pública: activa y en fecha;
- carpeta vacía, ruta fuera del directorio o publicación inexistente → **no se sirve**. Falla
  cerrado.

La lógica vive en `PublicationsController::uploadedFileValidator()` y en
`publicFileIsServable()`, que se puede probar sin base de datos. En `protected-files.php` solo
queda el registro:

```php
ProtectFileMiddleware::protect(
    append_to_path_system($uploadsDir, PublicationsController::UPLOAD_DIR),
    [PublicationsController::class, 'uploadedFileValidator']
);
```

Para un módulo nuevo que se clona de Publications: cambia el mapper y el criterio de
visibilidad, y conserva la forma. Primero la sesión, luego el registro dueño del archivo, y
fallar cerrado.

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
3. Ejecuta `bin/cli verify-integrity`: la comprobación 29 dice cuántas carpetas hay protegidas,
   públicas y sin archivos.
