# Arquitectura del front del panel

Qué es el panel por dentro y cómo se construye una vista suya. **Medido el 2026-09-16** (lote 9.4) con `git grep`
y lectura de archivos; cada regla apunta a la vista donde vive. Complementa `09-frontend-assets.md` (cómo se cargan
los assets y los selectores del núcleo).

**La norma es el esqueleto, no un módulo.** Publications es el módulo que mejor lo cumple, y por eso sirve de
ejemplo, pero no integra todo (PO, 2026-09-15). Cuando esta guía y Publications discrepen, gana esta guía.

## 1. El esqueleto

Una vista del panel se pinta en tres llamadas desde el controlador
(`src/app/classes/Publications/Controllers/PublicationsController.php:328-330`):

```php
$this->helpController->render('panel/layout/header');
$this->render('list', $data);
$this->helpController->render('panel/layout/footer');
```

Anidamiento resultante (el armazón con el `ui shell` del tema desde JCF, `f77f0298`; plan
`.agents/docs/planes/2026-10-29-el-armazon-del-panel.md`):

```
body.<$bodyClasses>                                   panel/layout/header.php:41
├─ div.ui.modal[support-js]                           header.php:44
├─ (salvo $noTopBar = true) panel/layout/topbar.php   LO QUE FLOTA (desde JCG): avisos fijos (nag), el popup de noticias
│                                                       [news-popup], los flyouts de Configuración [admin-options-flyout]
│                                                       y del perfil [profile-flyout], ventanas, franja de suplantación (sticky)
└─ div.pusher                                         header.php (lo exige el ui flyout de Fomantic)
   └─ div.ui.shell.admin-shell[data-shell-text]       header.php (admin-shell: ámbito de las reglas funcionales)
      ├─ nav.navigation                               panel/layout/menu.php (marca, button.toggle, accordion menu, .footer)
      └─ div.main
         ├─ header.bar > .right > topbar-bar.php      los botones y el ui dropdown de usuario; datos en topbar-data.php
         └─ main.content.<$containerClasses>          header.php
            └─ LA VISTA DEL MÓDULO → section.module-view-container
footer.php cierra main, .main, el shell y el pusher, y carga el JS
```

Medido el 2026-10-29 (JCF y JCG). **`html` lleva `scroll-padding-top`** (la barra, y la franja al suplantar:
`_topbar.scss`): un `#ancla` o `scrollIntoView()` no queda bajo la barra; para desplazar a mano, `pcsStickyTopOffset()`
(`helpers.js`). Los avisos fijos de abajo publican su alto en `--pcs-bottom-nags`.

**Desde JCF se desplaza la PÁGINA**, no una caja (antes, `.container-sidebar > .content`). La barra es `sticky` y mide
64 px (56 en el móvil): lo que se ancle arriba la descuenta (el índice de la hoja de diseño, `design-sheet.scss`). El
geovisor es la excepción: `main.content` del alto visible y sin desplazar (`geo-viewer.scss`, regla con su motivo).
**`header.bar` lleva `backdrop-filter`**: es bloque contenedor de sus hijos `fixed`, así que nada flotante va dentro de
ella (por eso `topbar.php` se partió en tres). **El shell no lleva `ui-pcs`**: una regla funcional del contenido se escribe
con `:is(.#{$globalClassPcs}, .admin-shell) &`. El JS: `pcsAdminShell()` en `configurations.js` (`rememberState`);
`pcsAdminSideBarIsOpen()`/`pcsAdminSideBarToggle()` quedan como API global. **Trampa**: `$('.ui.accordion').accordion()` sin acotar
reinicia también el de la navegación; un acordeón se inicia sobre su formulario (los seis del framework, acotados en
JCG, `bf858464`).

Variantes sin lateral: `panel/layout/header-no-sidebar.php` y `footer-no-sidebar.php`.

**Trampa: una vista corre DENTRO de `BaseController::render()`** (`src/app/core/psr4/PiecesPHP/Core/BaseController.php`
`:106-137`). Hace `extract($data)` y el `require` de la vista en el mismo ámbito, y decide al final con su `$mode` si
imprime o devuelve. Una vista que asigne `$mode`, `$output` o `$data` pisa las de `render()`: en JCH, un `foreach (… as
$mode …)` en `topbar-bar.php` dejó la barra entera sin imprimir (`#2172`; renombrado a `$themeOption`). Por la misma vía,
una clave `mode` en `$data` haría lo mismo, SIN VERIFICAR.

**Trampa: una `.menu` dentro de un `ui dropdown` es un submenú.** Fomantic la oculta y, si se la fuerza a verse, la
coloca fuera del desplegable con reglas que llevan `!important` (`dropdown.less:260`). Por eso el selector del modo de
color del menú de usuario son botones (`ui fluid three mini buttons`) y no la pieza segmentada de la guía (JCH, `#2176`).
Y todo `.item` de dentro es un ítem del desplegable: le quita `active` al iniciarse (medido en JCH, `#2180`).

**El modo de color** (JCH, `9cc93c88`): la meta `theme_mode` del usuario (`AdminPanelController::themeModeOf()`) pone
`data-theme` en `<html>` desde `header.php`: siempre `light`, `dark` o `auto` (sin meta, `auto`: el del sistema; JCM,
`a6268d5e`). Una hoja propia que pinte un fondo o un texto usa los tokens de LECTURA del tema, nunca blanco o negro fijos.
CKEditor: `.ck { --ck-color-…: var(--theme-…) }` en `rich-editor-adapter.scss`.

**El contrato del tema** (README del tema, «Variables del host»): se ESCRIBEN en `:root` `--accent` (y `-2`, `-3`), `--data-2..5`, `--font-body`, `--font-heading`, `--font-size-base`, `--r-*`, `--shadow-*` (quitar una sombra: `0 0 transparent`, nunca `none`); se LEEN, nunca se redefinen, las siete `--theme-*`. **Trampa**: `--theme-text-muted` sobre `--theme-fill` en claro da 4,3:1 (bajo AA).

**Íconos SVG** (JCI): el panel y el acceso cargan, tras el tema, `svg-icons.css` del tema y `src/statics/svg-icons/svg-icons.css`
(la del clon, solo con el patrón comentado; `src/app/config/assets.php`, `fomantic-piecesphp`). **Trampa**: una hoja en el
`Statics/` de un módulo con `url("x.svg")` relativa da 404, porque se sirve desde `statics/server-delegated/`, que solo
enlaza lo que se pidió por su ruta PHP (`#2180` d).

**Las pestañas** (JCL): `ui secondary pointing menu` + `item`; `tabs-controls` es gancho sin estilo (14 vistas, sus JS
la seleccionan con `[data-tab]`). Lo que no es pestaña dentro del menú no lleva `data-tab` (el `right menu
actions-buttons`), o el JS lo tomaría por una.

**La zona (panel o público) la declara el LAYOUT, no la clase del controlador**: `APIController` hereda de
`AdminPanelController` y `PublicationsPublicController` de `BaseController`. Quien necesite la zona la pide al layout
(`ExtraScripts::getScriptsFor($zone, …)`, `MetaTags::setRobots()`), nunca a `instanceof` (`pendientes.md` 273).

