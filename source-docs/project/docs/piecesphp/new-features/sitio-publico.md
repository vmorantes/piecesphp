# El sitio público por marcas

Un diseño web cualquiera (HTML, CSS y JavaScript de navegador; un export de Webflow sirve) se **adapta una sola vez** y
después se sirve tal cual. La adaptación separa lo que se repite (menú, cabecera, pie) en **partes** y pone **marcas**
`<pcs-…>` donde van datos del sistema: un menú, un enlace entre páginas, los metadatos, un logo, un texto traducible, una
lista de publicaciones, un formulario. Un cambio visual posterior del diseñador se sube y se sirve sin volver a adaptar
nada.

El formato es **uno** y tiene **dos lectores**:

| Lector | Dónde | Qué hace con las marcas |
| :-- | :-- | :-- |
| **Vista previa** | El ordenador del diseñador, con doble clic en `index.html`, sin servidor | Un JavaScript mínimo (`site/.pcs/`) las pinta con datos de ejemplo |
| **PiecesPHP** | El proyecto | Convierte cada marca en su pieza real **una vez**, guarda el resultado en caché y lo sirve |

!!! note
    **Lo que el diseñador escribe nunca se ejecuta como código del servidor.** El sitio lleva HTML, CSS, JavaScript de
    navegador, imágenes y fuentes; lo dinámico va solo con marcas. Ver «Seguridad».

## Activarlo

1. Importa la carpeta del diseñador (ver «La orden de importar»).
2. En `src/app/config/config.php`:

```php
$config['public_site'] = 'mi-sitio';   // el nombre con el que importaste; vacío (de fábrica): nada cambia
```

El valor tiene que ser un nombre (minúsculas, números y guiones) y existir `app/view/<nombre>/pcs.json`. Si no, el sitio
**no se sirve**, el framework sigue como estaba y queda **una entrada** en el registro de errores (una por proceso, no una
por petición).

Con el sitio activo:

| Dirección | Qué se sirve |
| :-- | :-- |
| La portada (ruta `public-index`) | `index.html` del sitio, si lo trae; si no, la portada del framework |
| El contacto (ruta `public-contact`) | `contact.html` del sitio, si lo trae; si no, el contacto del framework |
| `/<página>/` para cada otra página | La página del sitio (ruta `public-site-<página>`), registrada antes del comodín de vistas genéricas |
| Una página que el sitio no trae | No tiene ruta propia: la atiende el comodín de vistas genéricas del framework, y si tampoco hay una vista con ese nombre, 404 |

`index` y `contact` conservan su nombre de ruta de siempre: los correos y el panel enlazan a ellas. Las demás páginas
**no pueden usar** una dirección que ya sea del sistema: `admin`, `core`, `statics`, `terminal`, `tokens`, `components`,
`unsubscribe`, `general` y `site` están reservadas, y la importación rechaza también cualquier dirección ya registrada
por otra ruta. Si aun así una colisión llegara al servidor, esa página no se sirve y se anota una vez en el registro.

**Qué se carga del framework con el sitio activo** (solo en las páginas del sitio):

- Los **CSS globales del framework no se cargan**: el sitio trae los suyos.
- Los **JS globales del framework no se cargan**, salvo que la página lleve un `<pcs-form>`: entonces sí, más el JavaScript
  propio de esa acción (el del contacto, por ejemplo).
- Los CSS y JS de `assets/` que el diseñador enlaza entran por `set_custom_assets()`, **en su orden**. El primero ocupa el
  lugar del enlace original; si la página no tenía ninguno, los estáticos se añaden al final del `<head>` y del `<body>`.
- Se mantienen el atributo `lang` de `<html>` (el idioma de la aplicación), las etiquetas `<meta>` que lee el JavaScript
  del framework, la regla que alterna las imágenes de marca claras y oscuras y los scripts extra del panel
  (zona pública).
- Las referencias a la vista previa (`.pcs/`, `data/`, `parts/parts.js`) se quitan: no existen en el proyecto.

El título y la descripción del `<head>` se pasan por `__()` con el grupo `public-site-<sitio>`, usando el texto literal
como clave: se pueden traducir como cualquier otro texto.

## La carpeta del diseñador

Lo que recibe y devuelve el diseñador es una carpeta con esta forma:

