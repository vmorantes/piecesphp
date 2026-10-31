# Pantallas de contenido: las piezas del panel

Desde la `9.0.0`, las pantallas de gestión del panel (un listado, su alta y su edición) se arman con **unas pocas
piezas comunes**, en lugar de que cada módulo dibuje sus botones, su cabecera y su tabla a su manera. Publicaciones,
noticias, documentos y sus categorías ya están hechas así; son el ejemplo que copiar.

Las piezas se ven funcionando, con su código, en la **hoja de diseño** del panel (en el menú de la rueda, junto a «Acerca
de»; la ven root y el administrador general).

> [!NOTE]
> **Provisional.** Esta página crece con el rediseño del panel. La lista completa de componentes, con su ejemplo vivo,
> está en la **hoja de diseño**; esta página explica cómo usar las piezas desde un módulo.

## La regla

**Una pantalla se compone de estas piezas; lo que falte, se añade a la pieza, no a la pantalla.** Y nada de CSS propio
donde Fomantic llega: cada pieza se apoya en un componente de Fomantic y solo lleva el CSS mínimo que Fomantic no da.

| Pieza | Archivo | Se apoya en | ¿Se puede prescindir de ella? |
| :-- | :-- | :-- | :-- |
| Cabecera | `src/app/view/panel/built-in/content/page-header.php` | `ui breadcrumb`, `ui header`, `ui grid`, `ui buttons` | No: es el armazón |
| Acciones de cada fila | `PiecesPHP\Core\Utilities\Helpers\RowActions` | `ui icon button`, `ui dropdown` | Sí |
| Etiqueta de estado | `PiecesPHP\Core\Utilities\Helpers\StatusLabel` | `ui basic circular label` | Sí |
| Contraseña con ojo | `src/app/view/panel/built-in/content/password-field.php` | `ui action input` | Sí |
| Título de sección | `src/app/view/panel/built-in/content/section-header.php` | `ui header` | Sí |
| Interruptor con ayuda | `src/app/view/panel/built-in/content/toggle-field.php` | `ui toggle checkbox` | Sí |
| Tarjeta de imagen y su editor | `src/app/view/panel/built-in/content/image-card.php` y `src/app/view/panel/built-in/content/image-editor.php` | `ui horizontal card`, `ui fullscreen modal`, `ui slider` | Sí |
| Filtrar y Limpiar | `src/app/view/panel/built-in/content/filter-actions.php` | `ui button` | Sí |
| Tarjeta de cifra | `src/app/view/panel/built-in/statistics/statistic-card.php`, con `figure` | `statistic card` del tema | Sí |
| Aviso de «aún no hay…» | `src/app/view/panel/built-in/content/empty-state.php` | `ui placeholder segment` | Sí |
| Barra de guardar | `src/app/view/panel/built-in/content/form-action-bar.php` | `.actions` del `ui form` (pieza del tema nuevo) | No |
| Ventana de formulario | `src/app/view/panel/built-in/content/form-modal.php` | `ui modal` | Sí: lo complejo va en su página |
| Tabla de listado | la clase `content-table` en la tabla de DataTables | `ui table` | No |

## La cabecera

Migas, título, una frase que dice para qué sirve la pantalla, **una sola acción principal** y las demás como botones
básicos. Desde la vista del módulo:

```php
<?php
$this->helpController->_render('panel/built-in/content/page-header.php', [
    'breadcrumbs' => [
        __($langGroup, 'Inicio') => ['url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('')],
        __($langGroup, 'Publicaciones'),
    ],
    'title' => $title,
    'description' => __($langGroup, 'Lo que se publica en el sitio, con su categoría y su fecha.'),
    'primaryAction' => $hasPermissionsAdd
        ? ['text' => __($langGroup, 'Nueva publicación'), 'url' => $addLink, 'icon' => 'plus']
        : null,
    'secondaryActions' => $hasPermissionsListCategories
        ? [['text' => __($langGroup, 'Categorías'), 'url' => $listCategoriesLink, 'icon' => 'tags']]
        : [],
]);
?>
```

- **Las migas van como arreglo**, el mismo que acepta `get_breadcrumbs()`: `'Texto' => ['url' => …]` para una con
  enlace y `'Texto'` para una sin él. La pieza las pinta con `ui breadcrumb` de Fomantic, **escapa cada texto y cada
  URL**, y la última sale como la activa, recortada con «…» si no cabe y con el texto completo al pasar el ratón. Un
  texto que escribió un usuario (el título de lo que se edita) se pasa **tal cual**: si lo escapas antes, se verá
  `&amp;`. Desde la 9.0.0, pasarle el HTML de `get_breadcrumbs()` no pinta migas y deja un aviso (`E_USER_DEPRECATED`),
  que en local detiene la página.