## 2. Dentro de la vista: `section.module-view-container`

**Listado** (`src/app/classes/Publications/Views/publications/list.php`):

| Pieza | Clase | Línea |
| :-- | :-- | :-- |
| Contenedor | `section.module-view-container` | :10 |
| Migas | `div.breadcrumb` con el HTML de `get_breadcrumbs()` (`AppHelpers.php:3126`) | :12 |
| Limitador de ancho | `div.limiter-content` | :16 |
| Título y descripción | `div.section-title > .title` y `.description` | :18-21 |
| Botones principales | `div.main-buttons > a.ui.button.brand-color` (`.alt` para el secundario) | :27-38 |
| Pestañas | `div.tabs-controls > div[data-tab]` y `div.ui.tab.tab-element` | :45-52 |
| Tabla | `div.mirror-scroll-x > div.container-standard-table > table[url].ui.basic.table`; última columna `th[order=false][class-name=buttons]` | :54-71 |

El JS de la tabla es `dataTableServerProccesing(...)` (`src/statics/core/js/helpers.js:757`), llamado desde
`Statics/js/publications/list.js:84`.

**Formulario** (`.../publications/forms/add.php`): la misma cabecera (:16-24) y `.tabs-controls` (:33); después
`form.ui.form.<modulo>` (:41) `> div.container-standard-form` (:43) `> div.ui.tab[data-tab]`. Campos con
`.field.required` (:49), `.ui.toggle.checkbox` (:75), fechas con el atributo `calendar-js` (:68). Guardar:
`button.ui.button.brand-color[save]` (:276). El JS es `genericFormHandler` (`add-form.js:66`).

El CSS de todas estas clases vive en `src/statics/core/sass/admin_app_base.scss` (`.module-view-container` :115,
`.breadcrumb` :220, `.section-title` :258, `.container-standard-table` :308, `.limiter-content` :563,
`.tabs-controls` :595).

## 3. Por situación

| Necesito… | Se hace con | Ejemplo vivo |
| :-- | :-- | :-- |
| Una página de listado | Esqueleto de §2 + `dataTableServerProccesing` | `Publications/Views/publications/list.php` |
| Un formulario | `form.ui.form` + `container-standard-form` + `genericFormHandler` | `Publications/Views/publications/forms/add.php` |
| El título de una sección dentro de un formulario o una tarjeta | `div.form-section-title` dentro de `container-standard-form`. **No `ui dividing header`**: `ui-pcs.scss:377-379` lo pinta con `--second-brand-color` (magenta), y el PO lo rechazó en Respaldos (2026-10-16) | `Settings/Views/panel/pages/app_configurations/email.php:98` |
| Una pantalla sin listado (estado, ajustes, acciones) | El contenido en `container-standard-form` (también si no hay `form`), cada tabla en `container-standard-table`, y una sola frase: la de la cabecera | `SystemStatus/Views/maintenance.php` |
| Separar dos bloques seguidos | Nada: `container-standard-form` y `container-standard-table` llevan `margin-bottom: 1rem` cuando no son el último hijo (`admin_app_base.scss`, GG). **Lo que va después de un `form` con la barra de guardar no lo cubre** (Correo, «Probar el correo»: `abierto.md` C2) | `Settings/Views/panel/pages/app_configurations/backups.php` |
| Un botón primario / secundario | `ui button brand-color` / `ui button brand-color alt` | `list.php:30-38` |
| Pestañas | `.tabs-controls` + `ui tab` y `.tab()` sobre `[data-tab]` | `list.php:45-52` |
| Un desplegable | `ui dropdown` (con `search` si filtra) y `configFomanticDropdown()` | `topbar.php:556` y `configurations.js:1569` |
| Un mensaje de éxito o error tras una acción | `successMessage` / `warningMessage` / `infoMessage` / `errorMessage` (`helpers.js:15/87/159/231`): un `toast` de Fomantic arriba a la derecha (JCT, `8fcc6ae1`, el larguero; mientras dura tapa el menú de usuario) | cualquier `*-form.js` |
| Un aviso permanente del sistema | `ui bottom fixed nag` con `system-alert-nag` y `data-key`, uno por aviso que registre `SystemAlertRegistry` (`topbar.php:225-229`; el cierre se recuerda por clave, `admin-area/js/main.js:169-174`). Hoy, el de la `app_key` de relleno (`app-key-placeholder`); otro uso se habla antes con el PO | `topbar.php:229` |
| Un modal | `div.ui.modal[<atributo>]` y `$(sel).modal(...).modal('show')`; no hay helper genérico | `topbar.php:534` y `configurations.js:1539-1556` |
| Subir y recortar una imagen | `modalImageUploaderForCropperAdminViews()` (`AppHelpers.php:2961`) | `topbar.php:577` |
| Una tabla vacía | El texto `emptyTable`/`zeroRecords` de DataTables (`statics/core/js/translations/es.js:100/111`); en tarjetas, `helpers.js:1055` | — |
| El marco del acceso | `usuarios/inc/access-frame.php`: la vista captura su cuerpo con `ob_start()` y lo pasa en `$accessContent` (JCY: la comprobación de etiquetas no admite un abrir y cerrar en dos archivos) | `usuarios/login.php` |
| Un 403/404 | `forbiddenHandler`/`notFoundHandler` (`config/containers.php:227`, `:165`) pintan `pages/403` y `pages/404`, **sin el layout del panel**, las dos (y 503) con `pages/error-page.php`, que trae su hoja del tema también en la zona pública y no pasa por los assets (JCY); JSON si la petición es XHR | `src/app/view/pages/404.php` |

## 4. Piezas que despliega el sistema

- **Lateral de usuario:** no es `ui sidebar`: es `.topbar-content` + `.topbar-options.user-options`
  (`topbar.php:275` y `:287`), abierto y cerrado por JS propio (`pcsAdminTopbars()`, `configurations.js:813`), CSS en
  `includes/components/_topbar.scss`.
- **Cambio de idioma:** opción con `change-system-lang-trigger` (un `MenuGroup` de `topbar.php:43-52`) y modal (`:534`, solo con varios
  idiomas). `handleGlobalChangeLangModal` escribe la cookie `cookie_lang_definer` y recarga: no hay ruta.
- **Conectarse como otro usuario (solo root):** modal `connect-as-another-user-modal` (`topbar.php:573`) con
  búsqueda en la ruta `users-search-dropdown`; `handleConnectAsAnotherUser` (`configurations.js`) llama con
  `postRequest()` a la URL de su `data-url` (`user-impersonation-start`) y recarga; un rechazo sale dentro del modal. La
  cookie la escribe el servidor (`HttpOnly`): JS ya no la toca. Salir: todo `[exit-as-logged-options]` con su `data-url`
  (`user-impersonation-end`): la franja `.impersonation-band` (`topbar.php:226-234`, también su aviso de fin) y la
  entrada del menú. La franja desplaza el botón del menú del móvil con `body:has(> .impersonation-band)` (`_topbar.scss`).
  Líneas medidas el 2026-10-23 (JA).

