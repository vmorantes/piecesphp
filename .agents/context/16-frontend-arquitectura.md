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

Anidamiento resultante:

```
body.<$bodyClasses>                                   panel/layout/header.php:38
├─ div.ui.modal[support-js]                           header.php:41
├─ (topbar, salvo $noTopBar = true)                   header.php:67-68 → panel/layout/topbar.php
│  ├─ div.ui.bottom.fixed.nag[system-alert-nag][data-key]  topbar.php:229, uno por aviso (SystemAlertRegistry::nagsFor(), :225)
│  ├─ div.ui-pcs.topbar-switches                      topbar.php:235
│  │  └─ .topbar-toggle: user-options :236, notifications-options :251, admin-options :259, exit-as-logged-options :267
│  ├─ div.topbar-content.close (usuario :275, noticias :348, administrativo :366)
│  └─ modales del sistema: noticias :524, idioma :534, «conectarse como» :556; toast de borrado de cuenta :607
└─ div.ui-pcs.container-sidebar                       header.php:71
   ├─ aside[main-aside].ui-pcs.sidebar > div.content  panel/layout/menu.php:8 y :14 (desplazamiento PROPIO)
   └─ div.content.<$containerClasses>                 header.php:90 (desplazamiento de la pantalla)
      └─ LA VISTA DEL MÓDULO → section.module-view-container
footer.php cierra .content y .container-sidebar (:2-3) y carga el JS (:4-15)
```

Líneas medidas el 2026-10-09 con `grep -n`.

**La página no se desplaza: se desplazan dos cajas.** `.container-sidebar > .content` lleva `overflow-y: auto` y
`height: 100vh` (`src/statics/core/sass/includes/components/_container-aside.scss:17-22`), y el menú lateral tiene su
propio desplazamiento. Por eso una captura con `fullPage` sale del alto de la ventana. Los paneles `.topbar-options`
cerrados también se desplazan, pero miden 0 px de ancho y quedan fuera de la ventana: quien mida alturas tiene que
filtrar por caja visible (lo hace `bin/navegador`, §8).

Variantes sin lateral: `panel/layout/header-no-sidebar.php` y `footer-no-sidebar.php`.

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
| Un mensaje de éxito o error tras una acción | `successMessage` / `warningMessage` / `infoMessage` / `errorMessage` (`helpers.js:15/87/159/231`): un `toast` de Fomantic arriba al centro | cualquier `*-form.js` |
| Un aviso permanente del sistema | `ui bottom fixed nag` con `system-alert-nag` y `data-key`, uno por aviso que registre `SystemAlertRegistry` (`topbar.php:225-229`; el cierre se recuerda por clave, `admin-area/js/main.js:169-174`). Hoy, el de la `app_key` de relleno (`app-key-placeholder`); otro uso se habla antes con el PO | `topbar.php:229` |
| Un modal | `div.ui.modal[<atributo>]` y `$(sel).modal(...).modal('show')`; no hay helper genérico | `topbar.php:534` y `configurations.js:1539-1556` |
| Subir y recortar una imagen | `modalImageUploaderForCropperAdminViews()` (`AppHelpers.php:2961`) | `topbar.php:577` |
| Una tabla vacía | El texto `emptyTable`/`zeroRecords` de DataTables (`statics/core/js/translations/es.js:100/111`); en tarjetas, `helpers.js:1055` | — |
| Un 403/404 | `forbiddenHandler`/`notFoundHandler` (`config/containers.php:227`, `:165`) pintan `pages/403` y `pages/404`, **sin el layout del panel**; JSON si la petición es XHR | `src/app/view/pages/404.php` |

## 4. Piezas que despliega el sistema

- **Lateral de usuario:** no es `ui sidebar`: es `.topbar-content` + `.topbar-options.user-options`
  (`topbar.php:275` y `:287`), abierto y cerrado por JS propio (`pcsAdminTopbars()`, `configurations.js:813`), CSS en
  `includes/components/_topbar.scss`.
