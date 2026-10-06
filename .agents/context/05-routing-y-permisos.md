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

#### El patrón se comprueba al registrarse (P57, `0e59bdeb`)

`RouteAdapter::routeSegment()` analiza cada patrón con `FastRoute\RouteParser\Std` (punto único: el constructor llama a
`name()` antes, y `RouteGroupAdapter::register()` vuelve a pasar por ahí con el segmento del grupo).

- **En local:** `\InvalidArgumentException` con el nombre, el patrón, el mensaje del analizador y el `archivo:línea` que la
  declaró (último marco interno del enrutador en `debug_backtrace()`; el primer marco externo devolvía `CliActions.php`).
- **Fuera de local:** no lanza. Apunta en `PiecesPHP\Core\Routing\InvalidRoutes` (`add`/`all`/`clear`, sin disco), llama a
  `log_exception()` y `register()` se salta esa ruta. El aviso `invalid-route-pattern` (DANGER, root, flotante) la enseña.
- **Límite:** mira el patrón PROPIO, no el que resulta de pegarle el prefijo del `RouteGroup`. Lo final lo analiza
  `bin/check-routes`, que sigue siendo obligatorio y funciona aunque la aplicación no arranque. Suite
  `core/route-validation` (11), provocada: sin el `parse()`, la ruta mala entra en el enrutador (322 → 323).

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

## Cómo se conceden los permisos de verdad (medido en `#459`-`#461`)

**El catálogo de permisos lo rellenan las PROPIAS RUTAS al arrancar.** `set_route()`
(`AppHelpers.php:2345-2356`) registra la ruta y, por cada rol de su `rolesAllowed`, llama a
`Roles::addPermission($nombre, $rol)`. El catálogo escrito a mano de `roles.php` solo tiene ocho nombres.

Consecuencias, las tres medidas:

1. **`rolesAllowed` NO se aplica en la petición**: se traduce a permisos en el arranque, y quien decide en la petición
   es `Roles::hasPermissions()` (`index.php:742`), cuyo 403 (`:752`) **exige además `require_login`**.
2. **Una ruta con `requireLogin` y sin roles queda cerrada para TODOS**, root incluido, no abierta a los que entren.
   Así estaban las cinco `locations-*-ajax-search` (`3453b05e`).
3. **Una ruta sin `requireLogin` que declara roles no aplica ninguno**: son inertes para el 403 — pero NO son
   decoración, porque `verify-integrity` exige declarar login **o** roles para que nadie deje una ruta pública por
   descuido dentro de un módulo guardado.

> **El nombre de una ruta no se puede repetir, y quien lo impide es el framework, no Slim** (medido el 2026-10-02,
> `#763`). **Slim calla**: acepta dos rutas con el mismo nombre y `getNamedRoute()` devuelve la primera, sin aviso.
> `set_route()` (`app/core/AppHelpers.php:2362`) lanza `RouteDuplicateNameException` si el nombre ya está en
> `$routesSetted`. **RESUELTO el 2026-10-02 (`#765`): el router nunca tuvo un duplicado; duplicaba el instrumento.**
> `set_route()` guarda la ruta dos veces, `$routesSetted[$name]` y `$routesSetted[$alias]`, y las dos entradas llevan
> dentro el `name` principal; `RouteInventoryTask` leía el **campo** `name` en vez de la **clave**, así que
> `terminal-help` salía dos veces y **`terminal-h`, que es una ruta real, no salía**: el total cuadraba en 343 por
> casualidad. Siete consumidores comían ese universo (dos comprobaciones de `verify-integrity`, `walk-routes`,
> `walk-attribute`, `walk-matrix`, `censo-consumidores` y `core/maintenance-mode`). Lo vigila
> `core/route-inventory`, que coteja ROUTER contra ARTEFACTO con dos canarios.

> **`requireLogin` NO sirve para decir «esta ruta es pública»** (medido el 2026-10-02, `#773`): de las **99** rutas
> que no lo exigen, **unas 25 no son páginas**: son las de servir estáticos de cada módulo, y su nombre es el de su
> clase `Routes` (`MySpace\MySpaceRoutes`, `Publications\PublicationsRoutes`…). Un censo que cruce por ahí cuenta
> medio framework como público.

> **El alias de una ruta está RETIRADO** (bloque CX, 2026-10-02, decisión del PO en P92). Ver la sección «El alias de
> una ruta» más abajo, y la **comprobación 42**, que impide volver a declararlo. **Nunca dio 403**: heredaba el
> permiso, porque `Roles::hasPermissions()` resuelve la clave al nombre; el arquitecto escribió aquí lo contrario el
> 2026-10-02 y se lo dijo al PO (`pendientes.md` 303.1).