## 5. Estética: lo que ya hay

- **Los colores del cliente son tokens del tema generados en PHP**: `CSSVariables` en `src/app/config/containers.php`
  publica `--accent` y `--accent-2` (JCP, H106), servidos en la ruta `admin-global-variables-css`. En SCSS, las variables
  de `includes/_variables.scss` (`$colorFirst`…) apuntan a los tokens del tema; la zona pública y `errors.scss`, a
  valores fijos.
- **Ningún color escrito a mano en el SCSS del panel** (bloque HF, 2026-10-22; la regla del PO es «colores solo por
  variable»). Lo que no es de marca (grises, sombras, los colores de estado de Fomantic) es una **variable de SCSS con
  nombre de su papel**: la que se usa en dos o más hojas, en la paleta de
  `src/statics/core/sass/includes/_variables.scss` (la leen las hojas del panel por `src/statics/core/sass/includes/_variables_app_base.scss`);
  la de una sola hoja, arriba de esa hoja. La variable se escribe con el valor **literal de antes**, así el CSS compilado
  es el mismo y `bin/huella-css --comparar` lo demuestra. **Por qué SCSS y no variables CSS**: una variable CSS nueva es
  contrato del núcleo (`containers.php`) y su sitio es el tema de Fomantic de la 10 (`abierto.md` D16), que cambiará
  los valores en un solo sitio: la paleta. Fuera de la regla: la zona pública (`src/statics/sass/`: el diseño es solo
  el panel) y las líneas que DEFINEN una variable. **Hecho en HF** (`dc85fcda`): ocho valores en la paleta
  (`$colorTextStrong`, `$colorTextMuted`, `$colorBorderSoft`, `$colorBorderSoftAlt`, `$colorShadowSoft`, `$colorSuccess`,
  `$colorError`, `$colorWhite`) y el resto, arriba de su hoja. Dos trampas: en un **parcial** (`_*.scss`, que se carga
  con `@use … as *`) la variable local es **privada**, `$-nombre`, o se exportaría a quien lo carga y chocaría con la
  paleta; y una hoja que ya carga otro parcial de variables `as *` carga la paleta **con espacio de nombres**
  (`@use '…/includes/variables' as palette;` y `palette.$colorTextMuted`: `UserSystem/Statics/sass/login.scss`). El CSS
  compilado va comprimido y en minúsculas: la huella no distingue `#FFF` de `#fff`.
- **Componentes de Fomantic que usa el panel** (125 vistas del panel, contando `ui … <componente>` en el atributo
  `class`, un archivo cuenta una vez): `button` 97, `form` 75, `dropdown` 35, `label` 30, `input` 24, `grid` 23,
  `table` 20, `buttons` 17, `tab` 16, `header` 11, `checkbox` 8, `modal` 6, `menu` 5, `segment`/`card`/`cards` 3,
  `toast`/`nag`/`divider` 1. **Cero**: `message`, `sidebar`, `calendar` (va por `calendar-js`), `popup`, `accordion`,
  `progress`, `steps`, `breadcrumb`, `loader`, `dimmer`, `statistic`, `list`.
- **Piezas propias donde Fomantic tiene una** (existentes; no se multiplican): `.tabs-controls` en vez de
  `ui tabular menu`; `.breadcrumb` en vez de `ui breadcrumb`; lateral y topbar propios; `.progress-circle`
  (`MySpace/Views/example-resources.php:120`) en vez de `ui progress`.

## 5b. Los textos de una pantalla

**El texto habla de QUÉ hace la pantalla para quien la usa, no de CÓMO lo hace para quien la programó** (PO,
2026-09-30, sobre «Lo que ya sirve el framework»: «esos errores de UI/UX son absurdos»).

- Fuera de los rótulos: «framework», «servir», «server-delegated», «caché de X», nombres de archivos, de opciones o de
  clases. Si el nombre técnico hace falta, va pequeño y en segundo plano (entre paréntesis, en gris), nunca como rótulo.
- Cada rótulo es un objeto o una acción que el usuario reconoce; cada ayuda, una frase y, si cabe, un ejemplo.
- **Quien revisa una pantalla nueva revisa sus textos en la captura**, no solo su armazón y sus medidas.

## 6. Prohibido

- **CSS propio donde Fomantic ya resuelve.** Una vista nueva o rehecha se construye con componentes de Fomantic; el
  CSS propio va solo donde Fomantic no llega, y se justifica en la instrucción (PO, 2026-09-15). **Un defecto en CSS
  propio no se parchea: se arregla sustituyendo ese CSS por el componente del tema** (PO, 2026-11-05, ante el parche de
  las migas de los perfiles: «¿no reduciremos CSS y usaremos los estilos base que ya nos provee nuestro tema?»). En una
  `10.x`, lo que un clon puede incluir o cargar por su nombre no se borra: deja de cargarse y queda `@en-desuso 11.0.0`
  (H170); y una plantilla de traducción con marcado (`src/app/lang/files/`) no se cambia hasta una MAYOR (H174).
- **Componentes fuera del sistema** (otra librería de UI, un modal a mano sin `ui modal`).
- **Una vista del panel fuera del esqueleto** sin decirlo: sin `section.module-view-container` dentro de `.content`.
- **Estilos en línea** para maquetar (`style="max-width…"`).
- **Toda instrucción que toque una vista del panel nombra el componente de Fomantic que usa y dice si es descartable
  y si se puede apagar** (PO, 2026-09-15). Sin eso, no sale.

- **Preguntar con `window.confirm`, `alert` o `iziToast.question`**: se pregunta con `confirmAction()`
  (`helpers-lib/confirmAction.js`, un `ui mini modal` desde JCT, `8fcc6ae1`; nunca `.modal('destroy')` al cerrar: destruiría el dimmer de la ventana que haya debajo) y se avisa con `errorMessage()`/`successMessage()`. Lo vigila la comprobación 50 de
  `verify-integrity` (artefacto `files/dev/browser-dialogs-allowed.json`, solo las cadenas de las suites de escapado).
- **Un color escrito a mano en el SCSS del panel** (`#…`, `rgb()`, `rgba()` fuera de la línea que define la variable):
  lo vigila la comprobación 54 de `verify-integrity` (`VerifyIntegrityTask::checkLooseColorsInPanelScss()`), cota 0,
  sobre las hojas VERSIONADAS del panel. No ve los colores con nombre (`red`, `white`) ni lo que va tras `//`.
- **Un archivo nuevo en lo global** (`src/app/view/`, `src/statics/core/js/`, `src/statics/core/sass/`) sin declararlo:
  va a su módulo, o se declara global con su motivo en `files/dev/global-files.json` (comprobación 49; criterio del PO,
  2026-10-14). Los estáticos de un módulo los sirve su propia ruta `…/statics/[{params:.*}]` (patrón:
  `UserSystemFeaturesRoutes`, `AdminPanelController`).
  **Qué es global** (criterio para la auditoría de C9, bloque HG; decide el arquitecto con el criterio del PO): (a) lo
  que usan **dos o más módulos** o el arranque del panel para todos (`ui-pcs.scss`, `configurations.js`, las
  traducciones de JS, `genericFormHandler`…); (b) lo **público sin módulo**: las páginas y el armazón de la zona pública
  y de los errores (`view/pages/`, `view/layout/`, `view/webflow/`); (c) lo que **todo el correo** usa
  (`view/mailing/template_base.php`). Lo que solo usa UN módulo, a ese módulo. Lo que no usa nadie, a `abierto.md` G11
  (basura), sin moverlo. El armazón del panel (`view/panel/`) espera a P194.
