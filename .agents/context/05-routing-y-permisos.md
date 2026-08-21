# 05 — Routing y permisos

Slim 4 es el motor, pero **nunca se usa directamente**. PiecesPHP envuelve el
registro de rutas para poder derivar los permisos automáticamente.

## Las dos clases

```php
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
```

### `RouteGroup`

```php
$grupo = new RouteGroup('/mi-prefijo');   // segmento base del grupo
$grupo->register([ /* Route[] */ ]);
$grupo->addMiddleware(fn($request, $handler) => ...);
$grupo->getGroupSegment();                // el prefijo, útil para armar sub-rutas
RouteGroup::setRouter($router);           // se hace una vez en routes.php
RouteGroup::initRoutes(false);            // construye el árbol Slim real (index.php)
```

Los grupos se crean en `config/routes.php` prefijados por
`get_config('prefix_lang')` para soportar el idioma en URL.

### `Route`

```php
new Route(
    string $route,                 // '/list[/]' — sintaxis Slim; [] = opcional
    string|callable $controller,   // 'Namespace\Clase:metodo' o un callable
    string $name = uniqid(),       // ⭐ NOMBRE DE LA RUTA = IDENTIFICADOR DEL PERMISO
    string $method = 'GET',        // GET|POST|PUT|PATCH|DELETE...
    bool   $requireLogin = false,
    ?string $alias = null,
    array  $rolesAllowed = [],     // códigos de rol (alternativa a roles.php)
    array  $defaultParamsValues = []
);
```

> **El nombre de la ruta es el permiso.** No hay una tabla de permisos aparte: el
> sistema de roles autoriza por nombre de ruta.

## Registro por módulo

`config/routes.php` no define rutas concretas de módulos; llama a
`XRoutes::routes($grupoAdmin, $grupoPublico)`, que a su vez llama a
`XController::routes($grupo)`. Patrón real (`News`):

```php
// NewsRoutes::routes()
$groupAdministration = NewsController::routes($groupAdministration);
$groupAdministration = NewsCategoryController::routes($groupAdministration);
self::staticResolver($groupAdministration);   // rutas de estáticos del módulo
NewsLang::injectLang();                        // traducciones del módulo
InvocationStrategy::appendBeforeCallMethod(fn() => self::init());  // menú, front-config
```

Y dentro del controlador:

```php
protected static $URLDirectory  = 'news';        // segmento URL del módulo
protected static $baseRouteName = 'news-admin';  // prefijo de TODOS sus nombres de ruta

public static function routes(RouteGroup $group)
{
    $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;
    $list = [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL, ...];

    $group->register([
        new Route("{$startRoute}/list[/]",             self::class.':listView',   self::$baseRouteName.'-list',           'GET',  true, null, $list),
        new Route("{$startRoute}/forms/add[/]",        self::class.':addForm',    self::$baseRouteName.'-forms-add',      'GET',  true, null, $creation),
        new Route("{$startRoute}/forms/edit/{id}[/]",  self::class.':editForm',   self::$baseRouteName.'-forms-edit',     'GET',  true, null, $edition),
        new Route("{$startRoute}/all[/]",              self::class.':all',        self::$baseRouteName.'-ajax-all',       'GET',  true, null, $queries),
        new Route("{$startRoute}/datatables[/]",       self::class.':dataTables', self::$baseRouteName.'-datatables',     'GET',  true, null, $list),
        new Route("{$startRoute}/action/add[/]",       self::class.':action',     self::$baseRouteName.'-actions-add',    'POST', true, null, $creation),
        new Route("{$startRoute}/action/edit[/]",      self::class.':action',     self::$baseRouteName.'-actions-edit',   'POST', true, null, $edition),
        new Route("{$startRoute}/action/delete/{id}[/]", self::class.':toDelete', self::$baseRouteName.'-actions-delete', 'POST', true, null, $deletion),
    ]);

    $group->addMiddleware(function ($request, $handler) {
        return (new DefaultAccessControlModules(
            self::$baseRouteName . '-',
            fn(string $name, array $params) => self::routeName($name, $params)
        ))->getResponse($request, $handler);
    });

    return $group;
}
```