> **EL ORDEN DE LOS GRUPOS MANDA, Y NO ES NEGOCIABLE** (medido el 2026-10-02, `#765`). `initRoutes()` recorre
> `self::$groups` en el orden en que se registraron, y FastRoute **rechaza una ruta estática que quede ensombrecida
> por una comodín anterior**: no es un aviso, es que **la aplicación no arranca**
> (`BadRouteException: Static route "/robots.txt" is shadowed by previously defined variable route "/([^/]+)"`).
> Por eso `src/app/config/routes.php` pasa a `SiteFilesController::routes()` **la instancia de la zona pública**
> cuando no hay prefijo de idioma, en vez de un grupo nuevo: así los cuatro archivos para buscadores entran ANTES de
> la comodín de esa zona. **Ese ternario no es un apaño del defecto de los grupos y no se «limpia»**: el arquitecto
> dictó retirarlo en el bloque CS y el arranque lo tumbó en el acto.

> **Y de ahí el precio del arreglo del CR**: un grupo con prefijo repetido ya no pierde sus rutas en silencio, se
> registra **al final**. Para un clon eso puede convertir «me faltan rutas» en «no arranca», que es el fallo que
> queremos —alto y en el acto—, pero hay que saberlo (`CHANGELOG`, bloque CR).

> **Un grupo con un prefijo ya usado ya no pierde sus rutas** (bloque CR): `RouteGroupAdapter::register()` guarda por
> `spl_object_id($this)`, no por prefijo. Su único consumidor es `initRoutes()`, en la propia clase. Lo cubre
> `core/route-groups-same-prefix`.

> **Un censo de grupos por nombre de clase es CIEGO**: `routes.php` importa `RouteGroup as PiecesRouteGroup`, así que
> los 19 grupos reales se escriben `new PiecesRouteGroup(…)`. Es el único alias de `src/`, y está justo ahí (LEY 16).

**Defecto abierto:** `set_route()` registra el ALIAS en el catálogo de rutas pero concede el permiso **solo al nombre**,
así que un alias con login responde 403 a todos. Hoy solo hay uno (`terminal-h`, alias de `terminal-help`). Arreglarlo
toca núcleo transversal. Lo declara la puerta `core/permissions-coherence` (8 comprobaciones), que también vigila que
ningún tipo de `TYPES_USERS` se quede sin rol —el caso de `GOOGLE_PLAY`, comentado, que haría que sus usuarios no
pasaran por la capa 2—.

## Leer al usuario conectado

`getLoggedFrameworkUser()` devuelve el paquete de sesión **o null**; `getLoggedFrameworkUserOrFail()` lanza
`PiecesPHP\Core\Exceptions\SessionRequiredException` (que extiende `BaseException` y, con ella, `\Exception`).

**Las dos capas fallan CERRADAS desde el 2026-09-23** (`0d23dd59`): la primera (`src/index.php`, bloque de
`:651`) responde 403 `RESTRICTED_AREA` a toda petición XHR sin sesión, sin excepciones —una cabecera de la petición no
autoriza nada—; y la segunda (`:746-752`, el `if` en `:748`) niega en cuanto `require_login` es true y no hay sesión, en vez de abstenerse
porque `$has_permissions` sea null. **Trampa latente:** `users-form-login` está exenta de la PRIMERA capa por su nombre
(`:653`), pero **no de la segunda**; hoy no le afecta porque declara `require_login = false` (medido en el inventario).
Si alguien se la pusiera en true, el formulario de acceso quedaría inaccesible. Puerta:
`core/access-without-session`, con efecto de red.

**Quién ve qué cuando falta la sesión** (medido el 2026-09-23 con la matriz: 204 rutas sin sesión, 112 redirigen y
**ninguna da 500**): `src/index.php` §8 (`:643-677`) corta ANTES del controlador. Petición normal → redirección al login
con la URL pedida guardada para volver; petición de datos → 403 `RESTRICTED_AREA`. `…OrFail()` es la segunda línea:
solo salta en una ruta sin `requireLogin` o con `control_access_login` apagado. **No confundir** «la llamada sube al
manejador» (lo que hace el código si llega) con «el usuario ve un error» (lo que no pasa): esa confusión produjo P65.

**La regla, desde el tramo 4 de 11a (2026-09-23):** si el camino exige sesión —una ruta con `requireLogin`, una vista
del panel, un mapper al que solo se llega desde ahí—, se usa `…OrFail()`. Si el camino puede alcanzarse sin sesión, se
comprueba el null y se decide qué responder; **nunca se desreferencia sin comprobar**.

Estado medido: 176 lecturas, de las que 65 ya son `…OrFail()`, 94 comprueban, 4 están explicadas y 4 son lecturas
muertas. **Ninguna suite ejecuta la mayoría de esos sitios**: lo que los vigila es el trinquete de PHPStan.

## Generar URLs — nunca las escribas a mano