```text
paquete/
├── site/                 el sitio, como cualquier web
│   ├── index.html        la portada (se abre con doble clic)
│   ├── contact.html      una .html por página, sueltas en la raíz de site/
│   ├── parts/            las piezas repetidas: menu.html, footer.html…
│   ├── assets/           su CSS, JS, imágenes y fuentes, tal cual
│   ├── data/             datos de ejemplo de la vista previa (los genera el proyecto)
│   └── .pcs/             el lector de la vista previa (oculto; no se toca)
├── CHECK.html            el comprobador del diseñador
├── GUIDE.html            la guía del diseñador
├── pcs.json              el manifiesto: nombre, versión del formato y huellas
├── DESIGN.md             las reglas del diseño, del diseñador (si las trae)
├── PROJECT.md            las reglas del proyecto y lo pendiente (solo de ida; no se toca)
└── AGENTS.md, CLAUDE.md, .agents/, .claude/, .cursor/   instrucciones para su asistente
```

Los nombres de sitio, páginas, partes y recursos son **minúsculas, números y guiones** (`sobre-nosotros`, `menu-principal`).

**Lo que entra en el proyecto** al importar:

| Origen en el paquete | Destino en el proyecto |
| :-- | :-- |
| `site/<página>.html` | `app/view/<sitio>/pages/<página>.html` |
| `site/parts/<parte>.html` | `app/view/<sitio>/parts/<parte>.html` |
| `site/assets/…` | `statics/<sitio>/…` (misma estructura) |
| `pcs.json` | Se lee para comprobar; en el proyecto se escribe uno nuevo en `app/view/<sitio>/pcs.json` |
| `DESIGN.md` (raíz del paquete, si existe) | `app/view/<sitio>/DESIGN.md`, copiado byte a byte: son las notas del diseñador |

**Lo que no entra**: `CHECK.html`, `GUIDE.html`, `PROJECT.md` (las reglas del proyecto, que solo viajan hacia el
diseñador), el andamiaje del asistente (`AGENTS.md`, `CLAUDE.md`, `.agents/`,
`.claude/`, `.cursor/`), `site/.pcs/`, `site/data/` (la exportación la regenera de los recursos registrados) y
`site/parts/parts.js` (el paquete de partes y la lista de recursos que lee la vista previa con doble clic). Un archivo suelto en la raíz de `site/` que no sea `.html`, o una carpeta que no sea `parts/`,
`assets/` ni `data/`, no se importa y la orden lo avisa.

Las URLs de `assets/` (`assets/x`, `./assets/x`, `../assets/x`, también dentro de `style` y de `url(…)` de un `<style>`)
pasan a `statics/<sitio>/x`, y un enlace a `otra.html` (o `otra.html#ancla`) pasa a la dirección de esa página, en el idioma
actual.

La página lleva el `<base>` del framework, como el resto del sitio: un `href="#x"` del diseñador apunta a esa misma
página (`#x`), no a la portada. Las URL que vienen de los datos de una lista admiten `http(s)://`, `mailto:`, `tel:` y las
relativas; cualquier otra sale vacía.

El recorrido completo, desde que llega el diseño hasta que el equipo de diseño lo devuelve: [Adaptar una plantilla, de
principio a fin](adaptar-plantillas.md).

## Las siete marcas

Las reglas viven en un solo archivo, `src/app/classes/PiecesPHP/PublicSite/kit/pcs/rules.json`, que leen por igual la
importación, el compilador y el comprobador del diseñador. Las que pintan un elemento admiten además los atributos
globales `class`, `id`, `style`, `title`, `role`, `aria-*` y `data-*`, **salvo `<pcs-image>`, que en el proyecto solo
pinta `class`, `alt` y sus atributos propios** (la vista previa copia los demás, pero el sitio real no).

Cada página lleva `<script src=".pcs/pcs.js"></script>` en su `<head>`, **sin `async`, `defer` ni `type` y antes de
cualquier otro `<script>`**: así, en la vista previa, las partes y las marcas ya están pintadas cuando corre el
JavaScript del diseñador (también en `DOMContentLoaded` o en el `ready` de jQuery). El comprobador avisa si no está así.
En el proyecto ese script no se carga.

