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
body.<$bodyClasses>                                   panel/layout/header.php:36
├─ div.ui.modal[support-js]                           header.php:38
├─ (topbar, salvo $noTopBar = true)                   header.php:64-66 → panel/layout/topbar.php
│  ├─ div.ui.bottom.fixed.nag[app-key-placeholder-nag]  topbar.php:324
│  ├─ div.ui-pcs.topbar-switches                      topbar.php:330
│  │  └─ .topbar-toggle: user-options, notifications-options, admin-options, exit-as-logged-options
│  ├─ div.topbar-content (usuario, noticias, administrativo)   topbar.php:370, :443, :461
│  └─ modales del sistema: noticias :619, idioma :629, «conectarse como» :651; toast de borrado de cuenta :702
└─ div.ui-pcs.container-sidebar                       header.php:68
   ├─ aside[main-aside].ui-pcs.sidebar                panel/layout/menu.php:8
   └─ div.content.<$containerClasses>                 header.php:87
      └─ LA VISTA DEL MÓDULO → section.module-view-container
footer.php cierra .content y .container-sidebar (:2-3) y carga el JS (:4-15)
```

Variantes sin lateral: `panel/layout/header-no-sidebar.php` y `footer-no-sidebar.php`.

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
| Un desplegable | `ui dropdown` (con `search` si filtra) y `configFomanticDropdown()` | `topbar.php:651` y `configurations.js:1568` |
| Un mensaje de éxito o error tras una acción | `successMessage` / `warningMessage` / `infoMessage` / `errorMessage` (`helpers.js:15/87/159/231`): un `toast` de Fomantic arriba al centro | cualquier `*-form.js` |
| Un aviso permanente del sistema | `ui bottom fixed nag`. **Hoy solo el de `app_key`** (`topbar.php:318-327`, `admin-area/js/main.js:208-221`); otro uso se habla antes con el PO | `topbar.php:324` |
| Un modal | `div.ui.modal[<atributo>]` y `$(sel).modal(...).modal('show')`; no hay helper genérico | `topbar.php:629` y `configurations.js:1537-1556` |
| Subir y recortar una imagen | `modalImageUploaderForCropperAdminViews()` (`AppHelpers.php:2925`) | `topbar.php:679` |
| Una tabla vacía | El texto `emptyTable`/`zeroRecords` de DataTables (`statics/core/js/translations/es.js:100/111`); en tarjetas, `helpers.js:1055` | — |
| Un 403/404 | `forbiddenHandler`/`notFoundHandler` (`config/containers.php:227`, `:165`) pintan `pages/403` y `pages/404`, **sin el layout del panel**; JSON si la petición es XHR | `src/app/view/pages/404.php` |

## 4. Piezas que despliega el sistema

- **Lateral de usuario:** no es `ui sidebar`: es `.topbar-content` + `.topbar-options.user-options`
  (`topbar.php:370-437`), abierto y cerrado por JS propio (`configurations.js:824-870`), CSS en
  `includes/components/_topbar.scss`.
- **Cambio de idioma:** opción con `change-system-lang-trigger` (`topbar.php:43-51`) y modal (`:629`, solo con varios
  idiomas). `handleGlobalChangeLangModal` escribe la cookie `cookie_lang_definer` y recarga: no hay ruta.
- **Conectarse como otro usuario (solo root):** modal `connect-as-another-user-modal` (`topbar.php:651`) con
  búsqueda en la ruta `users-search-dropdown`; `handleConnectAsAnotherUser` (`configurations.js:1564-1586`) escribe
  la cookie `asUserID` y recarga; el servidor lo resuelve en `src/index.php:595-620`. Salir:
  `.topbar-toggle.exit-as-logged-options` (`topbar.php:362`).

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

- **Configuración** (`src/app/view/panel/pages/app_configurations/`): `backgrounds`, `configurations`, `email`,
  `logos-favicons`, `os-ticket`, `security-and-ia` y `seo` abren con `<main class="…">`; `routes` con
  `div.container`.
- `panel/pages/about-framework.php` (`main`), `dashboard.php` (`div.banner-zone`), `test-cropper.php`,
  `generic_token/*`.
- **Usuarios** (`src/app/view/usuarios/`): `form.php`, `select-type-user.php` y los formularios por tipo (18).
- **FileManager**: `file-manager.php` (con estilo en línea) y `file-manager-rich-editor.php`.

Los parciales (`panel/built-in/utilities/*`, `util/*`, `map-elements`) no son páginas y no cuentan. **Alinear esas
vistas no es un lote hasta que el PO lo decida**; su objetivo, si llega, es «parecerse un poco más», no copiar
Publications.