- **El título y la frase también los escapa la pieza.**
- **`attributes`** en cualquier acción añade atributos al enlace (`target`, `rel`, o la marca que espere tu JavaScript).
  Pasan por el mismo filtro que las acciones de fila: el valor se escapa y un nombre que empiece por `on` se descarta.

## La tabla del listado

La tabla de DataTables lleva la clase **`content-table`** y la columna de acciones, **`class-name="collapsing"`**, que
la deja ocupar solo lo que necesita (lo mismo conviene en las columnas cortas: estado, fechas, contadores):

```php
<table url="<?= $processTableLink; ?>" class="ui basic table content-table">
    <thead>
        <tr>
            <th><?= __($langGroup, 'Título'); ?></th>
            <th class-name="collapsing"><?= __($langGroup, 'Estado'); ?></th>
            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
        </tr>
    </thead>
</table>
```

**En el móvil, una tabla es una tabla**: se desplaza en horizontal y conserva el orden. Si un listado se lee mejor como
tarjetas, añade la clase **`content-table-cards`**: cada fila pasa a ser una tarjeta, con el nombre de cada dato delante, y
la primera columna (el título) y la última (las acciones) van sin etiqueta. Se pierde el orden por columna en el móvil.

El estado de una fila se pinta con `StatusLabel`, nunca con un `ui <color> label` relleno, que saldría con el texto negro:

```php
<?php
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;

$columns[] = StatusLabel::html($e->visibilityText, StatusLabel::POSITIVE); //POSITIVE, NEGATIVE, WARNING o INFO
$columns[] = StatusLabel::neutral($e->roleName); //lo que no es un estado: un rol, un código
?>
```

El texto llega traducido y la pieza lo escapa. Para el texto de ayuda del buscador, `data-search-placeholder` en la tabla:

```php
<table url="<?= $processTableLink; ?>" class="ui basic table content-table" data-search-placeholder="<?= __($langGroup, 'Nombre, correo o rol'); ?>">
```

Lo que se reconoce va primero: el título enlazado a su edición y, debajo y en pequeño, su categoría. El número de
registro no ocupa columna; se puede seguir buscando por él poniéndolo en el orden de búsqueda de esa columna:

```php
$columnsOrder = [
    ['title', "{$table}.id", 'categoryName'], //la columna del título busca también por número y por categoría
    'visibilityText',
];
```

## Las acciones de cada fila

`RowActions` devuelve el HTML de la celda de acciones. **Hasta tres acciones se ven todas, como botones con el color de
marca**: la directa (editar) en el principal y las demás en el secundario. **Con más de tres**, se ve la directa y las
demás van a un menú «⋯».

```php
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$direct = $hasEdit
    ? RowActions::action(__(self::LANG_GROUP, 'Editar'), self::routeName('forms-edit', ['id' => $e->id]), 'edit')
    : null;

$others = [];
if ($hasPreview) {
    $others[] = RowActions::action(__(self::LANG_GROUP, 'Ver en el sitio'), $publicLink, 'external alternate', [
        'target' => '_blank',
        'rel' => 'noopener',
    ]);
}
if ($hasDelete) {
    $deleteLink = self::routeName('actions-delete', ['id' => $e->id]);
    $others[] = RowActions::delete(__(self::LANG_GROUP, 'Eliminar'), $deleteLink, __(self::LANG_GROUP, '¿Seguro de eliminar el elemento?'), [
        'delete-publication-button' => '', //la marca que busca el JavaScript de borrado del módulo
        'data-route' => $deleteLink,
    ]);
}

$columns[] = RowActions::cell($direct, $others);
```

- **Los textos los traduces tú** (con `__()`) y **la clase los escapa todos**.
- `RowActions::primary()` pinta la acción principal de la fila, si la tiene: una sola.
- `RowActions::inactive(__(self::LANG_GROUP, 'Eliminar'), 'trash alternate')` pinta una acción que no aplica a esta fila: se
  ve, enseña «No aplica» y no hace nada al pulsarla.
- `RowActions::text(__(self::LANG_GROUP, 'Ver cuerpo'), $url)` pinta un botón de texto.
- `RowActions::delete()` no borra nada: pinta el botón. El borrado lo sigue haciendo el JavaScript de tu módulo, que
  encuentra el botón por la marca que le pases en los atributos.
- Si alguna fila puede llegar a tener más de tres acciones, inicia el menú en cada dibujado de la tabla, en el
  `drawCallback` de tu `list.js`: `$('[row-actions-menu]').dropdown({ action: 'hide' })`.

## El aviso de «aún no hay…»

Para cuando no hay ningún registro todavía (no para una búsqueda sin resultados, que ya avisa DataTables):