| Marca | Atributos | Contenido | Qué pinta en el proyecto |
| :-- | :-- | :-- | :-- |
| `<pcs-part name="menu">` | `name` (obligatorio) | vacío | La parte `parts/menu.html`, ya compilada |
| `<pcs-link page="contact">…</pcs-link>` | `page` (obligatorio); `anchor` (un fragmento, sin `#`); `current-class` (una o más clases) | en línea | Un `<a>` a la URL de esa página en el idioma actual (más `#anchor` si lo lleva), con el contenido dentro y el resto de atributos. Si apunta a la página que se pinta, añade `aria-current="page"` y las clases de `current-class` |
| `<pcs-meta>` | `structured-data="off"` (opcional) | vacío | Título, descripción, Open Graph, datos estructurados (salvo con `structured-data="off"`, para el diseño que trae su propio JSON-LD) y canónica del framework (`MetaTags`). Se quitan los que el diseñador tuviera, para que no se repitan |
| `<pcs-image brand="logo">` | `brand` (obligatorio): `logo`, `partners`, `partners-vertical`, `loader-animation`, `loader-logo`, `loader-screen`, `loader-screen-mobile`; `alt`, `class`; `width` y `height` (enteros de 1 a 10000), `loading` (`lazy`, `eager`), `decoding` (`async`, `sync`, `auto`), `sizes` | vacío | La imagen de marca configurada en el panel, con su versión clara y oscura (`SettingsController::brandImageTag()`) |
| `<pcs-text key="…">texto</pcs-text>` | `key` (obligatorio) | texto | `__()` con el grupo `public-site-<sitio>` y esa clave; si no hay traducción, el texto escrito dentro. Siempre escapado |
| `<pcs-list resource="…" count="n">` | `resource` (obligatorio); `count`, de 1 a 100 (sin él, 10) | una `<template>` con `{{campo}}` | La plantilla repetida con las filas reales del recurso, escapadas |
| `<pcs-form action="contact">…</pcs-form>` | `action` (obligatorio) | cualquiera | Un `<form method="POST">` hacia la acción registrada, con su protección y un campo oculto `from` con la página actual. En `contact`, los campos que el formulario envía y la acción no espera (un teléfono, por ejemplo) llegan al correo como «Etiqueta: valor», con un tope de campos y de longitud |

Ejemplos mínimos (los de `kit/example/site/` del framework, que se ven funcionando importándolos):

```html
<!-- pcs-part: parts/menu.html entra aquí -->
<pcs-part name="menu"></pcs-part>

<!-- pcs-link: enlace a la página news; class pasa al <a> -->
<pcs-link page="news" class="button">Ver todas las noticias</pcs-link>

<!-- pcs-meta: en el <head>, una vez por página -->
<pcs-meta></pcs-meta>

<!-- pcs-image: el logo de «Identidad» -->
<pcs-image brand="logo" alt="Inicio" class="logo"></pcs-image>

<!-- pcs-text: traducible; el texto de dentro es el de reserva -->
<h1><pcs-text key="home-title">Bienvenido a nuestro sitio</pcs-text></h1>

<!-- pcs-list: tres publicaciones; solo vale {{campo}} de ese recurso -->
<pcs-list resource="publications" count="3">
    <template>
        <article class="card">
            <img src="{{image}}" alt="{{title}}">
            <h3><a href="{{url}}">{{title}}</a></h3>
            <p class="date">{{date}}</p>
            <p>{{excerpt}}</p>
        </article>
    </template>
</pcs-list>

<!-- pcs-form: los name de los campos no cambian -->
<pcs-form action="contact" class="form">
    <label>Tu nombre <input type="text" name="name" required></label>
    <label>Tu correo <input type="email" name="email" required></label>
    <label>Mensaje <textarea name="message" rows="4"></textarea></label>
    <button type="submit">Enviar</button>
</pcs-form>
```

**Qué hace una marca mal puesta.** En la importación y en `CHECK.html` es un error con la línea y qué hacer. Si de todos
modos llegara al servidor (una marca cuyo registro cambió después), se pinta **vacía**, se anota en el registro de errores
y la página sigue: una marca rota no tumba el sitio.

**Lo que finge funcionar, las direcciones ajenas y los bloques copiados** (ADR 0087). `CHECK.html` avisa, además, de:

- un enlace que no lleva a ninguna parte (`href="#"`, `href=""`, `javascript:` o sin `href`, salvo un ancla con `id` o
  `name`), una imagen sin `alt` y un formulario que no envía (sin `action`, o con `""` o `#`, fuera de `<pcs-form>`);