### Convención de sufijos de nombre de ruta

`<baseRouteName>-<sufijo>`, con sufijos estándar:

| Sufijo | Método | Propósito |
| :-- | :-- | :-- |
| `-list` | GET | Vista de listado |
| `-forms-add` / `-forms-edit` | GET | Formularios HTML |
| `-datatables` | GET | Endpoint JSON para DataTables |
| `-ajax-all` | GET | JSON con todos los elementos |
| `-actions-add` / `-actions-edit` / `-actions-delete` | POST | Acciones de escritura |

Sigue este esquema al crear rutas nuevas.

## Generar URLs — nunca las escribas a mano

```php
Controller::routeName('list');                          // URL de <base>-list
Controller::routeName('forms-edit', ['id' => 4]);       // con parámetros
Controller::routeName('list', [], true);                // silentOnNotExists
Controller::allowedRoute('list');                       // bool: ¿el usuario puede?
get_route('nombre-ruta', $params, $silent);             // helper global
get_route_info($nombre); get_routes(); get_route_by_controller(...);
```

`routeName()` devuelve **cadena vacía si el usuario actual no tiene permiso** —
por eso `allowedRoute()` es simplemente `strlen(routeName(...)) > 0`, y por eso los
menús usan `'visible' => Controller::allowedRoute('x')`.

### Por qué `routeName()` / `allowedRoute()` están en cada controlador

**No están en ninguna clase base: 44 controladores las reimplementan.** No es
descuido — es consecuencia de tres cosas del diseño:

1. **Son piezas nucleares del acoplamiento controlador↔Slim.** Traducen
   `$baseRouteName + sufijo` a una ruta Slim registrada y, de paso, resuelven el
   permiso. No son un helper accesorio.
2. **No todos los controladores comparten padre.** 39 extienden
   `AdminPanelController`, pero 4 extienden `BaseController` directamente
   (`PublicAreaController`, `PublicationsPublicController`,
   `ApplicationCallsPublicController`, `BuiltInBannerPublicController`) y
   `ContactFormsController` extiende `PublicAreaController`. Meterlas en
   `AdminPanelController` dejaría fuera toda la zona pública.
3. **Usan `self::$baseRouteName`, no `static::`.** En una clase padre `self::`
   resolvería a la propiedad del padre, no a la del hijo — se rompería.

**Medido por tokens** (`.agents/context/18-siguientes-ventanas.md`, T12), no estimado:

| Método | Cuerpos distintos | Archivos |
| :-- | --: | --: |
| `routeName` | **9** | 44 |
| `allowedRoute` | **5** | 38 |
| `_allowedRoute` | **26** | 32 |

**Corrección:** este documento afirmaba que «el punto de variación real es `_allowedRoute()`,
no `routeName()`». **Es cierto para `_allowedRoute` y falso para los otros dos**: se escribió
sin medirlo. `_allowedRoute` sí es el punto de variación legítimo —26 cuerpos en 32 archivos,
uno por módulo—, pero `routeName` tiene **9 cuerpos distintos** y `allowedRoute` **5**, y la
mayor parte de esa diferencia es **deriva, no intención**.

La distinción que importa: **la duplicación era deliberada** —boilerplate visible que impone
la convención y ahorra reescritura al clonar—, **las diferencias no**.

