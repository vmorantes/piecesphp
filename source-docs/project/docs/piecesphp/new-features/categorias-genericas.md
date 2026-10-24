# Categorías genéricas (`Forms\Categories`)

`src/app/classes/Forms/Categories` es un módulo pequeño y completo: una **categoría con un único dato, su nombre,
traducible a cada idioma de la instalación**, y la pantalla del panel para crearlas, editarlas, traducirlas y borrarlas.
No está atado a ningún otro módulo: hoy nadie del framework lo consume, y por eso sirve como **pieza para clones**
cuando un proyecto necesita «una lista de cosas con nombre» (tipos, clases, etiquetas) y no quiere escribirla desde cero.

También es el **ejemplo real de la pieza «Ventana de formulario»** de la hoja de diseño del panel
(ver [Pantallas de contenido](pantallas-de-contenido.md)): su listado crea y edita en una ventana
(`src/app/view/panel/built-in/content/form-modal.php`, disparada con `data-content-modal`), sin salir de la página.
Si quieres ver la pieza funcionando con código de verdad, este módulo es el sitio.

> [!NOTE]
> Se conserva a propósito (decisión del Product Owner, P144 a). Aunque ningún módulo suyo lo use, no es código muerto:
> es la plantilla y la referencia del listado con ventana.

## Cómo se activa y se desactiva

Dos constantes, en `src/app/config/constants.php`:

```php
//Formularios
define('FORMS_MODULE_ENABLE', true);
define('FORMS_MODULE_CATEGORIES_ENABLE', true);
define('FORMS_MODULE_DOCUMENTS_TYPES_ENABLE', true);
```

El módulo está activo cuando **las dos** valen `true` (`CategoriesRoutes::ENABLE`). Qué pasa si no:

| Constante en `false` | Efecto |
| :-- | :-- |
| `FORMS_MODULE_ENABLE` | No se registra ninguna ruta de `Forms` (ni categorías ni tipos de documento) y no se añade el grupo «Formularios» al menú lateral |
| `FORMS_MODULE_CATEGORIES_ENABLE` | No se registran las rutas de categorías ni se inyectan sus traducciones. El grupo «Formularios» sigue dibujándose en el menú, con la entrada «Categorías» (**sin verificar** qué enlace deja esa entrada cuando las rutas no existen: pruébalo antes de apagar solo esta constante) |

Las rutas se enganchan en `src/app/config/routes.php` con `FormsRoutes::routes($zona_administrativa)`, que llama a
`CategoriesRoutes::routes()`. Por eso **todas cuelgan de la zona administrativa** (`{idioma}/admin/…`) y piden sesión.

## Las rutas

Las define `Forms\Categories\Controllers\CategoriesController::routes()`. El prefijo de nombre es `categories-admin`, y
**el nombre de la ruta es el identificador de permiso**. Desde código no se escribe el nombre completo: se pide por su
sufijo.

| Sufijo | Método y ruta (tras `{idioma}/admin`) | Para qué sirve | Roles que la declaran |
| :-- | :-- | :-- | :-- |
| `list` | `GET /categories/list` | El listado, con la ventana de alta y edición | los seis (ver abajo) |
| `forms-add` | `GET /categories/forms/agregar` | Página de alta; la ventana la sustituye en el listado, queda para quien no tenga JavaScript | Principal, Admin. general, Admin. de organización, Usuario general |
| `forms-edit` | `GET /categories/forms/editar/{id}/{lang}` | Página de edición en un idioma (por omisión, el idioma de la instalación) | los mismos cuatro |
| `ajax-all` | `GET /categories/all` | JSON paginado (`page`, `per_page`) | los seis |
| `datatables` | `GET /categories/datatables` | Datos del listado para DataTables | los seis |
| `actions-add` | `POST /categories/action/add` | Crea | los mismos cuatro |
| `actions-edit` | `POST /categories/action/edit` | Guarda cambios (y traducciones) | los mismos cuatro |
| `actions-delete` | `POST /categories/action/delete/{id}` | Borrado lógico: pone `status` en 0 | los mismos cuatro, con la regla de abajo |

Además hay dos rutas de estáticos (`formularios/categorias-forms/statics-resolver/…`) que sirven el JavaScript y el CSS
del módulo.

**Quiénes son «los seis»**: los que da `UsersModel::TYPES_USERS`: Principal (0), Administrador general (1),
Administrador de organización (12), Usuario general (2), Institucional (3) y Comunicaciones (4).

> [!WARNING]
> Lo que declara una ruta **concede, no restringe** (ver [Permisos de ruta](permisos-de-ruta.md)): el acceso real es la
> unión de esta lista y de lo que el catálogo `src/app/config/roles.php` dé a cada rol. Qué rutas de este módulo tiene
> cada rol en ese catálogo, **sin verificar**.

Hay además una regla de negocio por encima de los roles (`CategoriesController::_allowedRoute()`):

- **Editar y eliminar**: Principal, Administrador general y Administrador de organización siempre; el **Usuario general
  solo las categorías que creó él** (`createdBy`).