```php
Controller::routeName('list');                          // URL de <base>-list
Controller::routeName('forms-edit', ['id' => 4]);       // con parámetros
Controller::routeName('list', [], true);                // silentOnNotExists
Controller::allowedRoute('list');                       // bool: ¿el usuario puede?
get_route_info($nombre); get_routes(); get_route_by_controller(...);
```

**`get_route('nombre-literal')` está vetado** (P36 fase 2, 2026-09-18). La comprobación `GET_ROUTE` de
`bin/cli verify-integrity` recorre `src/app` y `src/index.php` con el tokenizador de PHP (no con grep) y falla ante una
llamada con nombre literal que no esté en `files/dev/get-route-direct-allowed.json`, o si esa lista tiene una entrada
que ya no se usa. Excepciones hoy, por nombre y con motivo: `push-avatars` y `pcsphp-testing-queue-request-handle`
(rutas del núcleo sin controlador con el trait) y `admin-global-variables-css` (el CSS de variables, una closure que
piden páginas sin sesión, como 403, 404 y mantenimiento). Quedan fuera del veto: las pruebas de `local-tests/`, que
comparan contra `get_route()` a propósito como testigo independiente, y las llamadas con un nombre calculado (el propio
trait, `AppHelpers`, `RouteInventoryTask`), que la comprobación solo cuenta.

**`routeName()` no es un `get_route()` con otro nombre:** consulta los permisos. En una ruta pública (sin roles ni
`require_login`) concede a cualquier rol, así que nunca da `''`. En una protegida da `''` a quien no tiene permiso. Por
eso un enlace a una ruta protegida en una pieza compartida (barra superior, cabecera, menú, tarjetas) **se pinta solo si
`routeName()` no es `''`**; dentro de una página que ya exige esos mismos roles, directo.

`routeName()` devuelve **cadena vacía si el usuario actual no tiene permiso** —
por eso `allowedRoute()` es simplemente `strlen(routeName(...)) > 0`, y por eso los
menús usan `'visible' => Controller::allowedRoute('x')`.

### Por qué `routeName()` / `allowedRoute()` están en cada controlador

**No estaban en ninguna clase base: 44 controladores las reimplementaban** (T12; hoy viven en un trait, ver
abajo). No era descuido — era consecuencia de tres cosas del diseño:

1. **Son piezas nucleares del acoplamiento controlador↔Slim.** Traducen
   `$baseRouteName + sufijo` a una ruta Slim registrada y, de paso, resuelven el
   permiso. No son un helper accesorio.
2. **No todos los controladores comparten padre.** 43 extienden
   `AdminPanelController`, y 3 de zona pública extienden `BaseController` directamente
   (`PublicAreaController`, `PublicationsPublicController`,
   `BuiltInBannerPublicController`) —eran 4 hasta que `ApplicationCallsPublicController`
   se fue con su módulo en el bloque AB— y
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
`return true;` por defecto. **Lo usan 46 controladores** (medido el 2026-09-18 con
`git grep -l "use ControllerRoutingTrait;" -- src/app`; eran 41 antes de P36).

**Los controladores del sistema también** (P36, ruptura 32): `UsersController` (`users`), `LoginAttemptsController`
(`login-attempts`), `AdminPanelController` (`admin`), `GenericTokenController` (`generic-token`) y `TimerController`
(`timing`). Trampa de herencia: `AdminPanelController` es padre de 43 clases y, dentro del trait, `self::` es la clase
que declara el `use`; **una hija sin `use` propio hereda el prefijo `admin`** (`UsersController::routeName('list')`
daría `admin-list`). Por eso toda hija que tenga rutas propias declara su `use` y su `$baseRouteName`; la suite
`system-controllers-routing` lo vigila. `RecoveryPasswordController` y `UserProblemsController` heredan a propósito el
de `UsersController`.

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

**`SystemApprovalsMiddleware` y los usuarios no aprobados:** a un usuario pendiente de aprobación solo le quedan los
permisos generales más los que `SystemApprovalsMiddleware::keepsWhenNotApproved()` conserva de su rol: el nombre exacto
`users-edit-request`, el prefijo `user-system-features-` y los prefijos de perfil propio del mismo método. **No se usa
`users-` como prefijo**: daría a un administrador no aprobado la gestión de usuarios. Una ruta nueva que un usuario no
aprobado deba poder usar se añade ahí, con su prueba.

Otros middlewares notables: `SystemApprovals\SystemApprovalsMiddleware`,
`ProtectFileMiddleware` (protección de archivos estáticos, validado por
`ServerStatics::protectFileMiddleware`).

## El alias de una ruta: RETIRADO el 2026-10-02 (bloque CX)

