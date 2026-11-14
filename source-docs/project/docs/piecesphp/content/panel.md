# El panel por dentro

Cómo está hecho el panel de administración y qué usar para construir una vista suya. El front del panel usa
**Fomantic-UI** y jQuery.

**La norma es el esqueleto, no un módulo.** `Publications` es el que mejor lo cumple y sirve de ejemplo.

## El esqueleto

Una vista del panel se pinta en tres llamadas desde el controlador:

```php
$this->helpController->render('panel/layout/header');
$this->render('list', $data);
$this->helpController->render('panel/layout/footer');
```

Lo que queda alrededor de tu vista:

```
body
├─ barra superior (panel/layout/topbar.php): usuario, noticias, opciones de administración, modales del sistema
└─ div.container-sidebar
   ├─ aside.sidebar (panel/layout/menu.php): el menú
   └─ div.content
      └─ TU VISTA → section.module-view-container
```

`panel/layout/header-no-sidebar.php` y `footer-no-sidebar.php` son la variante sin menú.

## Dentro de tu vista

Una pantalla del panel se arma con las piezas de `src/app/view/panel/built-in/content/`: la cabecera
(`page-header.php`: migas, título, frase y acciones), la barra de guardar de los formularios y el estado vacío. Cómo se
usa cada una, con un ejemplo que se puede copiar, en «Pantallas de contenido»; el listado de publicaciones
(`src/app/classes/Publications/Views/publications/list.php`) es la referencia viva.

## Qué usar en cada caso

| Necesito… | Se hace con |
| :-- | :-- |
| Una tabla paginada por el servidor | `table[url].ui.basic.table` + `dataTableServerProccesing()` (`statics/core/js/helpers.js`) |
| Enviar un formulario | `form.ui.form` + `genericFormHandler()` |
| Botón primario / secundario | `ui primary button` / `ui basic button`. `ui button brand-color` es la forma anterior: las pantallas la van dejando, familia a familia, en el rediseño de la 10 (el `ui primary` lleva el color de marca de la instalación a través del tema del panel, que lo lee como `--accent`) |
| Pestañas | `.tabs-controls` + `ui tab` con `[data-tab]` |
| Un desplegable | `ui dropdown` (con `search` si filtra) |
| Una fecha | el atributo `calendar-js` en el campo |
| Avisar del resultado de una acción | `successMessage()`, `warningMessage()`, `infoMessage()`, `errorMessage()`: un *toast* arriba al centro. **El mensaje se pinta como HTML** (se puede mandar marcado a propósito, también en el `message` de una respuesta que `genericFormHandler` enseña): si el mensaje lleva algo que escribió el usuario, escápalo antes en el servidor (`htmlspecialchars()`) |
| Un modal | `div.ui.modal` y `$(selector).modal('show')` |
| Subir y recortar una imagen | `modalImageUploaderForCropperAdminViews()` |
| Traducir un texto fijo en HTML | el atributo `lang-group="<grupo>"` |

## Estética

- **Los colores son los tokens del tema**: el de marca es `--accent` (y `--accent-2`), y para fondos y textos que sigan
  el modo claro u oscuro, los de lectura (`--theme-surface`, `--theme-text`…; contrato en el README del tema). Úsalos;
  no pongas colores fijos.
- **En el SCSS del panel no se escribe un color a mano** (`#707070`, `rgba(0, 0, 0, 0.2)`): va en una variable de SCSS
  con el nombre de su papel. Si ya existe en la paleta del panel (`src/statics/core/sass/includes/_variables.scss`,
  sección «Paleta del panel»: `$colorTextStrong`, `$colorTextMuted`, `$colorBorderSoft`, `$colorShadowSoft`,
  `$colorSuccess`, `$colorError`, `$colorWhite`…), úsala; si es solo de tu hoja, decláralo arriba de ella. Lo comprueba
  `bin/cli verify-integrity` (la comprobación 54), que falla con la hoja y la línea. La zona pública (`src/statics/sass/`)
  queda fuera.

  ```scss
  //En una hoja de un módulo, dentro de su carpeta Statics/sass
  @use '../../../../../statics/core/sass/includes/variables_app_base' as *;

  //Colores de esta hoja: si cambian, cambia su aspecto.
  $markerColor: #E83D35;

  .my-module-card {
      border: 1px solid $colorBorderSoft;
      color: $colorTextMuted;

      .marker {
          background: $markerColor;
      }
  }
  ```
- **Fomantic primero.** CSS propio solo donde Fomantic no llega, y con motivo.
- **Nada de estilos en línea** para maquetar ni componentes de otra librería de interfaz.