- un `<div>` o un `<span>` que finge ser un botón (con `role="button"`, si no lleva `tabindex`; sin `role="button"`, si
  tiene una clase `btn`, `button` o `w-button`) o que parece un campo sin serlo (una clase `search`, `field` o `input`, o
  que empiece por `search-`, `field-` o `input-`, sin `<input>`, `<select>` ni `<textarea>` dentro);
- un recurso (imagen, hoja de estilo, script, fuente, vídeo) que se carga desde un dominio que no está en la lista de
  servicios conocidos (`knownHosts` en `src/app/classes/PiecesPHP/PublicSite/kit/pcs/rules.json`: fuentes, vídeo y
  mapas); los enlaces a otras webs no avisan. Se miran los atributos de dirección, los `style` y `<style>`, el JavaScript
  en línea y los `.css` y `.js` de `site/assets/`, sin sus comentarios. **Es un error** si el final de la ruta de la
  dirección coincide con un archivo del paquete, desde `site/` o `site/assets/` (`…/img/logo.png` ↔ `assets/img/logo.png`):
  es una ruta local disfrazada, y en producción el sitio dependería del servidor del diseñador; el aviso dice la ruta
  local que hay que poner. Si solo coincide el nombre del archivo, es un aviso;
- un bloque de 300 caracteres o más (sin contar los espacios) que aparece igual en tres páginas o más fuera de
  `<pcs-part>`: parece una parte.

La importación avisa de los recursos ajenos, de las rutas disfrazadas y de los bloques copiados con los mismos criterios,
sin parar (lo que finge solo lo mira `CHECK.html`): el comprobador del diseñador es donde se corrigen.

**Lo que el framework trae registrado de fábrica:**

- Recurso `publications`, con los campos `title`, `excerpt`, `url`, `image`, `date` y `category`: las publicaciones que ve
  el público, con el mismo filtrado de siempre (estado, fechas, aprobación, idioma). Existe solo si el módulo
  Publications está encendido.
- Acción de formulario `contact`: el formulario de contacto con su reCAPTCHA. Existe solo si la zona pública tiene
  habilitados los formularios de contacto (`PUBLIC_AREA_CONTACT_FORMS`).

Usar un recurso o una acción que no existe es un error de importación que lista los que sí hay.

### Dentro de una `<template>`

Un `{{campo}}` se resuelve contra los campos que declara el recurso (letra inicial y luego letras, números o `_`). En un
atributo de URL (`href`, `src`, `action`, `formaction`, `poster`, `srcset`, `xlink:href`, `data-src`, `data-srcset`) el
valor pasa además por el filtro de direcciones, sea cual sea el tipo del campo. **No se admite un `{{campo}}`** en un
atributo `on*`, en `style`, ni dentro de `<script>` o `<style>`: ahí el escapado de HTML no protege, y la marca se rechaza.

## La orden de importar

```bash
bin/cli public-site-import folder=<ruta del paquete> site=<nombre> [check=yes]
```

| Parámetro | Qué es |
| :-- | :-- |
| `folder=` | La carpeta del paquete (la que tiene `site/` y `pcs.json`) |
| `site=` | El nombre del sitio: `app/view/<nombre>/` y `statics/<nombre>/`. Con la forma de un nombre y distinto de `backups` |
| `check=yes` | Solo comprueba; no coloca nada |

**Comprueba todo** antes de tocar nada: que `site/` exista y tenga páginas, que `pcs.json` esté y su versión de formato no
sea posterior a la del proyecto, cada marca (atributos, partes, páginas, recursos, acciones), que ninguna página tome una
dirección del sistema, que las partes no se incluyan en círculo ni una página acabe pintando más de 200 partes, que no haya
enlaces simbólicos ni archivos que un servidor ejecutaría, y que el destino (`app/view/<sitio>/`, `statics/<sitio>/`) no
sea una carpeta ajena que ya existía. Cada problema sale con su archivo, su línea y **qué hacer**. Sin portada
(`site/index.html`) es un aviso: la portada del proyecto seguirá siendo la del framework.

**Atomicidad.** Si hay un solo error, **no se toca nada** y la orden termina con error. Si no, todo se prepara en carpetas
temporales hermanas de las de destino y se cambian de nombre al final; si eso falla a medias, se deshace en orden inverso
y el sitio anterior sigue sirviendo. La caché compilada del sitio se borra al terminar.