- **Alta y listado**: sin restricción por tipo de usuario más allá de los roles de la ruta.
- Un middleware de la zona (`DefaultAccessControlModules`) devuelve 403 si `allowedRoute()` niega una ruta del módulo.

Para construir un enlace o decidir si mostrar un botón:

```php
use Forms\Categories\Controllers\CategoriesController;

$listLink = CategoriesController::routeName('list');
$canAdd = CategoriesController::allowedRoute('actions-add');
$editLink = CategoriesController::routeName('forms-edit', ['id' => 5, 'lang' => 'es']);
```

## La tabla y el mapper

`Forms\Categories\Mappers\CategoriesMapper` (tabla `forms_categories`) define su estructura en `$fields`:

| Columna | Tipo | Notas |
| :-- | :-- | :-- |
| `id` | `int`, clave primaria | |
| `preferSlug` | `text`, admite nulo | Token para acceso individual sin exponer el `id` |
| `categoryName` | `text` | El nombre, en el idioma por omisión |
| `folder` | `text` | Lo rellena el alta con un `uniqid()` sin puntos |
| `status` | `int`, por omisión `STATUS_ACTIVE` (1) | 0 es `STATUS_INACTIVE`: así queda una categoría «eliminada» |
| `createdAt` | `datetime`, por omisión `timestamp` | |
| `updatedAt` | `datetime`, admite nulo | |
| `createdBy` | `bigint`, referencia a `UsersModel` | Lo pone el mapper al guardar |
| `modifiedBy` | `bigint`, referencia a `UsersModel`, admite nulo | |
| `meta` | `json`, admite nulo | Guarda `langData`, las traducciones |

Cosas que importan al clonarlo:

- **Traducción.** `categoryName` es la única propiedad traducible (`$translatableProperties`). En el idioma por omisión
  va a la columna; en los demás, dentro de `meta` → `langData`. Se escribe con `setLangData($lang, 'categoryName', $texto)`
  y se lee con `getLangData($lang, 'categoryName')`; `currentLangData('categoryName')` usa el idioma de la petición.
- **Nombre único.** `save()` y `update()` lanzan `Forms\Categories\Exceptions\DuplicateException` si ya hay una categoría
  con ese `categoryName` (se compara contra todas, **también las eliminadas**). El controlador la convierte en el
  mensaje de la respuesta.
- **Borrado lógico.** Eliminar hace `UPDATE … SET status = 0`; la fila no se borra.

**La tabla no se escribe a mano.** El SQL de creación lo emite la herramienta a partir del `$fields` del mapper:

```bash
bin/cli scheme-create module=Forms
```

`module=` es una carpeta bajo `src/app/classes`: `Forms` emite las tablas de todo el módulo de formularios (categorías y
tipos de documento) y `Forms/Categories` solo la de este (**la forma `Forms/Categories` no se ejecutó al escribir esto;
sale de leer `SchemeSqlTask::discover()`**). La tarea **emite** el SQL, no lo ejecuta. La tabla de las instalaciones
nuevas ya viene en `databases/piecesphp_structure.sql`. Para cambiar una tabla que ya existe, un `ALTER TABLE` en
`databases/actualizaciones/` con el esquema al día en el mismo commit.

Para tener datos de prueba existe `Forms\Seeds\FormCategoriesSeeder` (tres filas por omisión, con el prefijo
`categoria-formulario` en el nombre). Se lanza con la siembra de su carpeta, `bin/cli seed module=Forms` (simula) y `bin/cli seed module=Forms escribir=yes` (ver [Datos de prueba](datos-de-prueba.md); que esa carpeta lo encuentre, **sin ejecutar** al escribir esto).

## Usarlo desde otro módulo

El módulo **no trae un campo «elige una categoría» ya hecho**, ni en el mapper ni en el controlador. Trae lo necesario
para armarlo en tres líneas:

- `CategoriesMapper::allForSelect($etiqueta, $valor)`: un arreglo `id => nombre` (en el idioma de la petición), con una
  primera opción vacía, listo para `array_to_html_options()`.
- `CategoriesMapper::existsByID($id)`, `CategoriesMapper::getBy($valor, 'id', true)` y `CategoriesMapper::allBy($columna, $valor, true)`.
- La ruta JSON `ajax-all` (`CategoriesController::routeName('ajax-all')`) y su método `CategoriesController::_all($page, $perPage)`.

> [!WARNING]
> **`allForSelect()`, `all()`, `allBy()` sin filtro y `ajax-all` devuelven también las categorías eliminadas** (las de
> `status = 0`): ninguna filtra por `status`. El listado del panel sí filtra. En un formulario de otro módulo filtra tú
> con `allBy('status', CategoriesMapper::STATUS_ACTIVE, true)`, como en el ejemplo.

Ejemplo: guardar en tu entidad el `id` de una categoría, con un `select` que solo ofrece las activas.