## El inicio: tarjetas que registra cada módulo

El inicio del panel no es una vista fija: son **tarjetas** que cada módulo registra, y cada usuario ve las que su
permiso le deja. Las registra `PiecesPHP\AdminPanel\DashboardCardRegistry`; cada una es un `DashboardCard`, y lo que
enseña es un `DashboardCardBody`. **El inicio las pinta con las piezas de estadística de la hoja de diseño**
(`statistic-card.php`, `number-cards.php`, `action-card.php`): una tarjeta no trae HTML propio, así que todas tienen la
misma forma.

```php
new DashboardCard(
    string $key,              // minúsculas, cifras y guiones; única
    string $title,            // ya traducido; va en la barra de la tarjeta
    string $icon,             // icono de Fomantic, sin «icon»
    string $permissionRoute,  // el nombre de la ruta cuyo permiso hace visible la tarjeta
    callable $content,        // fn(UserDataPackage $user): ?DashboardCardBody — null oculta la tarjeta
    int $position = 100,      // de menor a mayor
    bool $collapsible = false,
    bool $collapsedByDefault = false,          // solo con $collapsible; plegada, $content NO se llama
    ?string $collapsedFooterCaption = null,    // el pie que se ve plegada (sin cifras)
    string|\Closure|null $collapsedFooterUrl = null, // su URL; una Closure(): ?string se resuelve al pintar
    string $help = ''                          // el texto del «?» de la barra; vacío, no hay «?»
);
```

Lo que enseña la tarjeta, con una de estas tres formas:

- **`DashboardCardBody::numbers(array $items, ?string $footerCaption = null, ?string $footerUrl = null)`**: de una a tres
  cifras, cada una `['value' => …, 'concept' => …, 'subconcept' => …, 'icon' => …, 'color' => 'green'|'orange']`
  (`value` y `concept`, obligatorios), con su pie y su flecha. Lanza con cero cifras o más de tres.
- **`DashboardCardBody::action(string $url, string $description, ?string $footerCaption = null)`**: una tarjeta de
  acción, con el icono y el título de la tarjeta. Para llevar a una pantalla, no para enseñar cifras. Con
  `$footerCaption`, el pie lleva ese texto junto a la flecha («Ver el informe»); sin él, solo la flecha.
- **`DashboardCardBody::html(string $html, ?string $footerCaption = null, ?string $footerUrl = null)`**: para lo que no
  sean cifras ni una acción. El HTML lo escapa quien llama. Úsalo poco: las otras dos ya tienen la forma del panel.

Así registra el framework «Mis sesiones» (`UserSessionsController::registerDashboardCard()`, llamado desde
`UserSystemFeaturesRoutes`):

```php
DashboardCardRegistry::register(new DashboardCard(
    'my-sessions',
    __(self::LANG_GROUP, 'Mis sesiones'),
    'desktop',
    'my-space-admin-user-security',
    fn(UserDataPackage $user): DashboardCardBody => DashboardCardBody::numbers(
        [
            ['value' => count(UserSessions::openOfUser((int) $user->id)), 'concept' => __(self::LANG_GROUP, 'sesiones abiertas'), 'icon' => 'desktop'],
        ],
        __(self::LANG_GROUP, 'Ver mis sesiones'),
        MySpaceController::routeName('user-security')
    ),
    10,
    help: __(self::LANG_GROUP, 'Las sesiones abiertas con tu usuario en este y en otros dispositivos.')
));
```

- **Los parámetros del final, por nombre** (`help:`, `collapsedFooterCaption:`…): son opcionales y así no hay que
  escribir los de en medio.
- **Un clon quita o sustituye** las del framework desde sus Routes, después de que se registren:
  `DashboardCardRegistry::remove('general-report')`, o `DashboardCardRegistry::replace(new DashboardCard('general-report', …))`
  (lanza si la clave no existe; `register()` lanza si ya existe).
- **Las del framework**: `my-sessions` (10), `pending-tasks` (20, las aprobaciones que ese usuario puede resolver; no
  sale si son 0), `users-access` (30, administradores: usuarios activos y accesos de hoy), `errors-alerts` (40, root;
  **nace plegada** y, plegada, enseña su barra y el enlace a los errores, sin cifras) y `general-report` (50, el informe
  general, como tarjeta de acción).
- **Dos cifras en una tarjeta se apilan**, como en la hoja de diseño: la rejilla de la pieza no tiene sitio para ponerlas
  en fila sin salirse de la pantalla.
