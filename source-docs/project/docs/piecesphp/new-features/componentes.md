# Proveedor de componentes HTML (`PiecesPHP\Components`)

`src/app/classes/PiecesPHP/Components` es un módulo pequeño con **una sola función**: servir, por HTTP y sin sesión,
**fragmentos de HTML reutilizables** (los «componentes») agrupados en archivos de plantilla, para que el JavaScript de
una página los pida, los clone y los rellene. No pinta pantallas del panel ni guarda nada: lo único que hace es
ejecutar una plantilla PHP y devolver su salida.

Sirve cuando una página pública cargada por AJAX necesita HTML que **ya sabe dibujar el servidor** (con sus traducciones,
sus URL y sus datos) y no quieres duplicarlo como cadenas dentro del JavaScript. El caso real del framework es el bloque
de publicaciones destacadas de la portada.

> [!NOTE]
> Lo documentado aquí es lo que el código hace hoy (2026-10-22). El módulo tiene partes sin uso (el lector en JS, un
> grupo de rutas y unas traducciones) que se deciden con su rediseño, en la 10.

## Cómo funciona, en cuatro piezas

| Pieza | Archivo | Qué es |
| :-- | :-- | :-- |
| Controlador | `src/app/classes/PiecesPHP/Components/Controllers/ComponentProvider.php` | Tiene la ruta `provide`: valida el grupo pedido, ejecuta su plantilla y escribe el resultado |
| Registro de rutas | `src/app/classes/PiecesPHP/Components/ComponentProviderRoutes.php` | Engancha el controlador al grupo de rutas `/components-provider` y lo activa o apaga (`ENABLE`) |
| Grupos de componentes | Una carpeta por grupo dentro de `src/app/classes/PiecesPHP/Components/Views/`, con su `components.php` | Una plantilla por **grupo**. Cada carpeta con un `components.php` es un grupo servible |
| Lector en JS (opcional) | `src/app/classes/PiecesPHP/Components/Statics/js/ComponentsProvider.js` | Pide un grupo y lo deja en un `Map`. **Hoy nadie lo usa** |

Las rutas se registran en `src/app/config/routes.php`: el grupo `{idioma}/components-provider` se crea en la línea 99 y
`ComponentProviderRoutes::routes($componentsProvider, $componentsProvider)` lo engancha en la 139. Si pones
`ComponentProviderRoutes::ENABLE` en `false`, no se registra la ruta ni se inyectan sus traducciones.

## El formato de un grupo

Una plantilla de grupo es un PHP que imprime **una etiqueta `<components name="…">` con varias `<component name="…">`
dentro**. Los nombres no son HTML estándar: son la convención que leen el JavaScript del cliente.
La referencia mínima, completa, es `src/app/classes/PiecesPHP/Components/Views/sample/components.php`:

```php
<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<components name='components'>

    <component name="component">

        <div class="field">

            <p>Texto de ejemplo.</p>

        </div>

    </component>

</components>
```

- **`<components name="…">`** nombra el conjunto. `ComponentsProvider.js` admite varios nombres separados por espacio.
- **`<component name="…">`** nombra una pieza; también admite varios nombres por espacio. Un cliente la busca con
  `component[name="…"]`, como hace `src/app/classes/Publications/Statics/js/publications/public/listing.js:73-75`.
- El contenido de cada `<component>` es el HTML que se va a clonar. Para que `ComponentsProvider.js` lo recupere, tiene
  que tener **un solo elemento raíz** (ver los pendientes).
- Atributos extra, como `data-slug="…"` en las publicaciones, sirven para que el cliente distinga varias copias de la
  misma pieza (`listing.js:74-75`).
- La plantilla es PHP normal: se ejecuta con el `render()` de `BaseController`, así que ve las variables globales del
  controlador y la petición (`$_GET`). **No recibe datos propios del controlador**: `provide()` la llama con
  `$data = []` (`ComponentProvider.php:66`).

## Cómo se registra un grupo nuevo

No hay una API de registro: **el grupo existe porque existe el archivo**. Para añadir uno:

1. Crea una carpeta con el nombre del grupo dentro de `src/app/classes/PiecesPHP/Components/Views/` y, en ella, un
   `components.php` con el formato de arriba. El nombre solo puede llevar letras, números, guion y guion bajo
   (`^[A-Za-z0-9_-]+$`; `ComponentProvider.php:61-64`).
2. No hay nada más que enganchar: `availableGroups()` (`ComponentProvider.php:77`) lista los directorios de `Views/`
   que tengan `components.php`, y esa lista es la que valida cada petición.