**El mecanismo ya no existe.** Lo retiró el bloque CX por decisión del PO (P92: «si es fácil y rápido, remuevan ese
mecanismo»), y `set_route()` no registra segunda ruta, ni middlewares para ella, ni entrada propia en el catálogo.
**`bin/cli h` ya no existe; se usa `bin/cli help`.** Lo vigila la **comprobación 42** de `verify-integrity`, con cota
**0**: ninguna ruta ni tarea puede declarar un alias.

- **El sexto parámetro de `Route`/`RouteAdapter` SE QUEDA, ignorado y documentado**: hay **221 declaraciones de
  `new Route(` con seis o más argumentos posicionales** (de 317 en total; medido por tokens el 2026-10-02), así que
  retirarlo desplazaría `rolesAllowed` en todas ellas y en las de cualquier clon.
- **`RouteAdapter::alias()` también se queda**: lo llaman tres sitios dentro de la propia clase (`:99`, `:448` el
  `toArray()`, `:507` la factoría). Un censo con `->alias(` daba cero y era ciego.
- **`toArray()` sigue emitiendo `route_alias`** (`:448`), que desde la retirada vale siempre el nombre de la propia ruta:
  campo sin significado, pendiente de limpieza (`pendientes.md` 303.10).

Lo de abajo describe **cómo funcionaba** y se conserva porque explica por qué el inventario y la matriz lo veían (o
no) y por qué una creencia falsa sobre él duró semanas.

## Cómo funcionaba el alias (medido el 2026-09-24, retirado el 2026-10-02)

**Lo que se creía y era falso:** que un alias quedaba sin permiso y respondía «prohibido» a todo el mundo. **No es
cierto y está ejecutado:** `bin/cli help` y `bin/cli h` hacen lo mismo.

**Cómo funciona de verdad**, con las tres piezas:

1. **`RouteAdapter::__construct()`** lo toma como **sexto parámetro posicional**
   (`src/app/core/psr4/PiecesPHP/Core/Routing/RouteAdapter.php:79-89`), y en `:98` hace
   `$this->alias($alias == null ? $name : $alias)`: **sin alias explícito, el alias ES el nombre**.
2. **`set_route()`** registra **dos** rutas en el enrutador: el nombre en `AppHelpers.php:2317` y **el alias como
   PATRÓN DE URL** en `:2323`. Por eso un alias **no es un segundo nombre de la misma dirección: es una segunda
   dirección, más corta, al mismo controlador**. Y en `:2347` guarda `$routesSetted[$alias]` con **el mismo array**
   que el del nombre. El permiso se concede solo al nombre (`:2353`).
3. **`Roles::hasPermissions()`** (`Roles.php:319-327`) busca la clave —que puede ser el alias—, y **a partir de ahí
   usa `$route['name']`**. Es decir: **resuelve la clave al nombre antes de comprobar**. Por eso el alias hereda el
   permiso sin que nadie se lo conceda.

**Trampas medidas, las tres:**

- **NINGÚN instrumento nuestro distingue un alias de su ruta**, desde el 2026-09-24. `RouteInventoryTask` recorre
  `get_routes()` y toma el `name` **del valor**, no la clave, así que la entrada del alias se colapsa con la de su
  ruta: no está en el inventario de 323, ni en la matriz, ni en los caminantes, ni en el censo de rutas, que parten
  todos de ahí. La puerta `core/permissions-coherence` era la única que los veía —porque lee claves— **y ahora también
  los resuelve al nombre**, que es lo correcto para medir permisos.
  **Hoy eso está bien**, porque lo que importa es el permiso y el permiso se hereda. **Pero si alguien quiere usar
  alias en serio, lo primero es que el inventario los liste como entradas propias**; hasta entonces, un alias es
  invisible para todo lo que medimos.
- **Hoy hay UN alias en todo el framework**: `terminal-h`, compuesto en `HelpTask.php:40` y pasado a la ruta en
  `:115`. Medido sobre 296 declaraciones de `new Route(`, de las que 205 pasan seis o más argumentos posicionales.
  *(La cita decía `:98` hasta el 2026-09-24. Salió de un analizador que **quitaba los comentarios antes de contar
  líneas**, así que sus números eran los del archivo sin comentarios: once líneas de desfase. **Una cita se sigue, así
  que una cita mal medida manda a quien la sigue a otro sitio.**)*
- **El sexto parámetro no se puede quitar.** Esas 205 declaraciones —y las de cualquier clon— cuentan las posiciones,
  así que retirarlo **desplazaría `rolesAllowed`** en todas. Si algún día se retira el mecanismo, **el parámetro se
  queda, ignorado y documentado como tal**, o se rompe el enrutado de cada clon.

**Y por qué esto estuvo mal escrito un día entero:** se dedujo del productor —`set_route()` concede solo al nombre—
sin mirar el consumidor —`hasPermissions()` resuelve la clave—. Es la **LEY 19** literal, incumplida por el arquitecto
y por el coder a la vez, y corregida al ejecutar `bin/cli h`.

