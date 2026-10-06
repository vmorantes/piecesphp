# Identidad, SEO y scripts inyectados

Qué pone el framework en la cabecera de cada página para buscadores y redes sociales, cómo se cambia por página, y
dónde se añaden scripts de terceros.

## Lo que sale solo en toda página pública

Los layouts públicos (`view/layout/header.php` y `view/webflow/layout/header.php`) imprimen tres bloques:

- `MetaTags::getMetaTagsGeneric()`: `<title>`, `author`, `description`, `keywords`, `theme-color`, la **canónica** y
  las **alternativas por idioma** (`hreflang`, más `x-default`).
- `MetaTags::getMetaTagsOpenGraph()`: Open Graph (`og:locale` del idioma de la página, la imagen con sus dimensiones
  y su texto alternativo) y las etiquetas de X/Twitter.
- `StructuredData::get()`: un bloque JSON-LD con `WebSite` y `Organization`.

Los valores por omisión salen de **Configuración → Apariencia → Identidad y SEO**: título del sitio, propietario,
descripción, palabras clave, imagen para compartir, título y descripción para compartir, y la cuenta de X.

**El panel, las pantallas de token y las de acceso llevan `noindex, nofollow`**, y no emiten canónica ni alternativas.

## Cambiarlo en una página

Desde el controlador, antes de pintar la vista:

```php
use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Core\Utilities\Helpers\StructuredData;

set_title($event->name);                          // <title> y, por omisión, og:title
MetaTags::setDescription($event->summary);        // description, og:description y twitter:description
MetaTags::setImage(baseurl($event->imagePath));   // og:image; las dimensiones se leen si el archivo es local
MetaTags::setType('article');                     // og:type

StructuredData::add([
    '@type' => 'Event',
    'name' => $event->name,
    'startDate' => $event->startDate->format(DATE_ATOM),
    'location' => ['@type' => 'Place', 'name' => $event->placeName],
]);
```

- **A `MetaTags` se le pasa el texto tal cual**: escapa todo lo que escribe. Si lo escapas antes, saldrá dos veces.
- **Marca en JSON-LD solo lo que la página enseña.** Es la regla de Google; una publicación, por ejemplo, no declara
  fecha de modificación porque no la muestra.
- **`MetaTags::setShareTitle()`** fija el título de la tarjeta al compartir sin tocar el `<title>`. La portada del
  framework lo usa con «Título para compartir»; si tu clon tiene su propia portada, llámalo desde su controlador.
- **`MetaTags::setURL()`** fija la canónica. Por omisión es la URL actual sin consulta; con el idioma en la consulta
  (`lang_by_cookie`, lo de fábrica), con `?i18n=<idioma>`. Una página cuyo contenido dependa de otra consulta tiene
  que fijar la suya.
- **Alternativas por idioma:** si tu página no existe en todos los idiomas, deja las suyas en
  `Config::set_config('alternatives_url', …)`, como hace la publicación, y no se anunciará un idioma en que no existe.
- **`MetaTags::setRobots('noindex, nofollow')`** para una página pública que no deba aparecer en buscadores.

## Scripts de terceros (analítica, chat, píxeles)

**Configuración → Integraciones → Scripts**, solo para el usuario principal. Cada script dice su **zona** (panel,
sitio público o ambos) y su **punto** (cabecera, principio del cuerpo o final del cuerpo), y se puede desactivar.

- **Solo los cinco layouts los imprimen.** La pantalla de acceso, las de problemas de acceso y las páginas de error
  **no** reciben scripts: en el acceso, un script de terceros leería la contraseña.
- **En un layout propio**, `ExtraScripts::getScripts()` sigue dando lo público de la cabecera. Para los otros puntos:

```php
<?= \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::getScriptsFor('public', 'body_start'); ?>  <!-- tras <body> -->
<?= \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::getScriptsFor('public', 'body_end'); ?>    <!-- antes de </body> -->
```

  En un layout del panel, con `'panel'`.
- **Si vienes de una versión anterior**, `bin/cli settings-migrate-extra-scripts` pasa el antiguo campo de SEO a la
  pantalla nueva.

## `robots.txt`, `humans.txt` y `llms.txt`

Los sirve la aplicación (rutas `site-files-robots`, `site-files-humans` y `site-files-llms`); **no hay archivos en
`src/`**. Cada uno es una base que pone el framework más lo que escribas en **Configuración → Apariencia → Archivos para
buscadores**, que enseña la base debajo de cada campo.

- **La base se mejora en el framework**, no en el panel: `SiteFilesController::robotsBase()`, `humansBase()` y
  `llmsBase()`.
- **Solo cuentan en la raíz del dominio.** Si tu instalación vive en una subcarpeta, un buscador no los leerá.
- **No vuelvas a crear `src/robots.txt`**: Apache serviría ese archivo antes que la ruta, y tus añadidos del panel
  dejarían de verse.

## El sitemap

`/sitemap.xml` lo sirve la ruta `site-files-sitemap`, calculado y guardado una hora en `app/cache/sitemap.xml`. No hay
botón ni archivo en `src/`. **Cada módulo aporta sus URL** con un proveedor, registrado desde sus rutas:

```php
use PiecesPHP\Core\Sitemap\Sitemap;

Sitemap::registerProvider('mi-modulo', [MiModuloPublicController::class, 'sitemapItems']);
```

`sitemapItems()` devuelve `SitemapItem[]`. **Cada URL tiene que ser la canónica de su página**:
`MetaTags::canonicalFor($url, $lang)`, una por idioma en que la página exista. Mira
`PublicationsPublicController::sitemapItems()` como ejemplo completo. Un nombre de proveedor repetido falla al
registrarse; uno que lanza se registra en el log y el sitemap sale con los demás.