3. Si tu componente es de otro módulo, haz como Publications: el archivo del grupo es una línea que delega en una vista
   del módulo (`src/app/classes/PiecesPHP/Components/Views/publications/components.php:5`):

   ```php
   <?php PublicationsPublicController::view('public/util/components'); ?>
   ```

   Así el HTML vive con su módulo, no en `Components/`.
4. **Actualiza la prueba**: `UnitTest-ComponentProviderGroup.php` espera que los grupos servibles sean exactamente
   `publications` y `sample`. Al añadir uno, esa lista hay que ampliarla, o la suite falla.

## Cómo se pide

### La ruta

| Dato | Valor | Fuente |
| :-- | :-- | :-- |
| Nombre | `components-provider-provide` | `ComponentProvider.php:40` y `:127` |
| Método | `GET` | `ComponentProvider.php:128` |
| Patrón | `{idioma}/components-provider/provide/{group}[/]` | `src/app/config/routes.php:99` y `ComponentProvider.php:125` |
| Parámetro | `group`: el nombre de la carpeta de `Views/` | `ComponentProvider.php:61` |

Desde PHP, la URL se obtiene con el nombre de la ruta, nunca a mano:

```php
use PiecesPHP\Components\Controllers\ComponentProvider;

$url = ComponentProvider::routeName('provide', ['group' => 'publications'], true);
```

El tercer argumento (`true`) silencia el error si la ruta no existe (por ejemplo, con `ENABLE` en `false`): la función
devuelve entonces una cadena vacía.

### El patrón de la portada: URL con marcador, relleno en la vista

`PublicAreaController` pasa la URL con un marcador (`src/app/controller/PublicAreaController.php:129` y `:228`):

```php
'componentsProviderURL' => ComponentProvider::routeName('provide', ['group' => '{GROUP}'], true),
```

y la vista lo sustituye por el grupo que necesita y lo deja en un atributo `data-` para que lo lea el JavaScript
(`src/app/view/pages/home.php:35`):

```php
<div class="ui items posts-list" publications-special-block
     data-url="<?= $ajaxPublicationsURL; ?>"
     data-components-provider-url="<?= strReplaceTemplate($componentsProviderURL, ['{GROUP}' => 'publications']); ?>"></div>
```

### Desde JavaScript

La respuesta es HTML. Se pide como cualquier otro, se parsea con jQuery y se busca la pieza por su nombre. Así lo hace
el bloque de publicaciones (`listing.js:39-75`, resumido):

```js
const url = new URL($('[publications-special-block]').data('components-provider-url'))
// Parámetros que la plantilla del grupo lee de $_GET (en publications: los slugs que quiere dibujar)
url.searchParams.append('slugs[]', 'mi-slug')

getRequest(url).then(function (response) {
	const components = $(response)
	const html = components.find('component[name="special-main-item"]').filter('[data-slug="mi-slug"]').get(0).innerHTML
	$(contenedor).append($(html))
})
```

`getRequest` es el ayudante de `src/statics/core/js/helpers.js:478`.

## Qué devuelve

- **200** con el HTML de la plantilla del grupo, tal cual, y `Content-Type: text/html; charset=utf-8`
  (`ComponentProvider.php:66-69`).
- **404** (`NotFoundException`) si `group` no es una cadena con forma de nombre o no es uno de los grupos de
  `availableGroups()` (`ComponentProvider.php:61-64`). La comprobación va **antes** del `require` y es la defensa contra
  recorrer directorios con `..`. La cubre `UnitTest-ComponentProviderGroup.php` con diez entradas hostiles
  (`..`, `../x`, `a/b`, `SAMPLE`, `sample.php`, vacío…); el nombre distingue mayúsculas.
- Si la propia plantilla lanza una excepción (cualquiera, también un `NotFoundException`), `render()` la captura, la
  registra y delega en el manejador global (`src/app/core/psr4/PiecesPHP/Core/BaseController.php:118-122`), que responde
  **500** con la página de error: no llega a `provide()`. **Para un 404, la plantilla no lanza**: lo decide `provide()`
  con el filtro del grupo, antes de ejecutarla.

## Quién puede pedirlo

**Cualquiera, con o sin sesión.** La ruta se declara con `requireLogin = false` (quinto argumento de `Route`,
`ComponentProvider.php:124-131`) y la lista de roles que lleva (todos los de `UsersModel::TYPES_USERS`, `:115-118`) **no
restringe**: un `rolesAllowed` sin `require_login` no actúa. El inventario lo confirma:
`files/dev/permissions-declared.json:502-512` anota `"requireLogin": false` con los roles 0, 1, 2, 3, 4 y 12.

Consecuencias para quien escribe un grupo:

