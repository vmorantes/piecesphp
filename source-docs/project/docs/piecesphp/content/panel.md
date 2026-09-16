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

Un listado (`src/app/classes/Publications/Views/publications/list.php`):

```html
<section class="module-view-container">
    <div class="breadcrumb"><?= $breadcrumbs ?></div>
    <div class="limiter-content">
        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <div class="description"><?= $description ?></div>
        </div>
        <div class="main-buttons">
            <a class="ui button brand-color" href="…">…</a>
        </div>
        <div class="mirror-scroll-x">
            <div class="container-standard-table">
                <table url="…" class="ui basic table">…</table>
            </div>
        </div>
    </div>
</section>
```

Un formulario (`…/publications/forms/add.php`) lleva la misma cabecera y después
`form.ui.form > div.container-standard-form`, con los campos en `.field` (`.required` si lo son) y el botón
`button.ui.button.brand-color[save]`.

## Qué usar en cada caso

| Necesito… | Se hace con |
| :-- | :-- |
| Una tabla paginada por el servidor | `table[url].ui.basic.table` + `dataTableServerProccesing()` (`statics/core/js/helpers.js`) |
| Enviar un formulario | `form.ui.form` + `genericFormHandler()` |
| Botón primario / secundario | `ui button brand-color` / `ui button brand-color alt` |
| Pestañas | `.tabs-controls` + `ui tab` con `[data-tab]` |
| Un desplegable | `ui dropdown` (con `search` si filtra) |
| Una fecha | el atributo `calendar-js` en el campo |
| Avisar del resultado de una acción | `successMessage()`, `warningMessage()`, `infoMessage()`, `errorMessage()`: un *toast* arriba al centro |
| Un modal | `div.ui.modal` y `$(selector).modal('show')` |
| Subir y recortar una imagen | `modalImageUploaderForCropperAdminViews()` |
| Traducir un texto fijo en HTML | el atributo `lang-group="<grupo>"` |

## Estética

- **Los colores y fuentes de marca son variables CSS** que se generan desde la configuración
  (`main-brand-color`, `second-brand-color`, `font-color-one`…). Úsalas; no pongas colores fijos.
- **Fomantic primero.** CSS propio solo donde Fomantic no llega, y con motivo.
- **Nada de estilos en línea** para maquetar ni componentes de otra librería de interfaz.

## Piezas que pone el sistema

No las reimplementes en tu módulo: ya están en la barra superior.

- **Lateral de usuario**: perfil, idioma, salir.
- **Cambio de idioma**: guarda la elección en una cookie y recarga.
- **Conectarse como otro usuario** (solo root): para ver el panel con los permisos de otro; se sale desde la misma barra.
