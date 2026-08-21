# 01 — Visión general

## Qué es

**PiecesPHP** es un framework/plataforma PHP propio (autor: Vicsen Morantes,
`sir.vamb@gmail.com`) para construir aplicaciones web administrativas multi-idioma
sobre stack LAMP. No es una librería genérica: es *a la vez* el framework y una
aplicación base con un conjunto grande de módulos de negocio ya construidos
(usuarios, publicaciones, noticias, documentos, convocatorias, organizaciones,
formularios, reportes, GeoJSON, newsletter, etc.).

La forma correcta de pensarlo: **un monolito modular MVC con un núcleo propio
montado encima de Slim 4**.

- Repo público de referencia: <https://github.com/vmorantes/piecesphp>
- DeepWiki: <https://deepwiki.com/vmorantes/piecesphp>
- Versión en código: `APP_VERSION = v7.0.6` (`src/app/core/bootstrap.php`),
  fecha `2026-04-05`.

## Stack

| Capa | Tecnología |
| :-- | :-- |
| Lenguaje | PHP `>=8.4.1 <8.6` |
| HTTP / routing | Slim 4 (`slim/slim`, `slim/psr7`) + capa propia `PiecesPHP\Core\Route*` |
| DI | `PiecesPHP\Core\Routing\DependenciesInjector` (PSR-11) |
| Base de datos | MySQL/MariaDB vía PDO; ORM propio (`piecesphp/database`) |
| Vistas | PHP plano (`require` + `extract`) vía `BaseController::render()` |
| Frontend | jQuery + Fomantic/Semantic UI, DataTables, SASS, TypeScript, Gulp 5 |
| Build | Gulp (`src/gulpfile.js`), `npm`/`pnpm` |
| Servidor | Apache (`src/.htaccess` hace el rewrite y las cabeceras de seguridad) |

### Paquetes propios (Composer, `piecesphp/*`)

`piecesphp/database` (ORM/ActiveRecord/EntityMapper), `piecesphp/datastructures`,
`piecesphp/geojson`, `piecesphp/html`. Viven en `src/vendor/` — **no** se editan
desde este repo.

### Dependencias externas relevantes

`mpdf/mpdf` (PDF), `phpoffice/phpspreadsheet` (Excel), `phpmailer/phpmailer`,
`scssphp/scssphp` (compilar SASS en runtime), `studio-42/elfinder` (gestor de
archivos), `spatie/url`, `pragmarx/google2fa` (OTP/2FA),
`microsoft/azure-storage-blob`, `openai-php/client`, `hubspot/api-client`,
`aminyazdanpanah/php-ffmpeg-video-streaming`, `guzzlehttp/guzzle`,
`react/event-loop` (acciones CLI reactivas).

## Requisitos de entorno

- **PHP 8.4.1 – 8.5** con: `openssl, pcre, hash, session, json, pdo, pdo_mysql, mysqli,
  pdo_sqlite, sqlite3, xml, xsl, xmlwriter, xmlreader, ctype, mbstring, fileinfo,
  gd, zip, curl`.
    - El `.1` del piso lo impone Symfony 8.1, que exige `>=8.4.1`.
    - **Ubuntu 24.04 LTS trae 8.3 por defecto**, así que hace falta el repositorio de
      ondrej. Ver `general.md`.
- Composer, Node 22.x LTS (recomendado 22.12.0, vía fnm), npm, Gulp CLI, TypeScript.
- Apache con `rewrite`, `headers`, `ssl` habilitados.
- Ver `source-docs/project/docs/piecesphp/content/general.md` para el despliegue
  completo paso a paso, y `.../permissions.md` para permisos de archivos
  (usar SetGID `2775`/`664` en directorios escribibles, **nunca 777**).

## Ramas Git

| Rama | Uso |
| :-- | :-- |
| `dev` | Rama de integración / trabajo diario |
| `limpieza-modulos` | Rama de trabajo actual (limpieza y depuración de módulos) |
| `master` | Se sincroniza desde `dev` |
| `last-stable` | Versión estable publicada (la que se descarga para desplegar) |
| `modificacion-docs` | Trabajo sobre documentación |
| `redesign` | Rediseño de UI |
| ~~`updagre-to-php84`~~ | Marcador vacío, 0 commits por delante de `dev`. Retirar |
| `upgrade-to-php85` | Migración al rango 8.4.1–8.5. **Ejecutada** el 2026-08-20, mergeada a `dev` |

Flujo habitual (documentado en `IGNORE.md`): se trabaja en `dev` y se mergea hacia
las ramas temáticas (`git checkout <rama> && git merge dev`), y finalmente
`master` y `last-stable`. Hay tres remotos (`origin`, `origin2`, `origin3`).

## Documentación existente en el repo

- `source-docs/project/docs/` — documentación MkDocs (fuente en Markdown). La
  sección `piecesphp/` es la más útil: `structure.md`, `routing.md`, `mappers.md`,
  `permissions.md`, `terminal.md`, `gulp.md`, `general.md` y `new-features/`
  (cronjobs, colas, eventos, http-client, freeze-request, protected-files,
  database-exporter, unit-testing).
- `CHANGELOG.md` — historial detallado por versión. **Es la mejor fuente para
  saber qué cambió recientemente.**
- `files/API/` — documentación y colección Postman de la API.
- `files/dev/tests.md` — pruebas unitarias/de desarrollo.
- `TODO.md` / `IGNORE.md` — notas del autor (pendientes, snippets de comandos).

## Estado y trabajo en curso (a la fecha de este documento)

De `TODO.md` e `IGNORE.md`:

- Pendiente: implementación de PayU; archivo de opciones JSON para el front;
  rehacer módulos de imágenes, noticias internas y temporizador; módulo de encuestas.
- Módulos marcados como "faltantes por renovar": noticias internas, registro
  fotográfico, formularios, personas, documentos, banner, mensajes, últimos
  movimientos, gestor de archivos.
- Se eliminaron los módulos de chat interno y presentaciones de capacitación.
- `PHPStanResult.txt` / `PHPStanResult.Summary.txt` contienen el análisis estático
  más reciente (se generan con `bin/phpstan`).

> `IGNORE.md` es un bloc de notas local del autor: está en `.gitignore` (línea 19),
> **no se versiona ni se despliega**. Contiene comandos de uso frecuente y
> credenciales personales. Sirve como fuente de contexto sobre el trabajo en curso,
> pero nada de su contenido debe copiarse a archivos versionados.