- **Cambio de idioma:** opción con `change-system-lang-trigger` (un `MenuGroup` de `topbar.php:43-52`) y modal (`:534`, solo con varios
  idiomas). `handleGlobalChangeLangModal` escribe la cookie `cookie_lang_definer` y recarga: no hay ruta.
- **Conectarse como otro usuario (solo root):** modal `connect-as-another-user-modal` (`topbar.php:556`) con
  búsqueda en la ruta `users-search-dropdown`; `handleConnectAsAnotherUser` (`configurations.js:1564`) escribe
  la cookie `asUserID` y recarga; el servidor lo resuelve en `src/index.php:548-573`. Salir:
  `.topbar-toggle.exit-as-logged-options` (`topbar.php:267`). Líneas del §4 medidas el 2026-10-09.

## 5. Estética: lo que ya hay

- **Colores y fuentes son variables CSS generadas en PHP**, no `:root` en SCSS (no hay ninguno): `CSSVariables`
  en `src/app/config/containers.php:41-69` (`main-brand-color`, `second-brand-color`, `font-color-one/two`,
  `menu-color-*`, `body-gradient`, `loader-*`, `font-family-global`…), servidas en la ruta
  `admin-global-variables-css`. En SCSS se leen por `includes/_variables.scss:32-36`.
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
  CSS propio va solo donde Fomantic no llega, y se justifica en la instrucción (PO, 2026-09-15).
- **Componentes fuera del sistema** (otra librería de UI, un modal a mano sin `ui modal`).
- **Una vista del panel fuera del esqueleto** sin decirlo: sin `section.module-view-container` dentro de `.content`.
- **Estilos en línea** para maquetar (`style="max-width…"`).
- **Toda instrucción que toque una vista del panel nombra el componente de Fomantic que usa y dice si es descartable
  y si se puede apagar** (PO, 2026-09-15). Sin eso, no sale.

- **Preguntar con `window.confirm`, `alert` o `iziToast.question`**: se pregunta con `confirmAction()`
  (`helpers-lib/confirmAction.js`) y se avisa con `errorMessage()`/`successMessage()`. Lo vigila la comprobación 50 de
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
- **Usuarios** (`src/app/view/usuarios/`): `form.php`, `select-type-user.php` y los formularios por tipo (18).
- **FileManager**: `file-manager.php` (con estilo en línea) y `file-manager-rich-editor.php`.

Los parciales (`panel/built-in/utilities/*`, `util/*`, `map-elements`) no son páginas y no cuentan. **Alinear esas
vistas no es un lote hasta que el PO lo decida**; su objetivo, si llega, es «parecerse un poco más», no copiar
Publications.

## 8. Capturas del panel

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
| Cabecera | `panel/built-in/content/page-header.php` (title, description, breadcrumbs como ARREGLO —la forma de `get_breadcrumbs()`—, que escapa y pinta con `ui breadcrumb`; un string → `E_USER_DEPRECATED` y sin migas; primaryAction, secondaryActions; cada acción con `attributes`) | `ui stackable grid` + `ui button` / `ui basic button` | No |
| Acciones de fila | `PiecesPHP\Core\Utilities\Helpers\RowActions` (`action()`, `delete()`, `cell()`, `attributes()`): hasta `MAX_VISIBLE` = 3 acciones, todas a la vista (la directa `brand-color`, las demás `brand-color alt`, P125); con más, la directa y un «⋯» | `ui icon button` + `ui dropdown` [row-actions-menu] | Sí |
| Sin registros | `panel/built-in/content/empty-state.php` | `ui placeholder segment` | Sí |
| Ventana de formulario | `panel/built-in/content/form-modal.php` + `contentModals()` (`admin-area/js/main.js`): el disparador `[data-content-modal="<id>"]`, con `data-content-modal-values` (JSON) para editar; `data-content-modal-action` y `-title` cambian acción y título | `ui modal` | Sí: una categoría compleja (color, imagen) va en página, como las de noticias |
| Barra de guardar | `panel/built-in/content/form-action-bar.php` (`saveAttributes`; cada secundaria con `attributes`, `type` y `danger`, que la pinta roja a la derecha); el `form` nace `ui form initial` con `data-content-form` | `ui segment` al final del formulario, **sin fijar** (el PO, 2026-10-09: «flotante no; mantengámoslo simple»); sus reglas propias son solo las del móvil | No |