> **RUTA APROBADA, ya no es un «si algún día».** El vehículo es un **trait**
> (`RouteNamingTrait`) y no una clase base: el trait se compone en cualquier jerarquía
> —los `*PublicController` extienden `BaseController`, no `AdminPanelController`—, `self::`
> sigue resolviendo a la clase que lo usa, y el hook `_allowedRoute()` pasa a ser un método
> `protected` con implementación por defecto `return true;`.
>
> Lo que compra: hoy quien clona un módulo copia sesenta líneas sin saber cuáles debe tocar,
> porque son todas ruido idéntico. Con el trait, lo único escrito en su controlador es
> `_allowedRoute()` — exactamente la parte que sí debe pensar. **La convención no se pierde,
> se afila.**
>
> Estado y variantes que el trait NO cubre: T12 del doc 18.
>
> Mientras tanto: **al crear un módulo nuevo, cópialas de un módulo existente**
> (`Publications\Controllers\PublicationsController` para la variante con hook —
> es la referencia canónica del proyecto;
> `FileManager\Controllers\FileManagerController` para la básica).

## Roles

Definidos en `src/app/config/roles.php` y gestionados por `PiecesPHP\Core\Roles`.

### Tipos de usuario (`App\Model\UsersModel`)

| Constante | Código | Nombre |
| :-- | --: | :-- |
| `TYPE_USER_ROOT` | 0 | Principal |
| `TYPE_USER_ADMIN_GRAL` | 1 | Administrador general |
| `TYPE_USER_GENERAL` | 2 | Usuario general |
| `TYPE_USER_INSTITUCIONAL` | 3 | Institucional |
| `TYPE_USER_COMUNICACIONES` | 4 | Comunicaciones |
| `TYPE_USER_ADMIN_ORG` | 12 | Administrador de organización |
| `TYPE_USER_GOOGLE_PLAY` | 50 | (especial, comentado en `TYPES_USERS`) |

Hay además `TYPES_USER_PRIORITY` (jerarquía numérica: root 500, admin gral 400…).
Los tipos cuyo `name` es `null` en `TYPES_USERS` se filtran automáticamente de
`$config['roles']['types']`; y si `ORGANIZATIONS_MODULE` está en `false`, se elimina
`TYPE_USER_ADMIN_ORG`.

### Cómo se otorga acceso

Dos vías, ambas válidas y combinables:

1. **`roles.php`** — listas `allowed_routes` por tipo de rol. Están segmentadas en
   `$permisosGenerales` ⊂ `$permisosAdministrativos` ⊂ `$permisosSuperiores`, y
   expuestas en `$config['roles']['baseInitialSegmentedPermissions']`.
2. **7º parámetro de `Route`** (`$rolesAllowed`) — lo que usan los módulos.

Un rol con `'all' => true` accede a todo.

### API de `Roles`

```php
Roles::hasPermissions(string $routeName, ?int $userType): bool
Roles::getCurrentRole(): ?array   // ['code' => ..., 'name' => ...]
```

Y el helper global `getLoggedFrameworkUser()` devuelve el usuario en sesión
(o `null`). `throw403()` corta con acceso denegado.

## Sesión y autenticación

- `PiecesPHP\Core\SessionToken` (JWT) y `SessionTokenIsolated`.
  `SessionToken::isActiveSession(SessionToken::getJWTReceived())` valida la sesión.
- `BaseToken` firma con `Config::app_key()`.
- 2FA/OTP: `PiecesPHP\UserSystem\Authentication\{OTPHandler, TOTPStandard}` +
  tabla `pcsphp_users_otp_secrets` (`pragmarx/google2fa`).
- **Conectarse como otro usuario**: parámetro GET `asUser` / cookie `asUserID`
  (constantes `CONNECT_AS_ANOTHER_USER_*`), con `RootOriginalID` y
  `RootIsLoggedAsUser` en configuración.
- Intentos de login: `LoginAttemptsModel` / tabla `login_attempts`, reporte en el
  panel (`LoginAttemptsController`).

## Middleware de módulo

`PiecesPHP\RoutingUtils\DefaultAccessControlModules` es el control de acceso
estándar que los módulos añaden como middleware de grupo. Recibe el prefijo de
nombres de ruta y un resolvedor de URLs.

Otros middlewares notables: `SystemApprovals\SystemApprovalsMiddleware`,
`ProtectFileMiddleware` (protección de archivos estáticos, validado por
`ServerStatics::protectFileMiddleware`).