- **Lo plegado se guarda por usuario**, en su `meta` (`dashboardCardsCollapsed`).
- Los repartos de siempre siguen delante: el administrador de organización y el usuario general entran a Mi perfil, y
  comunicaciones al listado de publicaciones.

## La barra lateral: bloques que registra cada módulo

La barra lateral tiene tres huecos donde un módulo o tu proyecto pintan un bloque propio, sin tocar
`app/view/panel/layout/menu.php`. Los registra `PiecesPHP\AdminPanel\SidebarBlockRegistry`; cada uno es un
`SidebarBlock`. **Sin bloques, la barra pinta lo mismo que antes**: ni un envoltorio vacío.

| Hueco | Constante | Dónde sale |
| :-- | :-- | :-- |
| Debajo de la marca | `SidebarBlock::SLOT_BRAND_AFTER_META` | dentro de `.brand > .identity`, bajo «Versión …» |
| Debajo del menú | `SidebarBlock::SLOT_MENU_AFTER` | tras la última entrada del menú, antes de los logos del carril contraído |
| En el pie | `SidebarBlock::SLOT_FOOTER` | dentro de `.footer`, bajo el patrocinador y el crédito |

```php
new SidebarBlock(
    string $key,        // minúsculas, cifras y guiones; única
    string $slot,       // una de las tres constantes de arriba
    callable $render,   // fn(UserDataPackage $user): string — el HTML del bloque; '' no pinta nada
    int $position = 100, // de menor a mayor dentro del hueco
    string $show = SidebarBlock::SHOW_ALWAYS // SHOW_ALWAYS, SHOW_EXPANDED o SHOW_COLLAPSED
);
```

Así lo registraría un módulo desde sus Routes (ejemplo; el framework no trae ninguno):

```php
use PiecesPHP\AdminPanel\SidebarBlock;
use PiecesPHP\AdminPanel\SidebarBlockRegistry;
use PiecesPHP\UserSystem\UserDataPackage;

SidebarBlockRegistry::register(new SidebarBlock(
    'mi-modulo-usuario',
    SidebarBlock::SLOT_BRAND_AFTER_META,
    fn(UserDataPackage $user): string => '<small>' . escape_html($user->fullName) . '</small>',
    show: SidebarBlock::SHOW_EXPANDED
));
```

- **El HTML sale tal cual.** Lo que venga del usuario o de la base lo escapa quien escribe el bloque (`escape_html()`).
- **Cada bloque va en `<div class="sidebar-block" data-sidebar-block="<clave>">`**, con `expanded only` o `collapsed only`
  según `$show`.
- **Un texto, solo con la barra expandida** (`SHOW_EXPANDED`): en el carril contraído (72 px) no cabe y se parte palabra a
  palabra. En el carril, un icono (`SHOW_COLLAPSED`).
- **A 390 px la barra es un cajón y no se contrae**: allí se ven los bloques `SHOW_ALWAYS` y `SHOW_EXPANDED`.
- **Sin sesión no se pinta ninguno**; la función recibe siempre un usuario.
- **Un clon quita o sustituye** los de un módulo, después de que se registren: `SidebarBlockRegistry::remove('<clave>')` o
  `SidebarBlockRegistry::replace(new SidebarBlock('<clave>', …))` (lanza si la clave no existe; `register()` lanza si ya
  existe).
- **Una excepción dentro de la función no se captura**: rompe la página, como en las tarjetas del inicio.
- **Provisional: el tema aún no da estilo base a `.sidebar-block`.** Un texto suelto toma la letra del sitio donde cae
  (en la marca, la del nombre) y bajo el menú no lleva la sangría de las entradas. Mientras, da forma al bloque con las
  piezas del tema (`<small>`, un `ui label`, un icono).

## Piezas que pone el sistema

No las reimplementes en tu módulo: ya están en la barra superior.

- **Lateral de usuario**: perfil, idioma, salir.
- **Cambio de idioma**: guarda la elección en una cookie y recarga.
- **Conectarse como otro usuario** (solo root): para ver el panel con los permisos de otro. Se empieza solo desde este
  modal (un enlace no la activa), no alcanza a otro root, al usuario de sistema ni a un usuario inactivo o de una
  organización inactiva, y caduca a los 60 minutos. Mientras dura, una franja arriba dice «Conectado como <nombre>» y
  lleva «Volver a mi cuenta». En código: `PiecesPHP\UserSystem\Authentication\Impersonation` (la regla) y las rutas
  `user-impersonation-start` y `user-impersonation-end`.
