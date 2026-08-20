# 12 — Convenciones y reglas de trabajo

## Idioma: la regla de oro

| Qué | Idioma |
| :-- | :-- |
| Clases, métodos, variables, constantes, namespaces | **Inglés** |
| Tablas y columnas de base de datos | **Inglés** |
| Nombres de rutas y permisos | **Inglés** (kebab-case) |
| Textos de UI, mensajes, validaciones | **Español**, envueltos en `__($grupo, 'Texto')` |
| Comentarios y docblocks | **Español** (es lo que hay en todo el código) |
| Mensajes de commit | **Español** |

## Nomenclatura

| Elemento | Convención | Ejemplo |
| :-- | :-- | :-- |
| Módulo (carpeta y namespace) | `PascalCase` | `News`, `ApplicationCalls` |
| Clase de rutas | `<Modulo>Routes` | `NewsRoutes` |
| Clase de idiomas | `<Modulo>Lang` | `NewsLang` |
| Controlador | `<Entidad>Controller` | `NewsCategoryController` |
| Mapper | `<Entidad>Mapper` | `NewsReadedMapper` |
| Modelo de sistema | `<Nombre>Model` | `UsersModel` |
| Grupo de idioma de módulo | `kebab-case` | `news-lang` |
| Nombre base de ruta | `kebab-case` | `news-admin` |
| Sufijo de ruta | `kebab-case` | `-forms-edit`, `-actions-delete` |
| Propiedades de BD | `camelCase` | `newsTitle`, `createdAt` |
| Archivos JS de vista | `kebab-case` | `add-form.js`, `delete-config.js` |

> `BaseController` asocia automáticamente `XController` ↔ `App\Model\XModel`.
> En los módulos esto se sobreescribe asignando `$this->model` en el constructor.

## Reglas duras (romperlas rompe el framework)

1. **Rutas**: siempre `PiecesPHP\Core\Route` / `RouteGroup`. Nunca `$app->get(...)`
   directo. El sistema de permisos depende del nombre de ruta.
2. **URLs**: siempre `Controller::routeName()` o `get_route()`. Nunca concatenar
   strings de URL.
3. **Visibilidad en menús**: siempre `Controller::allowedRoute(...)` o
   `Roles::hasPermissions(...)`.
4. **Todo método de ruta devuelve un `Response`.** Si no, el error se reporta como
   `MissingResponseInController`.
5. **Textos visibles siempre por `__()`** con un grupo de idioma, nunca hardcodeados.
6. **Assets**: `add_global_asset` / `set_custom_assets` / `import_*`, nunca
   `<script src>` a pelo en la vista.
7. **Configuración**: `get_config()` / `set_config()`. Recuerda que la BD
   sobrescribe a `config.php`.
8. **Un módulo nuevo se activa con una constante** en `config/constants.php` y se
   lee como `const ENABLE = X_MODULE;` en su clase `Routes`.
9. **Estáticos de módulo**: SCSS en `Statics/sass/`, nunca editar el `.css`
   generado.
10. **No se edita `src/vendor/`** ni los paquetes `piecesphp/*`; viven en repos
    aparte.

## Estilo de código

- PHP 8.1+ compatible hasta 8.4 (hay una rama `updagre-to-php84` en curso).
- Docblocks en todas las clases con `@package`, `@author`, `@copyright`, y
  `@property` para las propiedades mágicas de los mappers. **Los mappers dependen
  de `@property` para el autocompletado y para PHPStan.**
- Separadores decorativos usados en todo el código:
  ```php
  //──── GET ───────────────────────────────────────────────────────────────
  //========================================================================
  ```
- `.editorconfig`: UTF-8, 4 espacios (tabs en `.js`/`.yaml`/`.neon`),
  `end_of_line = crlf` por defecto y `lf` en `.sh` y en los ejecutables de `bin/`.
- Excepciones propias por módulo en `Exceptions/`: convención `SafeException`
  (error controlado, mostrable al usuario) y `DuplicateException`.

## Calidad

- `bin/phpstan` antes de dar por cerrado un cambio grande; revisar
  `PHPStanResult.Summary.txt`.
- `bin/rector` para refactors automatizados.
- Pruebas: `bin/cli unit-tests:<suite>` (ver `files/dev/tests.md`).
- Al terminar una funcionalidad, **añadir la entrada en `CHANGELOG.md`** con el
  formato existente (encabezado `# X.Y.Z (DD-MM-AAAA)` y viñetas por área en
  negrita).

## Git

- Trabajo diario en `dev`; ramas temáticas (`limpieza-modulos`, `redesign`,
  `updagre-to-php84`, `modificacion-docs`) se sincronizan con
  `git checkout <rama> && git merge dev`.
- `master` y `last-stable` se actualizan desde `dev` para publicar.
- Tres remotos: `origin`, `origin2`, `origin3`. Se hace push de `--all` y `--tags`.
- Mensajes de commit en español, con prefijos tipo `feat:` / `fix:` cuando aplica
  (el historial es mixto; hay commits con mensaje `-`).

## Despliegue

- `permissions-and-property.sh` (raíz) y `src/permissions.sh` ajustan permisos y
  propiedad. Ver `source-docs/project/docs/piecesphp/content/permissions.md`.
- El paquete de actualización se arma con el `zip -r9 ... UPDATE.zip` documentado en
  `IGNORE.md`, o con `bin/cli bundle all=yes zip=yes`.
- Se excluyen del despliegue: `.git`, `node_modules`, lockfiles, `src/vendor`,
  `src/adminer`, `src/statics/{filemanager,uploads}`, `src/app/{logs,cache}`,
  `source-docs`, `README.md`, `CHANGELOG.md`, `TODO.md`, `IGNORE.md`,
  `PHPStanResult.*`, `secure-keys/`, `bin/tools/vendor`.

## Seguridad

- **Nunca hardcodees secretos.** Usa `getKeyFromSecureKeys()` (lee de `secure-keys/`)
  o las configuraciones de BD.
- `app_key` firma tokens y cifrado: cambiarla invalida sesiones.
- Subir `SessionToken::setMinimumDateCreated(...)` en `roles.php` invalida todas las
  sesiones activas — útil tras un incidente.
- Cabeceras de seguridad (CORS, CSP, HSTS) y bloqueo de archivos sensibles están en
  `src/.htaccess`.
- **Nunca `chmod 777`** en producción; usa SetGID `2775`/`664` en los directorios
  escribibles.
- `IGNORE.md` está en `.gitignore`: es un bloc de notas local, no versionado. No
  muevas su contenido (comandos, credenciales personales) a archivos versionados.

## Cuando trabajes en este proyecto

1. **Lee primero `Publications`**: es la referencia canónica del proyecto — el
   módulo más completo y el más mantenido. Cubre zona admin y pública,
   sub-entidad, adjuntos, traducción de campos, caché y aprobaciones.
   `News` sirve como ejemplo mínimo solo-admin, pero está marcado «por renovar»:
   no lo uses como referencia de estilo.
2. Copia su estructura literalmente: nombres, sufijos de ruta, orden de métodos.
3. Si las reglas de negocio del encargo son ambiguas, **pregunta antes de escribir
   código** (regla explícita del skill `full-stack-php-senior` del proyecto).
4. Consulta `CHANGELOG.md` para saber qué cambió recientemente, y `TODO.md` /
   `IGNORE.md` para el trabajo pendiente.