```php
<?php
// En el controlador de tu módulo, al preparar la vista del formulario.
use Forms\Categories\Mappers\CategoriesMapper;

$options = ['' => __($langGroup, 'Seleccione una categoría')];
foreach (CategoriesMapper::allBy('status', CategoriesMapper::STATUS_ACTIVE, true) as $category) {
    /** @var CategoriesMapper $category */
    $options[(string) $category->id] = (string) $category->currentLangData('categoryName');
}
$data['categoryOptions'] = array_to_html_options($options, (string) ($selectedCategoryId ?? ''));
```

```php
<!-- En la vista de tu módulo -->
<div class="field required">
    <label><?= __($langGroup, 'Categoría'); ?></label>
    <select name="category" class="ui dropdown" required>
        <?= $categoryOptions; ?>
    </select>
</div>
```

Y al recibir el formulario, valida que la categoría exista **y esté activa** antes de guardarla. `existsByID()` no
sirve para eso: devuelve `true` también para una categoría eliminada.

```php
$categoryId = (int) $request->getParsedBody()['category'];
$category = CategoriesMapper::getBy($categoryId, 'id', true);
if ($category === null || (int) $category->status !== CategoriesMapper::STATUS_ACTIVE) {
    // responde con un mensaje de validación
}
```

Si tu módulo necesita **un enlace** a la gestión de categorías, usa
`CategoriesController::routeName('list')` y comprueba antes `CategoriesController::allowedRoute('list')`.

## El listado con ventana, para copiar

Lo que distingue a este módulo es su `Views/list.php` (`src/app/classes/Forms/Categories/Views/list.php`): el botón
«Nueva categoría» y la acción «Editar» de cada fila **abren la misma ventana**, y al guardar con éxito la ventana se
cierra y las tablas de la página se recargan sin recargar la página. Tiene tres piezas.

**1. El disparador de alta**, en la cabecera de la pantalla:

```php
$this->helpController->_render('panel/built-in/content/page-header.php', [
    'breadcrumbs' => $breadcrumbs,
    'title' => $title,
    'primaryAction' => $hasPermissionsAdd
        ? ['text' => __($langGroup, 'Nueva categoría'), 'url' => $addLink, 'icon' => 'plus',
           'attributes' => ['data-content-modal' => 'forms-category-modal']]
        : null,
]);
```

Si el JavaScript no corre, el `url` es la página de alta de siempre.

**2. La ventana**, al final de la vista, con los campos que esperan las acciones de alta y de edición:

```php
<?php ob_start(); ?>
<input type="hidden" name="id" value="" data-content-modal-when-value>
<input type="hidden" name="lang" value="<?= escape_html($defaultLang); ?>">
<div class="field required">
    <label><?= __($langGroup, 'Nombre'); ?></label>
    <input type="text" name="categoryName" required>
</div>
<?php
$this->helpController->_render('panel/built-in/content/form-modal.php', [
    'modalId' => 'forms-category-modal',
    'title' => __($langGroup, 'Nueva categoría'),
    'action' => $addAction,
    'fields' => (string) ob_get_clean(),
]);
?>
```

`$addAction` es `CategoriesController::routeName('actions-add')`. El `id` lleva `data-content-modal-when-value`: solo se
envía cuando la ventana se abre para editar. El `lang` de alta lo exige la acción aunque guarde en el idioma por omisión.

**3. La acción «Editar» de cada fila**, que sale de `CategoriesController::dataTables()`. Rellena la ventana con
`data-content-modal-values` (un JSON por `name` de campo) y cambia su acción y su título:

```php
$values = ['id' => (string) $mapper->id, 'lang' => $defaultLang,
           'categoryName' => (string) $mapper->getLangData($defaultLang, 'categoryName')];
$direct = RowActions::action(__(self::LANG_GROUP, 'Editar'), self::routeName('forms-edit', ['id' => $mapper->id, 'lang' => $defaultLang]), 'edit', [
    'data-content-modal' => 'forms-category-modal',
    'data-content-modal-values' => json_encode($values, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR),
    'data-content-modal-action' => self::routeName('actions-edit'),
    'data-content-modal-title' => sprintf(__(self::LANG_GROUP, 'Editar: %s'), $name),
]);
```

«Traducir» (si la instalación tiene más de un idioma) lleva a la **página** de edición en el otro idioma
(`src/app/classes/Forms/Categories/Views/forms/edit.php`): la ventana solo edita el idioma por omisión. Eliminar usa
`RowActions::delete()` con los atributos que espera `src/app/classes/Forms/Categories/Statics/js/delete-config.js`.

Para clonarlo a otro módulo con campos propios, copia la carpeta y cambia solo lo que es del módulo: el espacio de
nombres, el mapper (`$fields`, `TABLE`, `$translatableProperties`), los campos de la ventana, el `modalId`, el
`$baseRouteName` y el segmento de URL del controlador.

> [!NOTE]
> La ventana sirve para **uno o dos campos**. Lo que lleve imágenes, colores o muchos campos va en su página, como
> pide la hoja de diseño.

## Traducciones del módulo

Los textos de la interfaz están en `src/app/classes/Forms/Categories/lang/es.php` y `en.php`, en el grupo
`categories-lang` (`CategoriesLang::LANG_GROUP`). `CategoriesLang::injectLang()` los carga al registrar las rutas.
