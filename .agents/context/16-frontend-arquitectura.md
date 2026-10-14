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