**Respaldo y vuelta atrás.** Lo que había antes se mueve a
`app/cache/public-site/backups/<sitio>-<AAAAMMDDHHMMSS>-<código>/`, con dos carpetas: `view/` y `statics/`. La orden
imprime esa ruta. **No hay una orden para restaurar**: para volver, se apartan `app/view/<sitio>/` y `statics/<sitio>/`
actuales y se mueven allí `view/` y `statics/` del respaldo. La caché no estorba: cada vista compilada lleva en su huella
el HTML del que salió.

Al terminar, si `$config['public_site']` no es ese sitio, la orden recuerda la línea que falta en `config.php`.

## La orden de exportar

```bash
bin/cli public-site-export (site=<nombre> | from=<carpeta> [site=<nombre>]) destination=<ruta> [project-file=<ruta>] [zip=yes]
```

Arma la carpeta del diseñador, lista para que siga trabajando: sus páginas, partes y `assets/`; los
`data/<recurso>.js` de **todos los recursos registrados** (con los campos y filas de ejemplo de cada contrato); el lector
de la vista previa; `CHECK.html`, `GUIDE.html`, `pcs.json` con las huellas, el andamiaje de su asistente, `DESIGN.md` si
el origen lo trae y `PROJECT.md` (las reglas del proyecto).

**De dónde sale el sitio** (uno de estos tres; `from=` y un sitio importado con el mismo nombre no se combinan: la orden
falla):

1. `from=<carpeta>`: una carpeta con `site/` y `pcs.json`, relativa a la raíz del repositorio (por ejemplo
   `files/design/adaptado`) o absoluta. Pasa las mismas comprobaciones que una importación, y no hace falta que el sitio
   esté importado. El nombre sale de `site=` o, si falta, del `name` de su `pcs.json`.
2. `site=<nombre>` de un sitio importado: sale del proyecto.
3. `site=<nombre>` que no está importado y sin `from=`: sale del sitio de ejemplo del kit, con ese nombre (la orden lo
   dice).

**Adónde va:**

- `destination=` es una carpeta **nueva**: no puede existir y su carpeta padre sí. No se sobrescribe nada. Una ruta
  relativa se resuelve contra la raíz del repositorio.
- **Fuera del repositorio**: el paquete entero para el diseñador. Lleva `PROJECT.md`, que sale de `project-file=<ruta>` (un
  archivo dentro del repositorio; por omisión, `files/design/PROJECT.md`). **La orden falla sin escribir nada** si ese
  archivo no existe, es un enlace, está fuera del repositorio o no tiene una línea exactamente igual a
  `## Pendientes conocidos`. El paquete deja `PROJECT.md` protegido: los permisos que trae el paquete para la herramienta
  de agentes que los admite le niegan editarlo, y `CHECK.html` da un error si cambia. `DESIGN.md`, en cambio, es del diseñador y se edita.
- **Dentro de `files/design/`**: solo el sitio (`site/` con `.pcs/`, `pcs.json` y `DESIGN.md` si el origen lo trae), sin el kit ni
  `CHECK.html`, `GUIDE.html` o `PROJECT.md`. Con `zip=yes` ahí, da error.
- En cualquier otro sitio del repositorio, la orden no exporta.
- `zip=yes` deja además `<sitio>-<AAAA-MM-DD>.zip` junto a la carpeta de destino (necesita la extensión `zip` de PHP; si el
  `.zip` ya existe, falla).
- **Falla, y no deja nada**, si falta el lector de la vista previa del kit (en cualquier destino) o, al exportar fuera del
  repositorio, otra pieza del kit (`src/app/classes/PiecesPHP/PublicSite/kit/`: comprobador, guía, skills, subagentes,
  reglas de Cursor) o el mapa opcional del kit, `package-map.json`, no tiene la forma esperada o apunta fuera de lo
  permitido.
- **Lista blanca del andamiaje.** El kit solo puede dejar en el paquete, fuera de `site/`: `AGENTS.md`, `CLAUDE.md`,
  `.agents/skills/designer/*`, `.agents/skills/adapt-design/*`, `.claude/skills/designer/*`,
  `.claude/skills/adapt-design/*`, los tres subagentes (`design-explorer`, `design-adapter`, `design-reviewer`) en
  `.claude/agents/`, `.claude/settings.json` y las dos reglas de Cursor (`designer.mdc`, `adapt-design.mdc`). Cualquier otro
  destino en el mapa hace fallar la exportación.
