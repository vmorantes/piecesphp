# Crear un módulo

Todo el código de negocio vive en `src/app/classes/<Modulo>/`. `src/app/classes` es la raíz PSR-4, así que el
namespace de cada módulo **es el nombre de su carpeta** y no hay que tocar `autoloads.php`.

> **Dónde mirar un ejemplo.** `Publications` es el módulo más completo: zona de administración y pública,
> sub-entidad con su propio CRUD, adjuntos, traducción de campos, caché y aprobaciones. Úsalo para buscar **cada
> pieza** cuando la necesites, no como plantilla de copia: su controlador pasa de 1.900 líneas. `News` es el caso
> mínimo (solo administración), pero está pendiente de renovar: no lo tomes como referencia de estilo.

## 1. La bandera

En `src/app/config/constants.php`:

```php
define('MI_MODULO_ENABLE', true);
```

Cada instalación decide si el módulo está encendido. Con `false`, el módulo no registra rutas, estáticos,
traducciones ni menú.

## 2. Las carpetas

```
src/app/classes/MiModulo/
├── MiModuloRoutes.php          punto de entrada: lo único que conoce config/routes.php
├── MiModuloLang.php            traducciones del módulo
├── Controllers/MiModuloController.php
├── Mappers/MiModuloMapper.php
├── Views/mi-modulo/{list.php, forms/{add.php,edit.php}}
├── Statics/{sass/mi-modulo.scss, js/mi-modulo/{list,add-form,edit-form,delete-config}.js}
├── Exceptions/SafeException.php
└── lang/{es.php,en.php}
```

Otras carpetas que aparecen en módulos existentes cuando hacen falta: `Util/`, `Enums/`, `Queries/`, `Adapters/`,
`lang/lang-public/` (textos de la zona pública), `Views/mailing/`.

## 3. Las traducciones: `MiModuloLang.php`

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

Todo texto visible va por `__(MiModuloLang::LANG_GROUP, 'Texto en español')`. Los identificadores (clases, métodos,
columnas) van en inglés.

## 4. El mapper y su tabla

El mapper define `const TABLE`, `$table`, `$fields` y, si las usa, las meta-propiedades (ver
[Mappers](../content/mappers.md)).

**La tabla no se escribe a mano.** Sale de los `$fields`:

```bash
bin/cli scheme-create module=MiModulo
```

Imprime el `CREATE TABLE` ordenado (padres antes que hijas) y **no lo ejecuta**. Revísalo, haz un
`bin/cli db-backup`, aplícalo y añádelo a `databases/piecesphp_structure.sql`. El inverso es
`bin/cli scheme-drop module=MiModulo`.

> Una clave ajena hacia `pcsphp_users.id` se declara `bigint`, como esa columna. Con `int`, MariaDB rechaza la tabla
> (`errno 150`).

## 5. El punto de entrada: `MiModuloRoutes.php`

```php
namespace MiModulo;

use MiModulo\Controllers\MiModuloController;
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
            return (new ServerStatics())->serve($request, $response, $args, __DIR__ . '/Statics');
        };
        $cssVars = fn(Request $rq, Response $rs) => CSSVariables::instance('global')->toResponse($rq, $rs, false);

        $group->register([
            new Route('mi-modulo/statics/globals-vars.css', $cssVars, self::class . '-global-vars'),
            new Route('mi-modulo/statics/[{params:.*}]',    $handler, self::class),
        ]);
    }
}
```

## 6. El controlador

Extiende `App\Controller\AdminPanelController` y define `$URLDirectory`, `$baseRouteName`, `$title`, las constantes
`BASE_VIEW_DIR`, `BASE_JS_DIR`, `BASE_CSS_DIR` y `LANG_GROUP`, el constructor, las acciones y `routes()`.

`routeName()`, `allowedRoute()` y `_allowedRoute()` **no se copian**: los aporta un trait.

```php
use PiecesPHP\Core\Routing\ControllerRoutingTrait;

class MiModuloController extends AdminPanelController
{
    use ControllerRoutingTrait;
```

- **Toda acción devuelve un `Response`.**
- **Las URLs se construyen con `self::routeName('sufijo', $params)`**, nunca concatenando (ver
  [Rutas](../content/routing.md)).
- **Una acción de alta y la de edición pueden ser el mismo método**: la operación la decide el nombre de la ruta,
  no el `id` del cuerpo.
- **Un valor que llega de la petición entra al SQL por marcador** (`where([...])` o `WhereSegment`), nunca
  concatenado.
- Escribe `_allowedRoute()` solo si el módulo tiene reglas que los roles no pueden saber (por ejemplo, «solo el autor
  puede editar este registro»).
- Si el módulo tiene zona pública, añade `MiModuloPublicController extends BaseController` como
  `PublicationsPublicController`, y la firma pasa a `routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)`.

## 7. Las vistas

Las vistas del panel siguen el esqueleto del panel: se pintan entre `panel/layout/header` y `panel/layout/footer`, y
dentro llevan `section.module-view-container`. Qué componente usar en cada caso está en
[El panel por dentro](./panel.md).

## 8. Si el módulo sube archivos

Decide antes de escribir la subida si los archivos son **públicos**, **privados** (solo con permiso) o **mixtos**
(según la visibilidad del registro):

- privados: guárdalos con `ProtectedUploads::privatePath()`;
- mixtos: `ProtectedUploads::setFolderVisibility()`, como `Publications`;
- y **declara la carpeta** en `src/app/config/final-configurations-includes/protected-files.php`:

```php
use PiecesPHP\Core\Statics\ProtectFileMiddleware;

ProtectFileMiddleware::protectWithSession(append_to_path_system($uploadsDir, 'mi-modulo/privados'));
```

`bin/cli verify-integrity` falla si una carpeta de subidas no está protegida ni declarada como pública. La guía
completa: [Archivos protegidos](../new-features/protected-files.md).

## 9. Registrarlo

En `src/app/config/routes.php`:

```php
use MiModulo\MiModuloRoutes;

MiModuloRoutes::routes($zona_administrativa);
```

De más específico a menos específico; el grupo público (`$zona_publica`) siempre al final.

## 10. Compilar y comprobar

```bash
cd src && gulp sass-modules      # estilos del módulo
bin/phpstan                      # sin errores nuevos
bin/cli verify-integrity         # estructura del repositorio
bin/cli gates                    # todas las suites
```

- El menú aparece solo para los roles con permiso.
- `routeName('list')` da la URL a esos roles y cadena vacía al resto.
- Si el cambio afecta a quien clona el framework, va una entrada en `CHANGELOG.md`.

> **¿Tu módulo se parece mucho a Publications?** Puede salir antes clonarlo y renombrarlo. El procedimiento de
> búsqueda y reemplazo existe para agentes en `.agents/context/15-plantilla-clonar-publications.md`.