**Trampas medidas** (`#1234`-`#1240`):
- **El rosa de lo obligatorio es de Fomantic** (`.ui.form:not(.initial) … input:invalid`, `plugins/semantic/components/form.css:560`).
  Se apaga con la clase `initial`, y `contentFormsInitial()` (`admin-area/js/main.js`) la quita al intentar guardar.
  Solo en los formularios con `data-content-form`: los viejos siguen naciendo rosa.
- **Toda tabla de DataTables nace `unstackable`** (`dataTables.semanticui.min.js`, `ext.classes.table`): no se apila en el
  móvil. **`contentTablesStackable()`** (`admin-area/js/main.js`, escuchando `init.dt draw.dt` en el documento, puesto antes
  de que ningún `list.js` arranque) se la quita solo a `table.content-table` y pone en cada celda el `data-label` de su
  cabecera; a menos de 768 px el `thead` se oculta y cada fila es una tarjeta, con la etiqueta delante de cada dato salvo
  en la primera columna (título) y la última (acciones) (`#1264`). DataTables marca `dt-type-numeric` (a la derecha) las
  columnas numéricas: en la tarjeta se alinean a la izquierda. `dataTables.responsive.min.js` lo carga el layout y ningún
  listado lo usa (`responsive: false`).
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
  en su propio segmento (el patrón de `site-files.php`).
- **La leyenda de un gráfico es la nuestra, y es interactiva**: `linkStatisticsChartLegend(chart, leyenda)`
  (`statics/core/js/statistics-chart-legend.js`); la nativa de ApexCharts, apagada. **Trampa**: en tarta y donut,
  `toggleSeries()` de ApexCharts 3.50.0 no hace nada visible; la función usa `updateSeries()` (`#1312`).
- **Las piezas de estadísticas** (`view/panel/built-in/statistics/`, siete) **no traen su CSS**: la pantalla carga
  `statics/core/css/graphs-and-statistics.css` (el parcial `_graphs-and-statistics.scss`, compilado una vez en el
  núcleo). Sin esa hoja, la sección sale sin estilos (`#1285`, `#1289`).
- **La cabecera ensancha el título cuando hay como mucho una acción** (`eleven`/`five wide computer`; con dos o más,
  `eight`/`eight`): a 1440, `eight wide` da 29 caracteres por línea y `eleven`, 41 (`#1276`).
- **`ui grid` lleva margen de -1rem** (`plugins/semantic/components/grid.css:26`) para compensar el relleno de sus columnas:
  lo que sigue a un grid empieza justo donde acaba su texto. La cabecera lo corrige con su clase `content-page-header`
  (`#1246`).
- **Fomantic no alinea por ancho de pantalla** (no hay «mobile left aligned»), y `middle aligned` pone las columnas en
  `flex-direction: column`, que estira los botones a todo el ancho. Por debajo de 992 px la cabecera apila sus botones a
  la izquierda con CSS propio (`_content-screens.scss`).
- **`brand-color` pone `margin: 0`** (`ui-pcs.scss:346`) y anula el margen de Fomantic: dos botones así seguidos quedan
  pegados. Las acciones de fila se separan con `.row-actions { gap }` (`_content-screens.scss`, `#1250`).
- **La barra, a menos de 768 px, oculta el texto de una secundaria solo si lleva icono** (`.icon + .form-action-text`):
  una sin icono conserva su texto. El texto sigue en `title` y `aria-label`.
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