- Antes de dejar el paquete pasa por la comprobación de rastro de `bin/entregar-al-cliente` (necesita `bash` y `python3`):
  si queda algo de la metodología del proyecto, la orden falla y enseña la salida. Sin esa comprobación presente, no se
  exporta. **`DESIGN.md` y `PROJECT.md` van exentos**: lo que escribas en `PROJECT.md` viaja sin revisar, así que no
  nombres en él la metodología ni las herramientas del proyecto.

`PROJECT.md` lleva dos secciones obligatorias, «Censo funcional» y «Pendientes conocidos». Hoy la orden solo comprueba la
segunda; escribe las dos.

Importar y exportar sin cambios de por medio no cambia ningún archivo: los archivos generados se escriben con los mismos
bytes en PHP y en el navegador.

### El kit y el asistente del diseñador

El kit incluye instrucciones para el asistente de programación que use el diseñador: un `AGENTS.md` con las normas del
formato y una tabla de a quién delegar qué, dos skills (adaptar un diseño nuevo al formato; trabajar en el sitio ya
adaptado), tres subagentes (explorar, adaptar, revisar), dos reglas de Cursor y un `.claude/settings.json` con permisos que
niegan tocar lo que no se toca. **No lleva guiones ni hooks**: si la herramienta del diseñador no pudiera arrancarlos, lo
bloquearían. Son texto; nada se ejecuta por sí solo.

## Ampliarlo desde un proyecto

Los tres registros están en `PiecesPHP\PublicSite` y se alimentan desde el `routes()` del módulo del proyecto, que se
ejecuta al arrancar y antes de pintar. Cada uno tiene `register()` (falla si ya existe), `replace()` (falla si no existe) y
`remove()`. Añadir o quitar un nombre cambia la huella de la caché y recompila solo.

```php
use PiecesPHP\PublicSite\FormActionRegistry;
use PiecesPHP\PublicSite\MarkContext;
use PiecesPHP\PublicSite\MarkRegistry;
use PiecesPHP\PublicSite\ResourceContract;
use PiecesPHP\PublicSite\ResourceRegistry;

public static function routes(RouteGroup $group)
{
    // ... tus rutas ...

    // Una marca nueva: <pcs-whatsapp number="573001112233">Escríbenos</pcs-whatsapp>
    MarkRegistry::register('pcs-whatsapp', function (array $attributes, string $content, MarkContext $context): string {
        $digits = preg_replace('/\D/', '', $attributes['number'] ?? '') ?? '';
        return '<a href="https://wa.me/' . escape_html($digits) . '">' . $content . '</a>';
    });

    // Un recurso de lista: <pcs-list resource="team"> … {{name}} {{photo}} {{profile}} …
    ResourceRegistry::register(new ResourceContract('team', [
        'name' => ['type' => ResourceContract::TYPE_TEXT, 'example' => 'Nombre y apellido'],
        'photo' => ['type' => ResourceContract::TYPE_IMAGE, 'example' => '.pcs/example.svg'],
        'profile' => ['type' => ResourceContract::TYPE_URL, 'example' => 'index.html'],
    ], function (int $count, MarkContext $context): array {
        // En un proyecto real: consulta tu mapper y devuelve solo lo que el público puede ver.
        return array_slice([
            ['name' => 'Ana Pérez', 'photo' => 'statics/team/ana.jpg', 'profile' => '/equipo/ana'],
            ['name' => 'Luis Gómez', 'photo' => 'statics/team/luis.jpg', 'profile' => '/equipo/luis'],
        ], 0, $count);
    }));

    // Una acción de formulario: <pcs-form action="newsletter">…</pcs-form>
    FormActionRegistry::register('newsletter', fn (): array => [
        'url' => MiControlador::routeName('add', [], true),   // la ruta que recibe el POST
        'attributes' => ['data-newsletter' => ''],             // atributos fijos del <form>
        'js' => ['statics/js/newsletter.js'],                  // se cargan en las páginas que la usan
    ]);

    return $group;
}
```

### Una marca

`register(string $name, callable $renderer)`. El nombre es `pcs-` seguido de minúsculas, números y guiones. El pintor
recibe:

- `$attributes`: los atributos de la marca, `nombre => valor`, **sin escapar**;
- `$content`: su contenido ya pintado (HTML del diseñador con sus propias marcas resueltas; en `pcs-text`, su texto; en
  `pcs-list`, su `<template>`);
- `$context`: `->site`, `->page`, `->langGroup()` y `->part($nombre)`.

