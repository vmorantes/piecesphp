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

> **HECHO.** El vehículo es un **trait** y no una clase base: los `*PublicController`
> extienden `BaseController`, no `AdminPanelController`, así que componer es lo único que
> funciona en las dos jerarquías. Dentro del trait `self::` sigue resolviendo a la clase que
> lo usa, de modo que `self::$baseRouteName` sigue siendo la del módulo.

### Un solo trait: `PiecesPHP\Core\Routing\ControllerRoutingTrait`

Aporta los **tres** métodos: `routeName()`, `allowedRoute()` y `_allowedRoute()` con
`return true;` por defecto. **Lo usan los 44 controladores.**

Empezó siendo dos —uno de nombrado y uno de guarda— y **la frontera era inventada**: los
tres métodos no se pueden separar. `routeName()` llama SIEMPRE a `_allowedRoute()`, y
`allowedRoute()` no hace más que preguntarle a `routeName()` si devolvió cadena.

### Qué queda escrito en un controlador

**Nada, salvo que tenga reglas de negocio propias.** Y entonces se escribe **solo**
`_allowedRoute()`.

| Método | Copias borradas | Sobreviven | Registradas en |
| :-- | --: | --: | :-- |
| `routeName` | 35 | **9** | `VerifyIntegrityTask::KNOWN_ROUTE_OVERRIDES` |
| `allowedRoute` | 37 | **1** | ídem |
| `_allowedRoute` | 17 | **15** | ídem |

### EL CRITERIO ES UNO: ¿este método DECIDE algo?

No es el parecido con el cuerpo canónico. Ese fue el criterio de la primera pasada y **dejó
vivas dieciséis copias que no hacían nada**: cuerpos que solo devuelven si la ruta vino
vacía —lo mismo que el trait—, con diferencias de forma (`strlen($route) > 0` contra
`$route !== ''`), un closure `$getParam` que nadie llama, variables `$currentUserType` que
se asignan y no se leen, y un `if` de relleno comparando contra `'SAMPLE'`, `'sample'` o
`'NOMBRE_RUTA'`.

> **No eran variantes de un comportamiento: eran estratos de una plantilla y huecos que
> nadie rellenó.**

Las que sobreviven lo hacen por una de dos razones, y **cada una está escrita en el
registro**:

| Razón | Cuántas |
| :-- | --: |
| **Estructural**: nombran la ruta de otra forma o tienen otra firma, así que el trait no puede servirles | 10 |
| **Decide de verdad**: reglas de autorización propias | 15 |

Las estructurales son `App\Locations` (prefijo de dos niveles), `ContactForms` y
`PublicArea` (usan `$prefixNameRoutes` y no declaran `$baseRouteName`), `Terminal` (otra
firma) y `DataImportExportUtility` (toma el usuario de otra fuente).

### La puerta

`bin/cli verify-integrity` falla si **un controlador declara uno de los tres sin estar
registrado**, si **una entrada del registro ha dejado de decidir algo**, o si **una entrada
apunta a una declaración que ya no existe**. El veredicto lo da el **mismo clasificador con
el que se construyó el registro**, para que la puerta no pueda separarse del criterio.

### Los tres patrones de sobreescritura, para copiar

Ver [13-recetas.md](./13-recetas.md), con un ejemplo de cada uno:

1. **Propiedad del recurso** — solo el creador, o un tipo con permiso global.
2. **Conflicto de interés** — nadie se aprueba a sí mismo.
3. **Registro protegido** — hay una fila que no se borra nunca.

> **Al crear un módulo nuevo:** `use ControllerRoutingTrait;` y nada más. La referencia
> sigue siendo `Publications\Controllers\PublicationsController`.
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
