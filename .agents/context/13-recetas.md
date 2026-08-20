# 13 — Recetas (paso a paso)

## Módulo de referencia

**`Publications` es la referencia canónica.** Es el módulo más completo del
sistema y el más mantenido (última actividad 2026-04-10). Cúbre, él solo, todos
los patrones del framework:

| Patrón | Dónde en `Publications` |
| :-- | :-- |
| Zona admin + zona pública en un mismo módulo | `routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)` |
| Controlador público separado | `Controllers/PublicationsPublicController.php` (extiende `BaseController`, no `AdminPanelController`) |
| Sub-entidad con su propio CRUD | `Controllers/PublicationsCategoryController.php` + `Mappers/PublicationCategoryMapper.php` |
| Relación 1-N con adjuntos | `Mappers/AttachmentPublicationMapper.php` + `Util/AttachmentPackage.php` |
| Traducción de campos de BD | `Util/FieldTranslationUtility.php` + `$translatableProperties` |
| Caché de respuestas | `CacheControllersManager` en `PublicationsController` |
| Traducciones separadas admin/público | `lang/` + `lang/lang-public/` (6 idiomas cada uno) |
| JS por vista, incluida la pública | `Statics/js/publications/{list,add-form,edit-form,delete-config}.js` + `public/{listing,detail}.js` |
| Integración con aprobaciones | `SystemApprovals\Util\Packages\PublicationsApprovalHandler` |

Cuando dudes de cómo se hace algo, **búscalo primero en `Publications`**.

> `News` sirve como ejemplo *mínimo* (módulo solo-admin, sin zona pública ni
> adjuntos), pero está marcado «por renovar» en `IGNORE.md` y su última actividad
> es de 2026-03-23: **no lo tomes como referencia de estilo.**

⚠️ Ojo: `PublicationsController` tiene 1.935 líneas y `PublicationMapper` 1.287.
Son referencia **de patrones**, no plantilla de copia y pega. Para arrancar un
módulo nuevo usa el esqueleto de abajo y ve a `Publications` a buscar cada pieza
concreta cuando la necesites.

> Si el módulo nuevo **se parece mucho** a Publications, sale más rápido clonarlo y
> renombrarlo que construirlo desde cero: el procedimiento completo de búsqueda y
> reemplazo está en
> [15-plantilla-clonar-publications.md](./15-plantilla-clonar-publications.md).

---

## Receta 1 — Crear un módulo nuevo

### 1. Constante de activación
`src/app/config/constants.php`:
```php
define('MI_MODULO_ENABLE', true);
```

### 2. Estructura de carpetas
```
src/app/classes/MiModulo/
├── MiModuloRoutes.php
├── MiModuloLang.php
├── Controllers/MiModuloController.php
├── Mappers/MiModuloMapper.php
├── Views/mi-modulo/{list.php, forms/{add.php,edit.php}}
├── Statics/{sass/mi-modulo.scss, js/mi-modulo/{list,add-form,edit-form,delete-config}.js}
├── Exceptions/SafeException.php
└── lang/{es.php,en.php,fr.php,de.php,it.php,pt.php}
```
El namespace raíz es `MiModulo` (PSR-4 sobre `src/app/classes`); no hay que tocar
`autoloads.php`.

### 3. `MiModuloLang.php`
```php
namespace MiModulo;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

class MiModuloLang extends LangInjector
{
    const LANG_GROUP = 'mi-modulo-lang';

    public static function injectLang()
    {
        (new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs()))
            ->injectGroup(self::LANG_GROUP);
    }
}
```

### 4. `MiModuloMapper.php`
Ver [06-orm-mappers.md](./06-orm-mappers.md). Define `const TABLE`, `$table`,
`$fields`, constantes de `status` y meta-propiedades en el constructor.

### 5. Generar la tabla
En `MiModuloRoutes::routes()`, pon `$showSQL = true;` temporalmente, abre cualquier
ruta del panel, copia el `CREATE TABLE`, ejecútalo, y vuelve a `false`.
Añade el SQL a `databases/piecesphp_structure.sql`.

### 6. `MiModuloRoutes.php`
```php
namespace MiModulo;

use MiModulo\Controllers\MiModuloController;
use MiModulo\Mappers\MiModuloMapper;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\InvocationStrategy;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

class MiModuloRoutes
{
    const ENABLE = MI_MODULO_ENABLE;
    private static $init = false;

    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {
            $showSQL = false;
            if ($showSQL) {
                header('Content-Type: text/sql');
                echo (new \PiecesPHP\Core\Database\SchemeCreator(new MiModuloMapper()))->getSQL();
                exit;
            }

            $groupAdministration = MiModuloController::routes($groupAdministration);
            self::staticResolver($groupAdministration);
            MiModuloLang::injectLang();
            InvocationStrategy::appendBeforeCallMethod(fn() => self::init());
        }
        return ['groupAdministration' => $groupAdministration];
    }

    public static function init()
    {
        if (self::$init) return;
        if (getLoggedFrameworkUser() === null) return null;

        get_sidebar_menu()->addItem(new MenuGroup([
            'name'     => __(MiModuloLang::LANG_GROUP, 'Mi módulo'),
            'icon'     => 'cube',
            'href'     => MiModuloController::routeName('list'),
            'visible'  => MiModuloController::allowedRoute('list'),
            'asLink'   => true,
            'position' => 140,
        ]));

        self::$init = true;
    }

    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()
            ->get('staticRouteModulesResolver')(self::class, $segment, __DIR__ . '/Statics', self::ENABLE);
    }

    protected static function staticResolver(RouteGroup $group)
    {
        $handler = function (Request $request, Response $response, array $args) {
            return (new ServerStatics())->compileScssServe(
                $request, $response, $args, __DIR__ . '/Statics', [], self::staticRoute()
            );
        };
        $cssVars = fn(Request $rq, Response $rs) => CSSVariables::instance('global')->toResponse($rq, $rs, false);

        $group->register([
            new Route('mi-modulo/statics/globals-vars.css', $cssVars, self::class . '-global-vars'),
            new Route('mi-modulo/statics/[{params:.*}]',    $handler, self::class),
        ]);
    }
}
```