- **Un marco dentro de otro**: las piezas de estadística ya son `ui segment`; no se envuelven en otro segmento.

## 7. Quién no sigue el esqueleto (deuda, sin lote)

`git grep -l -F "module-view-container" -- 'src/*.php'` da **71 archivos**. No lo usan, entre las páginas del panel:

- **Configuración**: no es deuda, es OTRO armazón, y está estandarizado. Las trece pantallas que se abren desde el
  menú de configuración —las nueve de `Settings`, el índice y las cuatro de Sistema— usan
  `main.<pantalla>-view > section.main-body-header > div.head (h2.tittle + span.sub-tittle con el GRUPO) +
  div.body-card`, **sin migas**. Las clases viven en `ui-pcs.css`; lo único que el ARMAZÓN pide a cada hoja es
  `@import 'includes/base';` (`src/statics/core/sass/app_config/includes/_base.scss`), que quita el relleno del
  contenedor. **Una pantalla nueva del menú de configuración usa este armazón y esa importación; la regla no se
  copia a CSS plano.** Se mide con `bin/navegador --mide`: `top` de `.main-body-header` 0 y `padding-top` del
  contenedor 0px en las trece. Las reglas propias de una pantalla van en su hoja, y el mínimo posible: el PO
  anunció un rediseño que reducirá el SCSS propio (`pendientes.md` 266.9; lo que se llevará, en
  `../docs/inspiraciones/04-espacio-publico-reportes.md`).
- `panel/pages/about-framework.php` (`main`), `dashboard.php` (`div.banner-zone`), `test-cropper.php`,
  `generic_token/*`.
- **Usuarios** (`src/app/classes/PiecesPHP/UserSystem/Views/usuarios/`): `form.php` y `select-type-user.php`; los 18 formularios por tipo son `form-by-type/user-form.php` desde JCY (`8339ea25`): cada `<modo>/<tipo>/form.php` solo fija `$formMode` y `$formType`.
- **FileManager**: `file-manager.php` (una tarjeta `ui segment` desde JFH) y `file-manager-rich-editor.php`. **Su tema, `FileManager/Statics/sass/elfinder-theme.scss`, va entero dentro de `:root`**: elFinder carga su `css/theme.css` por su cuenta (`cssAutoLoad`, `elfinder.full.js:1189-1262`) DESPUÉS de nuestra hoja, aunque no esté en `assets.php`; una regla fuera de `:root` pierde contra él (`#2498` D2). `header-no-sidebar.php` pone `data-theme` desde JFH (`097b89a4`): una vista sin él sale en claro.

Los parciales (`panel/built-in/utilities/*`, `util/*`, `map-elements`) no son páginas y no cuentan. **Alinear esas
vistas no es un lote hasta que el PO lo decida**; su objetivo, si llega, es «parecerse un poco más», no copiar
Publications.

## 8. Capturas del panel

**`bin/comparativa --carpeta <dir> [--ancho <px>] <escaparate>=<ruta>`** (JCU, `a8b7df77`) junta la referencia del tema y nuestra pantalla en `<dir>/comparativa/`, con sus rótulos: con ella se cierra cada vista prototipada (H109). No viaja a la distribución.

`bin/navegador --capturas <carpeta>` guarda una imagen de cada pantalla de `--pantallas` (bloque DO, 2026-10-09):

- **La pantalla entera**: tras las comprobaciones, mide la caja visible con desplazamiento propio más alta —el
  contenido o el menú lateral (§1)— y agranda la ventana hasta ella (tope 20.000 px, que la línea declara). A 1440 px
  de ancho, el menú lateral mide 1.398 px: una pantalla corta sale con hueco bajo el contenido.
- **`--ancho <px>`** (por omisión 1440); a 390 el menú lateral se pliega.
- **`--oculta '<selector>[,…]'`**: oculta lo que se diga antes de medir y capturar, y lo declara por pantalla con su
  número de elementos. El aviso de la `app_key` de relleno: `'[system-alert-nag][data-key="app-key-placeholder"]'`.
- **Con datos**: se siembra, se captura y se limpia en la misma ronda (`13-recetas.md`, receta 8d).
- **El canal al PO**: el coder deja las capturas en `/tmp/pcsphp-capturas/<fecha>-<familia>-<ancho>/`; el arquitecto
  las lee y se las envía con el documento de propuesta.

## 8b. El menú lateral (FS, `f047af99`)

Las posiciones salen de las bandas de `MenuGroupCollection` (`BAND_HOME` 0 … `BAND_LANGUAGES` 900; P134 a), con un
desplazamiento por entrada y **ningún icono repetido**. Con posiciones iguales manda el orden de registro (desde PHP 8 la
ordenación es estable). `MenuGroup` no tiene títulos de sección. «Mi espacio» depende de
`ContentNavigationHubRoutes::ENABLE`. Las capturas (`bin/tools/navegador/recorrer.js:80`) resuelven por nombre de
ruta, no por orden del menú.

**Contraído, el menú desplaza, y con él los logos** (JDI, `9803d5f7`, P284; `admin_app_base.scss`, `menu.php`). El bloque `.collapsed.only` es el último hijo de `.ui.menu`, no del pie, y `.navigation` no desplaza: lo que sale de ella, como `.toggle`, se recortaría. Como el menú recorta, flotantes y tooltips van con `position: fixed`, a la altura que mide `pcsAdminRailPositions()` (`configurations.js`); `72px` y el hueco son del tema. **El puente del flotante mide 28 px**: con `fixed` hay un hueco que el del tema no cubre, y sin él no se pulsa un subítem. **Los logos no se encogen** (el PO rechazó eso en la `10.0.3`). Medido en `#2327`: 6 tamaños, con y sin aviso.

**Preguntar** (FT, `57e3c7d4`): la comprobación 50 caza también un toast de Fomantic con botones (`actions` dentro de la
llamada); los avisos sin botones siguen permitidos. **`RowActions::delete()` pinta siempre en rojo** (decisión del
arquitecto con el criterio del PO del 2026-10-10: borrar no va en negro).

## 9. Las piezas de las pantallas de contenido (rediseño, 2026-10-09)

Aprobadas por el PO (P123, `../docs/diseno/01-contenidos.md`); las enseña, cada una con su código, la **hoja de diseño** del panel
(ruta `admin-design-sheet`; sus ejemplos, uno por archivo, en `src/app/classes/PiecesPHP/AdminPanel/Views/panel/pages/design-sheet/examples/`,
y su JavaScript, si lo necesitan, en `src/app/classes/PiecesPHP/AdminPanel/Statics/js/design-sheet/` con el mismo nombre; su hoja,
`AdminPanel/Statics/sass/design-sheet.scss`. Vivían en `src/statics/core/` hasta FK, `6d0b9a51`). Una pantalla nueva del rediseño se compone de
aquí; lo que falte, se añade aquí antes que a la pantalla.