- **Todo lo que imprima una plantilla de grupo es público.** No pongas datos de usuario ni de administración.
- La plantilla se ejecuta con los parámetros de la URL: **hay que validar, escapar y poner tope** a lo que lea de
  `$_GET`, como hace `src/app/classes/Publications/Views/publications/public/util/components.php`: valida cada slug,
  **atiende como mucho diez** (`:11-12`; cada slug es una consulta y la ruta es pública) y escapa con `htmlspecialchars`
  todo lo que pinta, también los atributos `href` y `data-slug`.
- `ComponentProvider::routeName('provide', …)` devuelve la URL sin pasar por rol alguno para un visitante anónimo, y con
  `Roles::hasPermissions` para uno con sesión (`ControllerRoutingTrait.php:70-76`).
- `/components-provider/` está en el `Disallow` del `robots.txt` que genera el panel
  (`src/app/classes/PiecesPHP/Settings/Controllers/SiteFilesController.php:69`).
- La regla de negocio que oculte la ruta a quien los roles sí permiten iría en `_allowedRoute()`
  (`ComponentProvider.php:156`); hoy devuelve siempre `true`.

> [!NOTE]
> La fila de la matriz de permisos que hay en `files/dev/permissions-matrix.json:1130-1170` es la de las **estáticas** del
> módulo (`/components-provider/components-provider/statics/`), no la de `provide`. El comportamiento de `provide` por rol
> medido en vivo: **sin verificar**.

## Ejemplo completo: un grupo «catalog»

Derivado de `src/app/classes/PiecesPHP/Components/Views/sample/components.php` y del bloque de publicaciones. **No se ejecutó**: verifícalo en tu clon.

**1. El grupo** — una carpeta `catalog` en `Views/` del módulo, con su `components.php`:

```php
<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$label = array_key_exists('label', $_GET) && is_string($_GET['label']) ? $_GET['label'] : '';
?>
<components name="catalog">

    <component name="card">
        <div class="ui card">
            <div class="content">
                <div class="header"><?= $escape($label); ?></div>
            </div>
        </div>
    </component>

</components>
```

**2. La vista** — la URL con el grupo ya puesto, en un atributo `data-`:

```php
<?php use PiecesPHP\Components\Controllers\ComponentProvider; ?>
<div id="catalog"
     data-components-url="<?= ComponentProvider::routeName('provide', ['group' => 'catalog'], true); ?>"></div>
```

**3. El JavaScript**:

```js
const container = $('#catalog')
const url = new URL(container.data('components-url'), window.location.origin)
url.searchParams.set('label', 'Primera tarjeta')

getRequest(url).then(function (response) {
	const html = $(response).find('component[name="card"]').get(0).innerHTML
	container.append($(html))
})
```

**4. La prueba**: añade `catalog` a la lista esperada de `UnitTest-ComponentProviderGroup.php` y ejecútala:

```bash
bin/cli unit-tests:core/component-provider-group
```

(El prefijo `unit-tests:core/component-provider-group` está en `UnitTest-ComponentProviderGroup.php`.)

## El lector `ComponentsProvider.js`

Es un constructor que recibe la URL de un grupo, la pide con `$.ajax` y deja las piezas en un `Map`:

```js
const provider = new ComponentsProvider(url)
provider.on('load', function (e, components) {
	// components: Map<nombre del grupo, Map<nombre de la pieza, primer elemento>>
	const card = provider.getComponentsGroup('catalog').get('card')
})
// provider.isLoad() dice si ya cargó; getComponentsGroup() lanza Error si el grupo no existe
```

(`ComponentsProvider.js:7-111`). Su ruta pública sale de `ComponentProvider::pathJSModule()`
(`ComponentProvider.php:92`), pero **ningún módulo la llama** y `listing.js` no lo usa: parsea el HTML a mano.
Si lo vas a usar, ten en cuenta sus límites: guarda solo el primer elemento raíz de cada pieza, y dos `<components>`
con el mismo nombre se pisan. Que se sirva sin error: **sin verificar** (la matriz da 404 para la URL del
directorio de estáticas).

## Cobertura de pruebas

Una sola: `src/app/core/system-controllers/local-tests/UnitTest-ComponentProviderGroup.php`
(`unit-tests:core/component-provider-group`). Comprueba que los grupos servibles son `publications` y `sample`,
que ambos responden con `<components name=` y con `Content-Type: text/html; charset=utf-8`, que diez nombres hostiles
dan 404 sin llegar al `require`, que `provide()` no envuelve el render en un `try` y, en el grupo `publications`,
que no pinta más de diez piezas y que escapa el slug y la dirección. Llama a `provide()` directamente, sin Apache ni Slim.
No prueba el JavaScript.
`UnitTest-UserDataEscapes.php:571-575` prueba aparte que la vista de publicaciones escapa el extracto.