Devuelve **HTML ya escapado**: el motor lo imprime tal cual. Escapa con `escape_html()` todo lo que venga de atributos o de
datos; `$content` ya es HTML y no se escapa de nuevo. Si el pintor lanza una excepción, la marca sale vacía y queda en el
registro. Una marca del proyecto no tiene reglas en `rules.json`: acepta cualquier atributo y cualquier contenido, y
conviene cerrarla siempre (`<pcs-whatsapp …></pcs-whatsapp>`) y no ponerla en el `<head>`. Con `replace()` se cambia lo que
pinta una de las siete de fábrica; con `remove()`, se quita.

### Un recurso de lista

`new ResourceContract($name, $fields, $provider, $examples = [])`:

- `$name`: con forma de nombre (minúsculas, números, guiones).
- `$fields`: `campo => ['type' => …, 'example' => …]`. Los tipos son `text`, `url`, `image`, `date` y `number`
  (`ResourceContract::TYPE_*`). Los de tipo `url` e `image` pasan por el filtro de direcciones; todos se escapan. **Un campo
  que no declaras no existe** para la plantilla.
- `$provider`: `function (int $count, MarkContext $context): array`. Devuelve hasta `$count` filas
  (`campo => valor`). **Filtra ella por permisos y visibilidad**, porque la marca pinta lo que reciba. Se descartan las filas
  que sobran, los campos no declarados y todo valor que no sea escalar; si lanza una excepción, no hay filas y queda en el
  registro.
- `$examples`: las filas de ejemplo para la vista previa; vacío, se hacen tres con los `example` de los campos.

De los campos y los ejemplos sale el `data/<recurso>.js` de la exportación: **un dato nuevo es el único trabajo de
programación que pide un cambio de diseño.** El diseñador recibe los campos al exportar de nuevo.

Los `example` de los datos de ejemplo no fingen: una `url` apunta a una página que existe en la vista previa
(`index.html`) y una `image` a un archivo que viaja con ella (`.pcs/example.svg`, que trae el lector). Los de fábrica de
`publications` son así.

**Un recurso estimado** (adaptar antes de que exista el módulo que da los datos). Si el diseño ya pide una lista y el
módulo todavía no está, se registra el contrato con `ResourceContract::estimated($name, $fields, $examples)`: lleva
sus campos y sus ejemplos, pero su lector **no devuelve filas**. La importación no falla (el recurso existe), la vista
previa se ve con los ejemplos y el sitio servido pinta la lista vacía. Cuando el módulo exista, se sustituye con
`ResourceRegistry::replace()` por un contrato normal. Los tres argumentos son obligatorios; `$examples` son las filas de
ejemplo (con `[]` se hacen tres a partir de los `example` de los campos, como en un contrato normal). Declara **al menos
`count` ejemplos distintos**, para que el «cargar más» del diseño no repita filas.

```php
ResourceRegistry::register(ResourceContract::estimated('team', [
    'name' => ['type' => ResourceContract::TYPE_TEXT, 'example' => 'Nombre y apellido'],
    'photo' => ['type' => ResourceContract::TYPE_IMAGE, 'example' => '.pcs/example.svg'],
    'profile' => ['type' => ResourceContract::TYPE_URL, 'example' => 'index.html'],
], [
    ['name' => 'Ana Pérez', 'photo' => '.pcs/example.svg', 'profile' => 'index.html'],
    ['name' => 'Luis Gómez', 'photo' => '.pcs/example.svg', 'profile' => 'index.html'],
    ['name' => 'Marta Ruiz', 'photo' => '.pcs/example.svg', 'profile' => 'index.html'],
]));
```

`$contract->isEstimated()` dice si un contrato lo es; un contrato con lector propio, no.

**Avisos del sistema.** Mientras haya un sitio público activo (`$config['public_site']`) y algún recurso registrado sea
estimado, Avisos del sistema muestra (gravedad «atención», como aviso flotante) el texto: «El sitio
público usa datos estimados: `<recursos separados por comas>`. En el sitio servido esas listas salen vacías hasta que el
módulo registre su lector real.» Lo ven los usuarios root y administrador general; ocultarlo es de root, y cerrar el
aviso flotante lo aparta unos días. Un recurso estimado nuevo lo vuelve a enseñar. Sin sitio activo, o sin recursos
estimados, no hay aviso.

### La vista previa y las listas vacías