| Pieza | Dónde | Fomantic | Apagable |
| :-- | :-- | :-- | :-- |
| Cabecera | `panel/built-in/content/page-header.php` (title, description, breadcrumbs como ARREGLO —la forma de `get_breadcrumbs()`—, que escapa y pinta con `ui breadcrumb`; un string → `E_USER_DEPRECATED` y sin migas; primaryAction, secondaryActions; cada acción con `attributes`) | desde JBV (familia 0 de la fase (1), `51324c7d`): `nav.ui.breadcrumb` (separador `chevron right`, la activa `span[aria-current]`), `h1.ui.header > .sub.header`; con acciones, `ui stackable bottom aligned grid` y `ui stackable buttons`, la `ui primary` la ÚLTIMA; detrás, `ui hidden divider` | No |
| Acciones de fila | `PiecesPHP\Core\Utilities\Helpers\RowActions` (`action()`, `delete()`, `primary()`, `inactive()`, `text()`, `cell()`, `attributes()`): hasta `MAX_VISIBLE` = 3 acciones, todas a la vista; con más, la directa y un «⋯». `inactive()` es un `button` con `disabled` de clase y `aria-disabled`, sin el atributo: enfocable y con su tooltip | `ui icon button`, `ui primary icon button`, `ui negative basic icon button`, `ui basic button`, `ui dropdown` [row-actions-menu] | Sí |
| Etiqueta de estado | `PiecesPHP\Core\Utilities\Helpers\StatusLabel` (`html()` con `POSITIVE`, `NEGATIVE`, `WARNING`, `INFO`; `neutral()`). JCT `5bafbda6`. Un `ui <color> label` relleno da texto negro por la regla de contraste automático del tema | `ui <color> basic circular label` + `circle icon`; `ui label` | Sí |
| Título de sección | `panel/built-in/content/section-header.php` (`title`, `description`, `url`, `id`). JCV `95a6cda0` | `h2.ui.header`; las secciones, separadas con `ui section divider` | Sí |
| Interruptor con ayuda | `panel/built-in/content/toggle-field.php` (`inputName`, `label`, `checked`, `description`, `id`, `attributes`); lo inicia la pantalla con `.checkbox()`. JCV; `inline` salió en JCW (tema `ae32258`: el asterisco se queda en la etiqueta del campo) | `ui toggle checkbox` + `.description` | Sí |
| Lectura en vivo | `input[data-live-reading="days\|seconds"]`, y su texto en `[data-live-reading-for="<id>"]`; `contentLiveReadings()` (`admin-area/js/main.js`). JCV | ninguno | Sí |
| Tarjeta de imagen | `panel/built-in/content/image-card.php` (`id`, `title`, `headingTag`, `width`, `height`, `description`, `hint`, `accept`, `image`, `imageAlt`, `dark`, `field`, `action`, `format`, `quality`, `fillColor`) + `image-editor.php`, uno por página y fuera de los formularios + `own-plugins/ContentImageEditor.js` (`new ContentImageEditor()`, `onApply(id, fn)` sin `action`). JCW `8401f180` | `ui horizontal card` + `checkered image`; `ui fullscreen modal`; `ui slider` | Sí |
| Imagen en un formulario | `panel/built-in/content/image-field.php` (`card`, los parámetros de la tarjeta, y `attributes`): tarjeta + `.field`; lo aplicado lo añade el JavaScript con `onApply()` y el nombre de `field`. Con `required`, el rechazo es nativo y `updateCard` lo quita al aplicar. JCZ `7e186c42` | `ui horizontal cards` | Sí |
| Filtrar y Limpiar | `panel/built-in/content/filter-actions.php` (`clearUrl`: sin ella, `reset`). JCX `6394bb83` | `ui mobile equal width flex`, `ui primary labeled icon button` | Sí |
| Tarjeta de cifra | `panel/built-in/statistics/statistic-card.php` con `figure` (`value`, `dataType`, `label`, `meta`, `description`, `content`, `variant`, `size`), dentro de `ui cards`. JCX | `statistic card` del tema | Sí |
| Contraseña con ojo | `panel/built-in/content/password-field.php` (`id`, `inputName`, `value`, `label`, `placeholder`, `autocomplete`, `required`, `attributes`, `fieldExtra`); la mueve `contentPasswordFields()` (`admin-area/js/main.js`). JCT `3aceb0e9` | `ui action input` + `ui icon button` | Sí |
| Sin registros | `panel/built-in/content/empty-state.php` (`text`, `description`, `icon`; `action` con `text`, `url` e `icon`) | `ui placeholder segment` | Sí |
| Pasos | `GenericStepsViewHandler` (JS global, `helpers-lib/`; `options.indicators` sincroniza un `ui steps`): el ejemplo, «Pasos» de la hoja (`formularios-pasos`, JBZ `719121e4`). El valor del paso cambia de número a texto: comparar con `==` | `ui steps` | Sí |
| Acción principal al pie | `panel/built-in/content/main-action.php` (title, description, buttonText, attributes con `RowActions::attributes()`, variant `primary`/`negative`, icon); clase del framework `content-main-action` (JBY, `27f5479a`) | `ui secondary segment` + `ui stackable flex` del fork | Sí |
| Ventana de formulario | `panel/built-in/content/form-modal.php` (con `close icon` y `scrolling content`; guardar SIN `approve`: es `type="submit"`, y con `approve` Fomantic cerraría antes de guardar; un dropdown dentro sale recortado) + `contentModals()` (`admin-area/js/main.js`): el disparador `[data-content-modal="<id>"]`, con `data-content-modal-values` (JSON) para editar; `data-content-modal-action` y `-title` cambian acción y título | `ui modal` | Sí: una categoría compleja (color, imagen) va en página, como las de noticias |
| Campo de color | `panel/built-in/content/color-field.php` (`id`, `value`, `inputName`, `label`, `ariaLabel`, `disabled`, `attributes`); lo sincroniza `contentColorFields()` (`admin-area/js/main.js`). JCQ `c11379f1` | `ui colorpicker input` del tema (JCZ `f72bff8b`); `configColorPickers()` también | Sí |
| Barra de guardar | `panel/built-in/content/form-action-bar.php` (`saveAttributes`; cada secundaria con `attributes`, `type` y `danger`); el `form` nace `ui form initial` con `data-content-form` | desde JBV: `div.actions` (pieza del FORK; desde JBX la pinta el tema: `.ui.form .actions`, a la derecha y con una línea encima, sin caja); orden peligrosas (`ui basic negative`) → secundarias (`ui basic`) → guardar (`ui primary`); **sin fijar** (el PO, 2026-10-09: «flotante no; mantengámoslo simple»); sin CSS propio | No |

