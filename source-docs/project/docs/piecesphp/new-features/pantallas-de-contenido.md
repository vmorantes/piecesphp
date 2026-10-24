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
| Cabecera | `src/app/view/panel/built-in/content/page-header.php` | `ui grid`, `ui button` | No: es el armazón |
| Acciones de cada fila | `PiecesPHP\Core\Utilities\Helpers\RowActions` | `ui icon button`, `ui dropdown` | Sí |
| Aviso de «aún no hay…» | `src/app/view/panel/built-in/content/empty-state.php` | `ui placeholder segment` | Sí |
| Barra de guardar | `src/app/view/panel/built-in/content/form-action-bar.php` | `ui segment` | No |
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

**En el móvil, cada fila pasa a ser una tarjeta**, con el nombre de cada dato delante, y la tabla no se desliza en
horizontal. No hay que hacer nada para tenerlo: lo pone el panel en toda tabla con `content-table`. La primera columna
(el título) y la última (las acciones) van sin etiqueta.

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
    'icon' => 'newspaper outline',
    'action' => ['text' => __($langGroup, 'Nueva noticia'), 'url' => $addLink],
]);
?>
```

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

- **`danger`** pinta la acción en rojo y la lleva a la derecha.
- **Eliminar es `type="button"`**, nunca `submit`: si el JavaScript no cargara, un `submit` guardaría el formulario en vez
  de borrar.
- **En el móvil, la barra cabe en una fila**: «Guardar» con su texto y las secundarias que tengan `icon`, solo con el
  icono (el texto sigue en su `title` y `aria-label`). Una secundaria sin icono conserva su texto.

## La ventana de formulario

Para lo que tiene uno o dos campos (las categorías de publicaciones, las de formularios, los tipos de documento): el
alta y la edición se hacen en una ventana sobre el listado, y al guardar la tabla se actualiza sin recargar la página.
**Lo que lleva imágenes, colores o muchos campos va en su página** (las categorías de noticias, por ejemplo).

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