```php
<?php
$this->helpController->_render('panel/built-in/content/empty-state.php', [
    'text' => __($langGroup, 'Aún no hay noticias.'),
    'description' => __($langGroup, 'Cuando agregue la primera, aparecerá en este listado.'),
    'icon' => 'newspaper outline',
    'action' => ['text' => __($langGroup, 'Nueva noticia'), 'url' => $addLink, 'icon' => 'plus'],
]);
?>
```

`description` y el `icon` de la acción son opcionales.

## El formulario de alta y edición, y su barra

El `form` nace con la clase **`initial`** y el atributo **`data-content-form`**: así un campo obligatorio vacío no sale
en rojo al abrir la página, sino solo cuando se intenta guardar. La barra va **dentro del `form`, al final**:

```php
<form method="POST" action="<?= $action; ?>" class="ui form initial news" data-content-form>

    <!-- los campos -->

    <?php
    $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
        'saveAttributes' => ['save' => ''], //la marca que busca el JavaScript del formulario
        'secondaryActions' => [
            ['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times'],
            ['text' => __($langGroup, 'Eliminar'), 'type' => 'button', 'icon' => 'trash alternate', 'danger' => true,
                'attributes' => ['delete-news-button' => '', 'data-route' => $deleteRoute]],
        ],
    ]);
    ?>

</form>
```

- **El orden es fijo**: primero las peligrosas (`danger`, como `ui basic negative button`), luego las secundarias
  (`ui basic button`) y **«Guardar» la última, a la derecha** (`ui primary button`).
- **La barra es un `<div class="actions">` dentro del formulario**: el tema del panel la pinta alineada a la derecha y
  separada de los campos por una línea, sin caja alrededor.
- **Eliminar es `type="button"`**, nunca `submit`: si el JavaScript no cargara, un `submit` guardaría el formulario en vez
  de borrar.
- **Cada botón conserva su texto también en el móvil** (y su `title` y `aria-label`).

## Secciones, interruptores y lecturas en vivo

Un bloque con varias partes separa cada una con su título y un `ui section divider`:

```php
<?php
$this->helpController->_render('panel/built-in/content/section-header.php', [
    'title' => __($langGroup, 'Servidor'),
    'description' => __($langGroup, 'Desde dónde sale el correo.'),
]);

$this->helpController->_render('panel/built-in/content/toggle-field.php', [
    'inputName' => 'autoTls',
    'label' => __($langGroup, 'Auto TLS'),
    'checked' => $autoTls,
    'description' => __($langGroup, 'Cifra la conexión si el servidor lo admite.'),
    'id' => 'auto-tls',
]);
?>
```

El interruptor lo inicia el JavaScript de tu pantalla, con `.checkbox()`. El nombre del campo es `inputName`, nunca `name`.

Para enseñar lo que significa una cifra mientras se escribe, marca el campo con `data-live-reading` (`days` o `seconds`) y pon
el destino junto a él:

```html
<input type="number" id="session-days" name="days" data-live-reading="days" data-live-reading-zero="Sin caducidad">
<span data-live-reading-for="session-days"></span>
```

## La tarjeta de imagen y su editor

**Dentro de un formulario**, usa `panel/built-in/content/image-field.php`: pinta la tarjeta como campo y tu JavaScript añade el archivo al enviar.

```php
$this->helpController->_render('panel/built-in/content/image-field.php', [
    'card' => ['id' => 'main-image', 'field' => 'mainImage', 'title' => __($langGroup, 'Imagen principal'), 'width' => 800, 'height' => 600, 'required' => true],
]);
```

```js
const files = {}
new ContentImageEditor().onApply('main-image', (file) => { files.mainImage = file })
genericFormHandler(selector, { onSetFormData: (formData) => {
	if (files.mainImage) { formData.set('mainImage', files.mainImage) }
	return formData
} })
```

Toda imagen configurable (un logo, un fondo, una foto) va con la misma tarjeta: vista previa, medida, «Cambiar imagen» y
«Descargar / ver». Al elegir un archivo se abre el editor; el recuadro es fijo y lo que se mueve es la imagen.

En el controlador, `import_cropper()`. En la vista, las tarjetas en su rejilla y **un solo editor por página**, fuera de
cualquier formulario:

```php
<div class="ui two stackable horizontal cards">
    <?php
    $this->helpController->_render('panel/built-in/content/image-card.php', [
        'id' => 'logo-card',
        'title' => __($langGroup, 'Logo'),
        'description' => __($langGroup, 'Se usa en el inicio de sesión y en el menú lateral.'),
        'hint' => __($langGroup, 'Preferiblemente con fondo transparente · Se guarda como PNG'),
        'image' => (string) get_config('logo'),
        'field' => 'logo',
        'action' => $actionURL,
        'width' => 400,
        'height' => 400,
        'format' => 'image/png',
        'fillColor' => 'transparent',
        'accept' => 'image/*,.ico',
    ]);
    ?>
</div>
<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
```