**Trampas medidas** (`#1234`-`#1240`):
- **Una segunda instancia de `ContentImageEditor` lleva `scope`** (`new ContentImageEditor('profile')` y `scope` en `image-editor.php` e `image-card.php`): el modal se desprende al dimmer, así que partir por DOM no vale (JCZ, `d321b279`). **`invert-on-dark`**, clase del framework en el SCSS del panel: `filter` no admite `light-dark()`; el acceso y los errores no llevan `data-theme` y salen en claro.
- **Una pieza no recibe un parámetro `$name`**: `render()` ya trae `$name` con la ruta de la vista, y una llamada sin él la
  pintaría en el campo (JCQ; lo cazó `core/theme-tokens` k18). Por eso `color-field.php` usa `inputName`. Vale junto a
  `$mode`, `$output` y `$data`.
- **`getValue()` de `Parameters` revalida al leer**: quitar `validate()` no provoca nada; para provocar una validación
  previa hay que leer crudo (JCQ).
- **«Nadie lo nombra» no prueba que algo no se use** (`9a12ef36`, 2026-10-22; repuesto por H86): `src/gulpfile.js:374` mete todo
  `statics/core/js/helpers-lib/*.js` en el JavaScript global, y una vista se pinta por su nombre (`render('mailing/…')`).
  Y aunque no se use, **una utilidad no se borra: se revisa, se mejora y se documenta** (PO, 2026-10-29).
- **Una vista de correo que hace `new BaseController()` deja sin assets globales la página que la pinta a mitad**
  (`BaseController.php:75-77`, `clear_global_assets()` sin `lock_assets`; `#2134` 6a): ocho vistas de correo siguen ese patrón.
  Un ejemplo en la hoja va en su propia petición (iframe con `src`), nunca pintado dentro de la página.
- **El rosa de lo obligatorio es de Fomantic** (`.ui.form:not(.initial) … input:invalid`, `plugins/semantic/components/form.css:560`).
  Se apaga con la clase `initial`, y `contentFormsInitial()` (`admin-area/js/main.js`) la quita al intentar guardar.
  Solo en los formularios con `data-content-form`: los viejos siguen naciendo rosa.
- **DataTables es la 3.1.4 desde JCB** (`3c623771`): en sus callbacks `this` es un `DataTable.Dom`, no jQuery (`this.api()`);
  nada de propiedades internas (`aoColumns`, `nTable`); `import_datatables()` solo trae `responsive` y deja en el registro
  un plugin que no existe. Los iconos de orden: dos hojas pintaban el mismo `.dt-column-order::before/::after`; el tema trae
  su componente `datatables` (`Fomantic-UI` `3365ab0`) y el panel ya no carga `dataTables.semanticui.min.css` ni
  `responsive.semanticui.min.css` (JCC, `6deb9c40`); Responsive, solo si un listado lo pide (P254 a).
- **Toda tabla de DataTables nace `unstackable`** (`dataTables.semanticui.min.js`, `ext.classes.table`) y se desplaza: `.dt-layout-full` lleva `overflow-x: auto` en el tema. **Una tabla es una tabla** (PO, 2026-10-30; JCT). **`contentTablesStackable()`** (`admin-area/js/main.js`, escuchando `init.dt draw.dt` en el documento, puesto antes
  de que ningún `list.js` arranque) solo se la quita a `table.content-table-cards`, la opción, y pone en cada celda el `data-label` de su
  cabecera; a menos de 768 px el `thead` se oculta y cada fila es una tarjeta, con la etiqueta delante de cada dato salvo
  en la primera columna (título) y la última (acciones) (`#1264`). DataTables marca `dt-type-numeric` (a la derecha) las
  columnas numéricas: en la tarjeta se alinean a la izquierda. `dataTables.responsive.min.js` lo carga el layout y ningún
  listado lo usa (`responsive: false`).
- **La caja de un listado es `ui fluid card container-standard-table`** si la tabla pasa por DataTables (JCU, `5f83f3a1`): el `ui segment` metía la tabla en su relleno. Una tabla estática sigue en `ui segment`: su primera celda iría pegada al borde. DataTables envuelve la celda en `.cell-container`: `.row-actions` no es hijo directo del `td`.
- **Un error de formulario se pinta por defecto** (`genericFormHandler`, JCU): no se escribe un `onInvalidEvent` propio. Va compilado en `configurations.min.js`, que git ignora: sin `gulp js-vendor` se mide el código viejo. El prompt también es un `.ui.label`: `_placeholders.scss` lo excluye de su `display: none`.
- **`ui code segment` usa la fuente del cuerpo a propósito**: lo pide `design.md` §3.25.2 («sin monoespaciada»), no es defecto del fork. Para un bloque concreto, `<pre class="ui code segment"><code>…</code></pre>` sale monoespaciado, sin tocar el tema (medido por el agente del tema, 2026-10-30).
- **Dentro de una tabla, el tooltip es un popup** (`dataTablesTooltips()`, `admin-area/js/main.js`, en `draw.dt`): el `::after` de `data-tooltip` lo recortaría el desplazamiento. Quita `data-tooltip`, `data-position` y `data-inverted`: tras dibujar, esos atributos ya no están.
- **Las pestañas llevan su ARIA sin tocarlas** (`contentTabsAria()`, `admin-area/js/main.js`): `.ui.menu` con `.item[data-tab]`; las flechas saltan las pestañas ocultas.
- **`core/view-escapes` f2 busca el literal `name="password" value=""`**: en una pieza de contraseña, `name` va antes que `value` (JCT, `996d0b47`).
- **`render()` reformatea el HTML que devuelve**: una suite no busca literales de marcado crudo; usa `DOMDocument` (`core/image-card`; dos casos por literal pasaban SIN el escapado, JCW).
- **`genericFormHandler` con un elemento DOM cae en su selector por omisión**: pide `$(element)` (JCW).
- **`ui sticky`**: en una pestaña oculta mide cero, y `'destroy'` sin iniciar lo inicia: solo vive en la visible (`seo.js`, JCW).
- **Cropper repone la URL original al destruirse**: revocarla al `replace()` o al cerrar da `ERR_FILE_NOT_FOUND`; se suelta al abrir otra imagen (`ContentImageEditor.js`, JCW).
- **Antes de tocar una vista, busca las suites que leen sus ganchos** (`git grep` del selector y del `data-type` en `local-tests/`): `core/logs-trace-purged` y `core/login-attempts-organization` cayeron en JCX por un `$pieces` fuera del controlador y un `data-type` quitado; su lista previa no las tenía.
- **Una tabla nunca desplaza en vertical** (PO, 2026-10-31): desplaza la vista y la tabla crece; la horizontal, solo si no cabe. Una rejilla `ui grid` dentro de `.container-standard-table` desborda por sus márgenes negativos, enciende `overflow-x: auto` y `configMirrorScrollX()` enseña la barra espejo: no se usa `dom` propio con rejilla (H145).
- **Una tabla ancha a 390**: `ui basic fitted horizontal scrolling segment` con `ui unstackable table` (Importar, JCY).
- **`.ui.grid > .tinted.column` pisa `background-size`**: un fondo con imagen pide más especificidad (`import/_access.scss`, JCY).
- **`ui text` de Fomantic solo existe sobre `span`**: en un `div` no casa ninguna regla.
- **`th.buttons` lleva 75 px de relleno a cada lado** (`admin_app_base.scss:352`, de todos los listados): en la tabla nueva,
  `class-name="collapsing"`.