### 7. `MiModuloController.php`
Extiende `App\Controller\AdminPanelController`, define `$URLDirectory`,
`$baseRouteName`, `$title`, las constantes `BASE_VIEW_DIR` / `BASE_JS_DIR` /
`BASE_CSS_DIR` / `LANG_GROUP`, el constructor (modelo, título, `setInstanceViewDir`,
assets globales), los métodos de acción, `routes()` y — **copiándolas de
`Publications\Controllers\PublicationsController`** — `routeName()`,
`allowedRoute()` y `_allowedRoute()`.

Si el módulo necesita zona pública, añade un
`MiModuloPublicController extends BaseController` siguiendo
`PublicationsPublicController`, y cambia la firma a
`routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)`.

### 8. Registrar en `config/routes.php`
```php
use MiModulo\MiModuloRoutes;
// ...
// Mi módulo
MiModuloRoutes::routes($zona_administrativa);
```
Recuerda: **de más específico a menos específico**; el grupo público
(`$zona_publica`) siempre se registra al final.

### 9. Compilar estáticos
```bash
cd src && gulp sass-modules
```

### 10. Verificar
- Aparece en el sidebar solo para los roles permitidos.
- `routeName('list')` devuelve URL para esos roles y `''` para el resto.
- Sin errores en `bin/phpstan`.
- Entrada añadida al `CHANGELOG.md`.

---

## Receta 2 — Añadir una ruta a un módulo existente

1. En `XController::routes()`, añade el `new Route(...)` **en el bloque del método
   HTTP que corresponda** (los bloques están separados por los comentarios
   `//──── GET ────` / `//──── POST ────`).
2. Usa `self::$baseRouteName . '-<sufijo>'` como nombre y elige el array de roles
   apropiado (`$list`, `$creation`, `$edition`, `$deletion`, `$queries`).
3. Implementa el método en el controlador: firma
   `public function metodo(Request $request, Response $response)` y **retorna un
   `Response`**.
4. Si es una vista, crea el `.php` en `Views/` y su JS en `Statics/js/`, y cárgalo
   con `set_custom_assets([...], 'js')`.
5. Si debe aparecer en el menú, añade el `MenuItem` en `XRoutes::init()` o en
   `config/menu.php` con `visible => X::allowedRoute('<sufijo>')`.

---

## Receta 3 — Endpoint JSON para DataTables

```php
public function dataTables(Request $request, Response $response)
{
    $helper = new \PiecesPHP\Core\Utilities\Helpers\DataTablesHelper(/* ... */);
    // configurar columnas, filtros y el query base con MiMapper::model()->select()
    return $response->withJson($helper->getResponse());
}
```
Ruta: `"{$startRoute}/datatables[/]"` con nombre `<base>-datatables`, método `GET`,
`requireLogin = true`.

---

## Receta 4 — Acción POST (crear/editar)

Un solo método `action()` atiende `-actions-add` y `-actions-edit`; distingue por el
nombre de la ruta actual. Patrón:

```php
public function action(Request $request, Response $response)
{
    $result = new ResultOperations([...]);   // PiecesPHP\Core\Utilities\ReturnTypes
    try {
        $params = new Parameters([
            new Parameter('title', $request->getParsedBodyParam('title'), [/* validadores */], true),
        ]);
        $params->validate();
        // ... crear/actualizar el mapper, ->save() o ->update()
    } catch (MissingRequiredParamaterException | InvalidParameterValueException | ParsedValueException $e) {
        // error controlado
    } catch (SafeException $e) {
        // error de negocio mostrable
    }
    return $response->withJson($result);
}
```

---

## Receta 5 — Cronjob, cola, listener o acción CLI

No toques el núcleo: añade el registro en el include correspondiente de
`src/app/config/final-configurations-includes/` (`cronjobs.php`, `queues.php`,
`event-listeners.php`, `cli-actions.php`). Sintaxis en
[10-cli-y-tareas.md](./10-cli-y-tareas.md).

---

## Receta 6 — Añadir un idioma

1. Añádelo a `allowed_langs` en `src/app/config/lang.php`.
2. Crea `src/app/lang/<code>.php`.
3. Añade el locale en `locale_langs` y `lc_time_names_mysql`, y los formatos en
   `format_date_lang` / `format_date_lang_sql`.
4. Añade la bandera en `get_fomantic_flag_by_lang`.
5. Crea `<code>.php` en el `lang/` de cada módulo (o deja que
   `scan-missing-lang` te diga qué falta):
   ```bash
   bin/cli scan-missing-lang --exclude-lang=es
   ```

---

## Receta 7 — Desactivar un módulo

Pon su constante en `false` en `src/app/config/constants.php`
(o en `CRITICAL_CONSTANTS` de `critical-definitions.php` para
`ORGANIZATIONS_MODULE`). La clase `Routes` deja de registrar rutas, estáticos,
traducciones y entradas de menú. Los permisos asociados quedan huérfanos pero no
rompen nada.

---

## Receta 8 — Proteger archivos estáticos

En `final-configurations-includes/protected-files.php`
(clase: `PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware`):

```php
use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
use PiecesPHP\Core\SessionToken;

ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, 'ruta/al/directorio'),
    function (Request $request, string $filePath) {
        return SessionToken::isActiveSession(SessionToken::getJWTReceived());
    }
);
```