En el JavaScript de la pantalla:

```js
new ContentImageEditor()
```

Con `action`, la tarjeta envía el archivo en el campo `field` y espera un JSON con `success` y `message`: si falla, el editor
sigue abierto con el error. Sin `action`, la imagen es tuya, por ejemplo para enviarla con un formulario:

```js
const editor = new ContentImageEditor()
editor.onApply('logo-card', (file, url) => {
    //`file` es el recorte; `url`, una dirección local para pintarlo.
})
```

Con imagen, pulsarla abre el editor con la imagen actual. Sin imagen, la tarjeta sale vacía y sin «Descargar». `format` admite `image/png` e `image/jpeg`; `dark` enseña la vista previa
sobre fondo oscuro. Un clon puede poner la tarjeta en sus pantallas; las de Banners, Organizaciones y Publicaciones siguen con
su recortador propio.

## Filtros y cifras

Los botones de un formulario de filtros son una pieza. Sin `clearUrl`, «Limpiar» es un `reset`, para el JavaScript que filtra sin
recargar; con ella, un enlace, para un formulario que filtra por GET:

```php
<?php $this->helpController->_render('panel/built-in/content/filter-actions.php', [
    'clearUrl' => $listURL,
]); ?>
```

Una cifra suelta es una tarjeta del tema, dentro de `ui cards`:

```php
<div class="ui three stackable cards">
    <?php $this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
        'title' => __($langGroup, 'Intentos totales'),
        'help' => __($langGroup, 'Todos los intentos del periodo.'),
        'figure' => [
            'value' => (string) $total,
            'dataType' => 'attempts-total',
            'description' => __($langGroup, 'En los últimos 30 días'),
        ],
    ]); ?>
</div>
```

`dataType` deja el número a mano del JavaScript que lo actualice. `variant` admite `compact` y `tall`, y `size`, `huge marked`.

## La ventana de formulario

Para lo que tiene uno o dos campos (las categorías de publicaciones, las de formularios, los tipos de documento): el
alta y la edición se hacen en una ventana sobre el listado, y al guardar la tabla se actualiza sin recargar la página.
**Lo que lleva imágenes, colores o muchos campos va en su página** (las categorías de noticias, por ejemplo). Tampoco un
desplegable largo: el contenido de la ventana se desplaza y el menú sale recortado.

En la vista del listado, la ventana con sus campos:

```php
<?php ob_start(); ?>
<input type="hidden" name="id" value="" data-content-modal-when-value>
<div class="field required">
    <label><?= __($langGroup, 'Nombre'); ?></label>
    <input type="text" name="name" maxlength="300" required>
</div>
<?php
$this->helpController->_render('panel/built-in/content/form-modal.php', [
    'modalId' => 'mi-modulo-categoria',
    'title' => __($langGroup, 'Nueva categoría'),
    'action' => $addAction, //la acción del alta
    'fields' => (string) ob_get_clean(),
]);
?>
```

Y lo que la abre. **Sin valores, vacía (alta)**: por ejemplo, la acción principal de la cabecera:

```php
'primaryAction' => ['text' => __($langGroup, 'Nueva categoría'), 'url' => $addLink, 'icon' => 'plus',
    'attributes' => ['data-content-modal' => 'mi-modulo-categoria']],
```

**Con valores, rellena (edición)**: el «Editar» de cada fila, con los valores por `name`, la acción de edición y el
título de la ventana:

```php
$direct = RowActions::action(__(self::LANG_GROUP, 'Editar'), self::routeName('forms-edit', ['id' => $mapper->id]), 'edit', [
    'data-content-modal' => 'mi-modulo-categoria',
    'data-content-modal-values' => json_encode(['id' => (string) $mapper->id, 'name' => $name], \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR),
    'data-content-modal-action' => $editAction,
    'data-content-modal-title' => sprintf(__(self::LANG_GROUP, 'Editar: %s'), $name),
]);
```

- **Un campo con `data-content-modal-when-value`** solo se envía si los valores lo traen: el `id`, que en el alta no
  debe viajar.
- **La ventana no sustituye a la página**: el enlace de «Editar» sigue apuntando a la página de edición, que sirve para
  los enlaces directos y para quien la abra en otra pestaña.
- **Se apoya en `genericFormHandler`** sin cambiarlo: le pide que no redirija ni recargue, y al guardar con éxito cierra
  la ventana y recarga las tablas de la página.

Un ejemplo completo, con varios idiomas: las categorías de publicaciones
(`src/app/classes/Publications/Views/categories/list.php` y su `PublicationsCategoryController::dataTables()`).