- **Los textos de `RowActions` los traduce quien llama**; la clase los escapa todos.
- **Un módulo sin `Statics` no puede servir sus hojas**: hace falta su clase de rutas con `staticRoute()` y
  `staticResolver()`, como `MySpaceRoutes` y, desde EO, `SettingsRoutes` (`/admin/configurations/statics/…`). Una vista de
  módulo que quiera pintar una pieza del panel la pide con el `HelperController` que le pasa su controlador
  (`$helpController`), porque `$this` pinta desde la carpeta del módulo (`#1291`).
- **Ningún marco dentro de otro** (PO, 2026-10-10): pestañas `ui secondary pointing menu` sin segmento alrededor, y los
  bloques (cifras, tabla, formulario, barra de guardar) como hermanos separados, nunca anidados; la barra de guardar,
  desde JBV, `div.actions` dentro del formulario (sin segmento).
- **Los gráficos, con Apache ECharts 5.5.1** (JDL, `249dd6ff`), dentro de un `ui chart` del tema (`chart-card.php`,
  `chartModifiers`; una dona, `tall` o más: `square` la deja a la izquierda en `.data` y corta las etiquetas, `#2342`).
  La leyenda es la nuestra, interactiva: `linkStatisticsChartLegend(chart, leyenda)` con la nativa declarada y oculta
  (`legend: {show: false}`): `legendToggleSelect` la usa para ocultar (en una dona funcionó también sin declararla,
  `#2342`; en las líneas, sin medir). Desde JDN acepta también una instancia de ApexCharts (compatibilidad hasta la
  `11.0.0`, H155), y `chart-card.php` sin `chartModifiers` pinta el contenedor de antes, sin `ui chart`. Y `resizeStatisticsChart(chart)` para la
  ventana y las pestañas (`statics/core/js/statistics-chart-legend.js`). **Toda tarta o dona pasa por
  `fitStatisticsChart(chart, interior)`** (JEW, `dd623d0d`): con `radius` al 100 % la porción resaltada (`emphasis.scaleSize`,
  5 px en ECharts 5.5.1) salía cortada en plano; el radio lo calcula en px en cada `resize()`, y el tooltip lleva los colores
  del tema resueltos al iniciar (no cambian si el sistema pasa a oscuro sin recargar: H204). **Trampa**: con etiquetas blancas dentro del aro,
  un aro fino deja salir el texto de las porciones pequeñas sobre la tarjeta blanca, donde no se ve.
- **Las piezas de estadísticas** (`view/panel/built-in/statistics/`, siete) **no traen su CSS**: la pantalla carga
  `statics/core/css/graphs-and-statistics.css` (el parcial `_graphs-and-statistics.scss`, compilado una vez en el
  núcleo). Sin esa hoja, la sección sale sin estilos (`#1285`, `#1289`).
- **La cabecera ensancha el título cuando hay como mucho una acción** (desde JBV, `twelve`/`four wide computer` y
  `ten`/`six wide tablet`, el patrón `cabecera-pagina` del tema; con dos o más, `eight`/`eight` y dieciséis en tableta).
  Medido antes con once y cinco: a 1440, `eight wide` da 29 caracteres por línea y `eleven`, 41 (`#1276`).
- **`ui grid` lleva margen de -1rem** (`plugins/semantic/components/grid.css:26`) para compensar el relleno de sus columnas:
  lo que sigue a un grid empieza justo donde acaba su texto. Desde JBV la cabecera lo corrige con un `ui hidden divider`
  detrás (el patrón del tema), no con CSS (`#1246`).
- **Fomantic no alinea por ancho de pantalla** (no hay «mobile left aligned»), y `middle aligned` pone las columnas en
  `flex-direction: column`, que estira los botones a todo el ancho. Desde JBV la cabecera es `bottom aligned` y su CSS
  propio de apilado salió; con 2.9.4, `ui stackable buttons` NO pone el botón a todo el ancho por debajo de 768 px (`#2120`
  H1; el patrón lo promete con el tema).
- **El lenguaje de marcado del panel (ADR 0069)**: componer con las piezas; la raíz de cada vista conserva
  `module-view-container` y su clase propia, y las clases que nombran una zona se quedan aunque no pinten (JBW conservó
  `routes-search`, `backup-tables`… y todos los `data-*`). Las vistas principales, sin migas (`design.md` §2.3 del prototipo).
- **Dos PUENTES de la fase (1), marcados en `_content-screens.scss`** (`#2119`): la miga lejos de `.topbar-switches` en
  escritorio, en una fila y la última con «…» (R1), y el primer `h1.ui.header` sin migas fuera del botón del menú a ≤800 px
  (R2), sobre `.ui-pcs.container-sidebar > .content`. Se van con el armazón (fase 2). R2 usa `:not()` con lista
  (Selectors 4). El de `ui primary` SALIÓ en JBX: desde
  `a506068a` el panel y el acceso cargan el tema nuevo (`statics/plugins/fomantic-piecesphp/`, biblioteca
  `fomantic-piecesphp` de `assets.php`, traído con `bin/traer-tema`, que no viaja; su origen en `ORIGEN.json`), y
  `containers.php` le publica `--accent` desde `main_brand_color` (desde JCP; antes, un puente en `ui-pcs.scss`). **Desde JEY**
  (`3fa39429`, `8e59d83e`, `662ad7f9`) se trae ENTERO (con `components/`: 224 archivos, 17 MB), se carga con `import_fomantic()`
  en todo el framework, también la zona pública, e `import_semantic()` (`statics/plugins/semantic/`) queda en desuso para los
  clones hasta la `11.0.0`. **`section-nav`** (`panel/built-in/content/section-nav.php`, JFE `c57b3d1c`): la navegación fija por secciones; la usan «Correos del sitio» y el manual; JS `contentSectionNav()` en `admin-area/js/main.js`, CSS en `admin_app_base.scss`. **Trampas (JFL, `7ede3c6b`)**: fijada es `position: fixed` y va después de la barra superior; con el `z-index` 20 del `ui sticky` (el de `header.bar`) tapaba los resultados de Ctrl K, que viven DENTRO del contexto de apilamiento de la barra: por eso lleva `z-index: auto` (`admin_app_base.scss`); el desplazamiento se mide desde el BORDE INFERIOR de la barra (`getBoundingClientRect().bottom`), no desde su alto, porque la franja de suplantación la baja 36 px; desde 992 px el menú tiene `max-height: calc(100vh - var(--section-nav-offset))` con desplazamiento propio, y `--section-nav-offset` lo publica el JS (no se actualiza el del sticky al redimensionar: H214 b). El tema no tiene variable de capas. **`import_fomantic_components([...])`** carga componentes sueltos con lo que su uso normal necesita, leído de `dependencias.json` del tema (H208, JFB, `77004625`): base `tokens.css` + `base-ui.css` (nunca `reset` ni `site`), el `cargar` de cada nombre, los opcionales solo pedidos por nombre, jQuery solo si hay JS; con `import_fomantic()` en la página no añade nada; las pruebas cambian el mapa con `set_config('fomantic_components_map', …)`. **Trampas**: escala con el `font-size` del `html` de la página (el tema mide en `rem`); `tokens.css` declara `color-scheme` en `:root`; **traer el tema con CSS nuevo deja rancio el censo de SCSS**: `bin/artefactos` antes de verificar (`#2479` H7). **Trampas**:
  una página que cargue `semantic` y `fomantic-piecesphp` rompe; `--accent-2` del tema no se deriva del color de
  marca; la fuente del panel la sigue poniendo el CSS propio (Poppins), no el tema.