Dos formas de ver una lista sin filas en la vista previa, para comprobar el diseño del estado vacío:

- `?pcs-vacio=<recurso>` en la dirección de la página (varios, separados por coma: `?pcs-vacio=publications,team`)
  pinta esas listas con cero filas. Sin valor (`?pcs-vacio`), pinta **todas**.
- Un `data/<recurso>.js` con `items: []` también da cero filas, pero el proyecto no lo genera así (un contrato sin
  ejemplos fabrica tres filas) y la exportación regenera `site/data/`: para probar, `?pcs-vacio`. Con `items` ausente se
  toma una fila con los `example` de los campos; en los demás casos, la vista previa repite las filas que haya, en
  orden, hasta `count`.

`count` es el **máximo** de filas que pinta una lista. La paginación, el «cargar más» y la búsqueda de una lista los
hace el JavaScript del diseño sobre las filas ya pintadas, igual en la vista previa y en el sitio servido.

### Una acción de formulario

`register(string $name, callable $definition)`. La definición se llama al pintar y devuelve
`['url' => string, 'attributes' => array<string,string>, 'js' => string[]]`: la dirección del POST (obligatoria; si falta o
está vacía, la acción se trata como no registrada y se anota), los atributos fijos del `<form>` y los JavaScript que esa
acción necesita, que se cargan en las páginas que la usan (junto con los globales del framework, ver «Activarlo»).

## La caché

Cada página y cada parte se compila una vez y se guarda en `app/cache/public-site/<sitio>/` como
`<page|part>-<nombre>-<huella>.php` más un `.json` con sus metadatos (estáticos, partes que incluye, título y
descripción). `app/cache` no se sirve por web y git la ignora.

La **huella** cubre: el HTML de esa vista, la versión del motor, el archivo de reglas, los nombres de las marcas
registradas, los campos de cada recurso, las acciones de formulario, las imágenes de marca disponibles, el sitio y la lista
de páginas y partes. Por eso se invalida **sola**: cambiar el HTML, añadir una página, registrar una marca o cambiar los
campos de un recurso produce una huella nueva; al compilarla se borran las vistas anteriores de esa página o parte. Lo que
la huella **no** cubre es lo que se pinta cada vez: los datos de una lista, el idioma y las traducciones se leen en cada
petición, así que no hay nada que invalidar cuando cambian. La importación borra la caché del sitio al terminar. Cada
archivo se escribe de forma atómica; si no se puede escribir, se anota y se sirve la página del framework.

## Seguridad

Lo que hay que saber, en general:

- **El PHP del diseñador nunca se ejecuta.** Las etiquetas de código de servidor que aparezcan en su HTML salen como
  texto, y la importación las rechaza como error. En la vista compilada solo hay código que escribe el motor.
- **Los datos se escapan y las direcciones se filtran.** Todo valor de una lista, de un atributo o de un texto sale
  escapado; las direcciones solo admiten las formas esperadas (`http(s)://`, `mailto:`, `tel:` y rutas relativas) y, si no, salen vacías. Los
  atributos que pasan al elemento se escapan y no incluyen manejadores de eventos.
- **La importación rechaza lo peligroso**: archivos que un servidor podría ejecutar o interpretar como configuración,
  enlaces simbólicos, nombres de archivo con caracteres que permitirían salirse de su carpeta y direcciones que pisarían las
  del sistema.
- **No toca nada si algo falla**, y el sitio anterior sigue sirviendo.

## Límites conocidos

- **Sin marcas dentro de la `<template>` de una lista.** No se resolverían: el compilador las quita y lo avisa.
- **`pcs-text` dentro de `<title>` no se resuelve.** El título es texto sin elementos; se traduce **entero**, con su texto
  literal como clave, en el grupo `public-site-<sitio>`.
- **Una marca registrada solo por el proyecto no la conoce el comprobador del diseñador**: el servidor y la importación sí,
  pero `CHECK.html` la señalará como desconocida y la vista previa no la pinta.
- **No hay orden para restaurar un respaldo**: se hace moviendo las carpetas (ver «La orden de importar»).
- Las páginas viven en la raíz del sitio (`/<página>/`): no hay subcarpetas de páginas.

## Pruebas

Dos suites: `unit-tests:core/public-site` (importar, compilar, pintar y exportar sobre un paquete sintético en un
temporal) y `unit-tests:public-site/kit` (el kit del diseñador).