- **`brand-color` pone `margin: 0`** (`ui-pcs.scss:346`) y anula el margen de Fomantic: dos botones así seguidos quedan
  pegados. Las acciones de fila se separan con `.row-actions { gap }` (`_content-screens.scss`, `#1250`).
- **La ventana no toca `genericFormHandler`**: le pasa `ignoreRedirection` e `ignoreReload` (`genericFormHandler.js:290-294`;
  sin ellos redirige a los 1.500 ms, `:516-560`) y en su `onSuccess` cierra y recarga las tablas con
  `$.fn.dataTable.tables({api:true}).ajax.reload(null, false)`. `form.reset()` no restaura un oculto cambiado con `.val()`:
  la pieza guarda su valor inicial (`#1268`).
- **Los atributos de las piezas pasan por `RowActions::attributes()`**, la única implementación del filtro (nombre limpio,
  ningún `on…`, valor escapado): el JS de un módulo sigue encontrando sus marcas (`save`, `add-translation`,
  `delete-publication-button`).
- **`get_breadcrumbs()` NO escapa** (`AppHelpers.php:3161`): quien pasa a las migas un texto del usuario lo escapa antes
  («Editar: <título>» en la edición de publicaciones).
- **Pestañas que se deslizan a menos de 768 px**: `.tabs-controls.content-tabs` (`_content-screens.scss`); las
  `.tabs-controls` sin la clase no cambian.
- **El `net::ERR_ABORTED` al editar una imagen con recortador es de Cropper.js y es inofensivo**: sin
  `checkOrientation: false` (`SimpleCropperAdapter.js:260`) pide la imagen por XHR para leer la orientación EXIF y aborta
  si no es JPEG (`cropper.min.js:10`). No se apaga: con fotos de móvil en JPEG, esa lectura es la que las endereza (`#1248`).
- **Una clave de traducción ES el texto español**: dentro de un grupo, «Inicio» como cabecera de columna se traduce igual
  que «Inicio» de las migas («Home»). Un texto que ya existe con otro sentido pide otra redacción (`#1244`).

## Patrones nacidos en el banco de correos (JEU, 2026-11-05)

- **Navegación fija de una página larga**: `ui vertical menu` dentro de `ui sticky` del tema a 992 px o más; por debajo, un
  desplegable en una columna con `position: sticky` (el `ui sticky` no sigue a la columna cuando la rejilla se apila); `scroll-margin`
  para que el ancla no quede bajo la barra. Hoy vive en `MailCatalog/Statics/`; pasa a pieza reutilizable con H200.
- **Vista previa de un correo**: `<iframe srcdoc sandbox="allow-same-origin">` sin `allow-scripts`, con el alto de su contenido:
  nunca una barra vertical propia (criterio del PO, 2026-10-31). Pintar un correo a mitad de página borra los assets globales (H89):
  `MailCatalog::preview()` guarda y repone las claves de la página.
- **El «⋯» de las acciones de fila lo inicia y lo coloca `contentRowActionsMenus()`** (`src/statics/admin-area/js/main.js`, JEV,
  `f1cd5fbc`): al ponerse `active`, el `.menu` sale con `position: fixed` junto a su botón (hacia arriba si abajo no cabe); se
  recoloca al desplazar y se cierra si su botón deja de verse. **La barra vertical la ponía `div.dt-layout-cell.dt-layout-full`**
  (el `overflow-x` de DataTables), no solo `.container-standard-table`; no se arregla con `overflow: visible`, que quita el
  desplazamiento horizontal. Límites: dentro de una `ui modal` (`will-change: transform`) `fixed` no es relativo a la ventana;
  sin `max-height` si no cabe ni arriba ni abajo. La medida: `bin/tools/navegador/mide-acciones-fila.js` con `bin/navegador
  --mide` (abre el «⋯» de la última fila); hoy solo Organizaciones pinta un «⋯» (4 acciones).

## Lo que manda al revisar: NUESTRO PANEL (P300, 2026-11-09)

Hasta el 2026-11-09 mandaba «la guía del tema > sus escaparates > el prototipo» (fase de rediseño). **Desde P300 manda el
panel implementado**, y el tema se compara contra él:

- **Ningún agente cambia el panel para parecerse a un escaparate.** Los escaparates de PiecesPHP
  (`Fomantic-UI/examples/catalogo/paginas/escaparate-piecesphp-*.html`) los pone al día el agente del tema replicando el
  panel, y pueden ir por detrás (H220: la comprobación que lista los desfasados).
- **La excepción son los errores evidentes del panel** (texto ilegible, contraste roto, algo desalineado o cortado, y lo
  que el prototipo pedía y no se implementó, como la insignia «Activa» del 2FA en el menú de usuario): no se copian y se
  corrigen aquí. Lo que el escaparate tenga mejor por gusto es una **propuesta**: decide el PO.
- **El patrón del tema sigue mandando sobre CÓMO se compone** (ADR 0069; un defecto propio se sustituye por el tema, §6):
  un botón destructivo es `ui basic negative button`, no un rojo sólido (el texto negro sobre `#f04438` es del tema y a
  propósito, por contraste AA; el `DESIGN.md` del tema admite también `ui negative button`, pero sus patrones usan el
  básico, y ese es el nuestro); cancelar o neutro, `ui basic button`; `confirmAction(…, 'red')` ya pinta el básico negativo
  (JFM, `8f5c22c1`).
- **Un desplegable de formulario lo pinta el tema entero**: la vieja «sobreescritura de formulario fomantic» de
  `admin_app_base.scss` (texto en `position: absolute`, `flex` en los múltiples) metía el texto bajo la flecha y dejaba una
  línea vacía en los múltiples; salió en JFP (`e39e8adf`). Un `<select class="ui dropdown">` que nadie inicia con
  `.dropdown()` sale nativo (el salto de «Correos del sitio» lo era); al iniciarlo, el envoltorio del tema no copia el
  `aria-label` del `select`: hay que copiarlo (H224).
- **Cada `bin/traer-tema` lleva capturas antes y después, a 1440 Y a 390** (la de JFO fue solo a 1440 y dejó pasar una
  regresión de móvil en la cabecera de página), de la hoja de diseño (etiquetas de estado, piezas) y de las
  pantallas que cambian: el punto de las etiquetas de estado partido en celdas estrechas entró con una traída y nadie lo
  vio. El tema compila en vivo: lo que se verifica es lo de `ORIGEN.json`, con `sin_commitear` 0.
