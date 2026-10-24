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
└── lang/{es.php,en.php}
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
```bash
bin/cli scheme-create module=MiModulo
```
Emite el `CREATE TABLE` ordenado —padres antes que hijas— y **no lo ejecuta**: se revisa, se
aplica a mano, y se añade a `databases/piecesphp_structure.sql`. El inverso es
`bin/cli scheme-drop module=MiModulo`.

**Declara `bigint` en las claves ajenas que apunten a `pcsphp_users.id`**, que es `bigint`.
Con `int` MariaDB rechaza la tabla con `errno 150`. Lo vigila
`bin/cli unit-tests:core/scheme-sql-round-trip`.

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
use PiecesPHP\Core\Statics\ServerStatics;
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

### 7. `MiModuloController.php`
Extiende `App\Controller\AdminPanelController`, define `$URLDirectory`,
`$baseRouteName`, `$title`, las constantes `BASE_VIEW_DIR` / `BASE_JS_DIR` /
`BASE_CSS_DIR` / `LANG_GROUP`, el constructor (modelo, título, `setInstanceViewDir`,
assets globales), los métodos de acción y `routes()`.

**`routeName()`, `allowedRoute()` y `_allowedRoute()` NO se copian.** Los aporta el trait:

```php
use PiecesPHP\Core\Routing\ControllerRoutingTrait;

class MiModuloController extends AdminPanelController
{
    use ControllerRoutingTrait;
```

Con eso el módulo ya nombra rutas y decide visibilidad de menús. **Solo se escribe
`_allowedRoute()` si el módulo tiene reglas de autorización propias** — ver la receta 9.

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

**Trampa medida dos veces (T120 y `#1447`): `action()` NO se reutiliza desde otra ruta.** Desde `7b96decd` (8.0.0) la
operación sale del NOMBRE de la ruta (`BaseController::isEditRoute()`, `BaseController.php:349-366`), que lanza si no
termina en `-actions-add` o `-actions-edit`. Quien llama a `action()` de otro controlador bajo una ruta suya rompe en
silencio: la API (`APIController`, arreglado con `createOrganization()`) y Mi organización (`saveProfileAction()`, que
no guardaba para nadie desde la 8.0.0; arreglado en `e90d413b`). **La forma**: el cuerpo en un método con la operación
explícita —`OrganizationsController::saveOrganization(Request, Response, bool $isEdit)`— y `action()` reducido a
`saveOrganization(…, self::isEditRoute($request))`; el otro camino autoriza primero y llama con la operación fijada. Y
la prueba de una pantalla que guarda comprueba un guardado CON ÉXITO (valor cambiado y devuelto), no solo los rechazos:
la de Mi organización solo probaba 403 y 404, y por eso nada lo vio.

---

## Receta 5 — Cronjob, cola, listener o acción CLI

No toques el núcleo: añade el registro en el include correspondiente de
`src/app/config/extensions/` (`cronjobs.php`, `queues.php`,
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

En `extensions/protected-files.php`
(clase: `PiecesPHP\Core\Statics\ProtectFileMiddleware`; el nombre viejo, en
`Helpers\Directories`, es un alias). La protección la da el NOMBRE en disco: un privado se
guarda como `foto.jpg.protected` y su URL no cambia (lote 4b; guía completa en
`source-docs/project/docs/piecesphp/new-features/protected-files.md`).

```php
use PiecesPHP\Core\Statics\ProtectFileMiddleware;

// Solo con sesión:
ProtectFileMiddleware::protectWithSession(append_to_path_system($uploadsDir, 'ruta/al/directorio'));

// Con un validador propio (falla cerrado si no se da ninguno):
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, 'otra/ruta'), [MiController::class, 'miValidador']);
```

Y el módulo guarda sus subidas privadas con `ProtectedUploads::privatePath()`, y las mixtas
según la visibilidad de su registro (`ProtectedUploads::setFolderVisibility()`, como
Publications).

## Receta 8b — Un importador o exportador de datos

Motor en `PiecesPHP\Core\DataTransfer` y panel en `DataImportExportUtility` (ADR 0022). Una definición
(`ImportDefinition` o `ExportDefinition`) y su registro en el `routes()` del módulo:
`DataImportExportUtilityRoutes::importer($grupo, MiDefinicion::class)` o `::exporter(...)`. Crea rutas con nombre propio
(`data-transfer-import-<key>`, `-action`, `-template`; `data-transfer-export-<key>` y `-form`): el permiso es el nombre.
Un exportador declara filtros con `parameters()` (`ExportParameter::text|integer|boolean|choice|multiChoice|date|dateRange`)
y los recibe validados en `columns(ExportContext)` y `rows(ExportContext)`; `ExportContext::get()` lanza con una clave no
declarada; un valor inválido da 400 y nunca llega a `rows()`. Clave `format` reservada. `ExportColumn` lleva tipo
(`asInteger|asDecimal|asMoney|asPercent|asDate|asDateTime|asBoolean`), `format()`, `width()`, `align()` y `transform()`;
un dato que no encaja con su tipo se escribe como texto, nunca lanza. `fileName()` opcional; el controlador lo limpia y
emite `filename` + `filename*`. XLSX monta el libro en memoria (+22 MiB medidos para 7 KB); CSV va fila a fila. Libro: `sheets()` (por defecto
`[mainSheet()]`), `ExportSheet` con `heading|subheading|appliedFilters|generatedAt|image|total|merge|style`, fórmulas por
columna con `{clave}` (solo del código; CSV las deja vacías), `ExportStyle::report()` por defecto, `afterBuild()` como
escape. `image()` exige realpath dentro de `basepath()`. El CSV ignora todo el libro. `afterExport(ExportContext, ExportResult)` corre tras generar y antes de enviar; si lanza
no hay descarga, 500 genérico y `log_exception()`; debe ser atómica. `importDefinition()` declara la doble vía y la
suite `data-transfer-roundtrip` la exige a todo exportador registrado que la declare. Trampas vistas: un fatal en una
suite de `local-tests/` tumba todo `bin/cli` (se cargan al arrancar); trabajo entre `tempnam()` y el `try` de limpieza
deja temporales huérfanos. Niveles: `interfaceLevel()` 0-3 (`checkInterface()` al registrar); 0 solo descarga, 1 +form,
2 y 3 +preview; `columns` reservada junto a `format`, aplicada solo en `mainSheet()`; la vista previa usa
`SpreadsheetExportWriter::firstRows()` (no se llama `preview` porque `bin/censo-formas-de-lectura` lo tomaría por forma de
lectura SQL); rutas de archivo del proyecto con `ProjectFile::resolve()`. Sin credenciales de prueba en el entorno del
coder, lo visual del panel solo se prueba por HTML y `node --check` (P53). Importación: `acceptedExtensions()` por
defecto `['xlsx']`; `InterfaceLevel` común (alias `INTERFACE_*`); nivel 0 del importador no registra rutas web;
`ImportRunner::run(..., $dryRun)` nunca llama a `persist()`; `Column::help()/example()` van a la plantilla como
comentario. Filtros guardados: `ExportPresetStore` (núcleo) y `UserMetaPresetStore` (módulo) en `users.meta`
→ `dataTransferPresets`, escritura SOLO de la columna `meta` tras releerla; el id siempre de la sesión.
**Trampa (`#294` H1): una provocación que quita la guarda de «no escribe» ESCRIBE de verdad; `bin/cli db-backup` antes.**
Trampas: `persist()` se llama solo con todas las filas válidas y **debe ser atómica** (transacción con la conexión
compartida, como `UsersImportDefinition`); `EntityMapper::save()` no deja el id en el objeto salvo que el modelo lo haga
(usa `getInsertIDOnSave()` o `getLastInsertID()`); un entregable (`ImportArtifacts`) nunca va a disco, log ni JSON; **una prueba de visibilidad por CLI da verde falso** si
no fija un usuario: `allowedRoute()` sin usuario concede (usa `Roles::hasPermissions` con el nombre completo, `#282`).
Guía para personas: `source-docs/project/docs/piecesphp/new-features/data-transfer.md` y sus páginas en
`data-transfer/` (panel, importador, exportador, referencia, migración). Su código sale por `pymdownx.snippets` de
`src/app/classes/DataImportExportUtility/Examples/` (marcadores `// --8<-- [start:x]`); la suite `data-transfer-examples`
los ejecuta y comprueba los marcadores. **Mover o renombrar un marcador rompe el build estricto de la guía.** Portada: `.tabs-controls` + `ui tab` (doc 16),
`ui basic table`; `description()` opcional; interruptor por definición en `data_transfer_disabled` con
`DataTransferController::isDisabled()` en TODAS las entradas (403, también root y terminal) y ruta `data-transfer-toggle`
solo root, registrada en EventsLog. Trampas (`#304`): un patrón de ruta inválido (`[/]toggle`) tumba TODO el enrutado,
CLI incluido; `<td<?= … ?>>` confunde al contador de etiquetas de `verify-integrity`; en Fomantic `tr.disabled` anula los
clics de la fila entera.

## Receta 8c — Un aviso del sistema

`\PiecesPHP\SystemStatus\SystemAlertRegistry::register(new SystemAlert(key, severidad, fn mensaje, fn activo,
ocultable, audiencia, flotante, ruta de arreglo, etiqueta))` en el `routes()` del módulo. **El activo de un aviso
flotante corre en cada página del panel: barato.** Uno caro, sin flotante (como `server-delegated-broken-links`). Si
el activo lanza, el aviso es inactivo y va a `log_exception()`. Guía: `source-docs/.../new-features/system-status.md`.
Enlaces de `server-delegated`: `ServerDelegatedLinks::scan()/deleteBroken()` (nunca siguen un enlace); la vista
Mantenimiento lo hace como `www-data`, sin privilegios de administrador del sistema.

## Receta 8d — Los datos de prueba de un módulo

Una clase en la carpeta `Seeds/` del módulo (`src/app/classes/<Modulo>/`) que extienda `Terminal\Seeding\ModuleSeeder` (`mapper()`,
`markerColumn()`, `row()`; opcionales `dependsOn()`, `ownedRows()`, `ownedFolder()`, `defaultCount()`). Con archivos:
`SeedContext::copyToUploads()` y `ownedFolder()`; modelo, `Publications/Seeds/PublicationsSeeder.php`. La descubre `SeedPlanner` con el
mismo recorrido de `scheme-create`. `bin/cli seed module=all` simula; `escribir=yes` y `limpiar=yes` solo con
`module=all` y solo en local (`--local` **y** `environment.php` en `local`). Modelo con dependencia:
`PiecesPHP/App/Locations/Seeds/StatesSeeder.php`. Guía: `source-docs/.../new-features/datos-de-prueba.md`.
**Trampas medidas (bloques DC y DE, `#1145`-`#1157`):**
- `requiredFields()` mira el VALOR de `default`, no su presencia: el ORM rellena `'default' => null` en toda opción que
  falte (`EntityMapper.php:1207`). Con `array_key_exists` daba `[]` en los 29 mappers. Lo vigilan el canario
  (`username` de usuarios) y la comprobación 3f de `unit-tests:core/seed-planner`.
- **Simular no llama a `save()`**: lo que el mapper exige al guardar solo sale con `escribir=yes`.
- **`bin/cli seed` tiene sesión (usuario id 1 FIJO, `src/index.php:313-326`); las suites (`CliActions`) NO.**
  `unit-tests:core/seed-round-trip` fija la del usuario principal (`set_config('current_user', …)`) y la restaura en un
  `finally`, como `UnitTest-UsersRegisterProfile.php:67-68` y `:99-100`.
- **Las aprobaciones**: `SystemApprovalManager::init()` crea en cada arranque una fila por organización, usuario y
  publicación sin ella (`referenceTable` = tabla, `referenceValue` = id como texto). Se declaran con
  `ownedRows()` en su forma con `where` (`referenceTable`). **Sin el `where`, el `IN` borra aprobaciones de otra tabla
  con el mismo id**: lo caza la aprobación señuelo de `seed-round-trip`. En un mismo proceso no existen hasta que se
  llama a `init()`: la suite lo llama tras escribir. **Se salta** lo que su manejador excluya con
  `excludesElement(int $id): bool` (de `BaseApprovalHandler`, no de la interfaz; `UsersApprovalHandler` excluye al
  usuario de sistema con `UsersModel::isSystemUser()`): un manejador que solo implemente la interfaz no lo tiene.
- **Archivos: NUNCA apuntar a un archivo versionado.** Al sustituir la imagen, cinco controladores hacen `unlink()` del
  anterior sin protección (Publications, BuiltInBanner, Documents, Organizations, GenericContent); NewsCategory se salta
  su `DEFAULT_ICON`. Se copia con `copyToUploads()` (origen en `statics/images/`, carpeta con el prefijo; privado =
  sufijo de `ProtectedUploads`, como nace la subida de esa tabla) y se borra la carpeta entera con `ownedFolder()`.
  Guardas en `SeedPlanner::ownedFolderPath()`: base en `statics/uploads/`, valor `^zz-siembra-[A-Za-z0-9_-]+$`, ruta
  real dentro de la base, sin enlaces. **`src/statics/uploads/` está ignorado por git**: los restos se buscan con
  `find src/statics/uploads -maxdepth 3 -name 'zz-siembra-*'`.
- **Una publicación sembrada por el usuario principal se autoaprueba**, y siendo activa y en fechas es visible: sus
  archivos se sirven SIN sesión aunque en disco sigan con `.protected` (`ServerStatics` la valida). Los documentos sin sesión dan 403 (medido); con sesión, sin medir.
- **Una lista exacta de seeders en una suite** («y solo esos») se actualiza en el MISMO commit que añade el seeder.
- **Medir por HTTP ensucia el punto de partida**: el login deja una fila en `login_attempts` y `gates` da «SUCIO». La
  ronda que pida mediciones HTTP y «partió LIMPIA» lleva el borrado de esa fila por id en su plan. **La pasada de
  `bin/navegador` ya no deja filas** (HV, 2026-10-22): marca antes y borra al terminar las suyas, por id y solo las
  `zz-agente-`, con `bin/cli walk-login-attempts mark=yes` y `prune=yes after-id=<n>` (`src/app/core/extensions/
  walk-login-attempts.php`, solo en local y excluida de la distribución), y lo dice en su última línea. Lo de abajo
  queda para las mediciones por HTTP que no pasan por el recorrido.
  **Cómo, sin cliente de base de datos** (`#1198`, 2026-10-09): una acción temporal `zz-scratch:` escrita en el
  scratchpad (`CliActions::make(...)->register()`, `(new \PiecesPHP\Core\BaseModel())->getDatabase()` y sentencias
  preparadas), que lee `id, userID, success, date` y si el usuario empieza por `zz-agente-` (sin imprimirlo), con
  `date >= <hora del db-backup>`, y borra con
  `WHERE id IN (…) AND date >= ?` —la fecha acota el borrado aunque un id venga mal—. Se carga **copiándola** a
  `src/app/core/system-controllers/local-tests/` (todo `.php` de ahí se incluye al arrancar `bin/cli`), y copia,
  ejecución, `rm` y `git status` van en **una sola llamada de shell**. **La ruta del `rm` va literal**: la guarda de
  hooks bloquea un `rm` sobre una ruta en variable, y al bloquear para la llamada entera.
  **Con el perfil persistente de `bin/navegador` y la sesión aún viva no se crea fila**: solo la deja la primera
  entrada, o la de después de caducar. La lectura se hace igual y el reporte dice «0 filas» (`#1204`).
- **Las capturas para el PO** (ADR 0058) viven en `~/Documentos/PiecesPHP/`. **Lo que él mira es PLANO: una carpeta por
  resolución con todas las imágenes**, `todas-1920x1080/` y `todas-390x844/` (el PO, 2026-10-17: «con todo es todo, no
  jerarquías»; la `panel/` por secciones se borró por su orden, P183). **Es la ÚNICA captura** (P184, GM): `bin/navegador
  --por-secciones /home/vmorantes/Documentos/PiecesPHP` escribe las dos, con `NNNN-<sección>-<módulo>-<pantalla>.png` de 10
  en 10 (sin repetir segmentos: `0010-inicio.png`), el mismo número en los dos anchos, las variantes por tipo de usuario
  (alta y edición del primero de cada tipo con sus pestañas) y un `manifiesto.json` por carpeta con el commit, los
  archivos sucios (su `git hash-object`) y la «receta» de cada pantalla. **Después captura SOLO lo tocado** (el PO, «¿todo
  siempre?»): pide `bin/cli gates areas changed-since=<commit del manifiesto> unchanged=<sucios>` (ADR 0062: `AREA_MODULES`
  de `src/app/classes/Terminal/Tasks/GatesTask.php` es la única lista); si algo tocado no es documentación ni de un módulo,
  lo rehace TODO y dice qué lo forzó (**los artefactos de instrumentos, `files/dev/` y `PHPStanResult.*` de la raíz, no
  fuerzan nada desde HA**: no cambian una pantalla, y `gates areas` los devuelve aparte, en `ignored`); si no, solo las pantallas de los módulos tocados, en su sitio y con su número. **Desde HZ cuenta también lo que la pantalla PIDE**: cada fila del manifiesto guarda `modulosObservados`, los módulos de las rutas que la página pidió (documento, XHR y `fetch`) mientras se producía, y una fila se rehace si su módulo o uno de los observados está tocado; un panel o una pantalla sin sesión con un observado tocado rehace su panel o el recorrido sin sesión enteros. Un manifiesto sin ese campo, el de antes de HZ, se comporta como antes hasta la siguiente pasada entera. **Ojo, medido en HZ (`#1737` H2)**: la barra superior pide a `News`, `LocalizationSystem` y `Settings` en TODA pantalla del panel, así que tocar uno de esos tres rehace casi todo (263 de 277, 9 min): es la verdad, porque la barra cambia con ellos; y las filas sin sesión no observan nada, porque las rutas de usuarios son núcleo; lo que
  desaparece va a `archivo/<fecha>-retiradas/`. Medido en GM (`#1551`): todo, unos 10 min; un módulo, 31,5 s; nada,
  0,76 s. `--todo` lo fuerza. **Ojo**: tocar el código de `admin` o de `users-list` lo fuerza todo, porque
  `AdminPanel/` y `UserSystem/` son núcleo y no están en `AREA_MODULES` (no es la ruta la que fuerza, es su código). Las de cada ronda van a `rondas/<fecha-tema>/` y lo viejo a `archivo/`. **El «antes y después» de una ronda** se hace con el mismo instrumento, por lista de direcciones, y desde HZ también con los correos tapados: `bin/navegador --pantallas <ruta>,<ruta> --capturas /home/vmorantes/Documentos/PiecesPHP/rondas/<fecha-tema>/antes`, y lo mismo con `despues` tras el cambio (`--ancho 390` para el móvil). El enmascarado es el mismo de la plana (`bin/tools/navegador/enmascarado.js`). El recorrido sale
  del DOM (nada tecleado), solo hace GET y **sustituye todo correo por `correo@ejemplo.com`** antes de cada captura, porque
  el PO las comparte con terceros: en el texto, en el valor y el `placeholder` de los campos y en **cualquier atributo** menos `src` y `srcset`, que
  recargarían la imagen (`data-tooltip`, `title`, `href` de un `mailto:`…; una imagen «logo@2x.png» casaría como correo), y también **un correo recortado por la vista** («nombre@dom…»,
  `user-card.php:74` recorta a 30 caracteres). Desde HZ; antes solo miraba el texto entero, `title` y los campos, y la
  plana enseñaba los correos recortados de las tarjetas de usuarios (`#1737`). **No tapa** un correo recortado ANTES de su
  `@`, ni uno pintado en una imagen, un `canvas`, un `content` de CSS o un iframe de otro origen. Su «administrador general» entra hoy como root (`abierto.md` G17). La guarda no deja
  borrar ahí: lo viejo se mueve, y lo que el PO mande borrar se mueve a un temporal propio y se borra allí (regla 40 §3).
- **La carpeta para el diseñador es una COPIA, no una captura** (P184): `bin/navegador --por-secciones --para-el-disenador
  <destino> --plana /home/vmorantes/Documentos/PiecesPHP` copia de `todas-1920x1080/` las pantallas de los tres paneles de
  la barra con sus variantes: «Lateral Administrativo» con una subcarpeta por grupo del panel y, en su raíz, el panel y el
  índice de Configuración; «Lateral Perfil» y «Lateral Noticias», planas. Sin «Importar usuarios»
  (`data-transfer-import-users`: el PO, «es un módulo»). Comprobado en GM: 78 de 78 con el mismo sha1 que su origen,
  0,17 s. Sin `--plana`, la busca en la carpeta que contiene al destino. Si el destino existe, para. **Pedir la URL de
  datos del listado de usuarios ESCRIBE** si un usuario tiene un rol que no existe (`abierto.md` H20). Un cierre de
  Chromium a mitad («Target closed») no se recupera: se pierde el resto de ese ancho y hay que repetir (`#1539`).
- **La base local SE QUEDA SEMBRADA entre rondas** (PO, 2026-10-09 tarde: «siembra en todo, porque así no puedo revisar
  bien»). Pero lo sembrado lleva `zz-`, `gates` lo cuenta como resto (ADR 0047) y `unit-tests:core/seed-round-trip` exige
  que no haya nada sembrado antes de empezar (`UnitTest-SeedRoundTrip.php:80-81`, su caso c3). Así que toda ronda, en este
  orden: **al empezar**, `seed module=all limpiar=yes`; `db-backup`; la siembra y las mediciones que la ronda pida; **antes
  de verificar**, `seed module=all limpiar=yes` otra vez y el borrado de `login_attempts` de arriba; `bin/verify`; y **al
  final, después de verificar y antes del commit del estado**, `seed module=all escribir=yes`, que es lo que el PO
  encuentra. El reporte dice qué quedó sembrado (módulos e ids). Mientras corre una ronda, la base puede estar vacía.

## Receta 9 — Reglas de autorización propias con `_allowedRoute()`

`routeName()` ya comprueba los roles. **`_allowedRoute()` es para lo que los roles no pueden
saber**: si este usuario concreto puede tocar este registro concreto. Devuelve `bool`; el
trait trae `return true;` por defecto, así que **si el módulo no tiene reglas extra no se
escribe nada**.

Toda sobreescritura tiene que estar registrada en
`Terminal\Tasks\VerifyIntegrityTask::KNOWN_ROUTE_OVERRIDES` con su razón, y
`bin/cli verify-integrity` falla si no lo está — **o si deja de decidir algo**.

En el proyecto hay **tres patrones**, y conviene reconocer cuál se necesita antes de
escribir.

### Patrón 1 · Propiedad del recurso

El más común: **solo el creador toca lo suyo**, salvo un tipo de usuario con permiso global.
La variante completa añade la organización. Se extrajo de `ApplicationCallsController`,
**borrado en el lote 3 de E3**; el patrón sigue vigente y hoy se ve igual en
`PublicationsController`.

```php
private static function _allowedRoute(string $name, string $route, array $params = [])
{
    $allow = $route !== '';

    if (!$allow) {
        return false;
    }

    $currentUser = getLoggedFrameworkUser();

    if ($currentUser === null) {
        return $allow;
    }

    //Borrar y editar se comprueban IGUAL: cambia solo la constante de permiso global.
    $globalPermissionByRoute = [
        'actions-delete' => MiModuloMapper::CAN_DELETE_ALL,
        'forms-edit' => MiModuloMapper::CAN_EDIT_ALL,
    ];

    if (!array_key_exists($name, $globalPermissionByRoute)) {
        return $allow;
    }

    $element = MiModuloMapper::getBy($params['id'] ?? null, 'id');

    if ($element === null) {
        return false;
    }

    //Es suyo.
    $allow = (int) $element->createdBy === (int) $currentUser->id;

    //O es el administrador de la MISMA organización que su creador.
    $creator = UsersModel::getBy($element->createdBy, 'id');
    $creatorOrganizationID = (int) $creator->organization;
    $creatorOrganization = OrganizationMapper::getBy($creatorOrganizationID, 'id', true);
    $sameOrganization = $currentUser->organizationMapper !== null
        && $creatorOrganizationID === (int) $currentUser->organizationMapper->id;
    $isOrganizationAdmin = $creatorOrganization !== null
        && (int) $creatorOrganization->administrator->id === (int) $currentUser->id;

    //O tiene el permiso global de ese tipo de ruta.
    if (in_array($currentUser->type, $globalPermissionByRoute[$name]) || ($sameOrganization && $isOrganizationAdmin)) {
        $allow = true;
    }

    return $allow;
}
```

> **La tabla `$globalPermissionByRoute` es el aporte de esta versión limpia.** En el original
> las ramas de borrado y edición son copia-pega **salvo la constante**, unas cuarenta líneas
> repetidas. Si tu módulo tiene más rutas con el mismo patrón, se añaden a la tabla y el
> cuerpo no crece.

### Patrón 2 · Conflicto de interés

**Nadie decide sobre sí mismo.** Extraído de `SystemApprovalsController`, donde impide que un
usuario apruebe su propia solicitud.

```php
private static function _allowedRoute(string $name, string $route, array $params = [])
{
    $allow = $route !== '';

    if (!$allow) {
        return false;
    }

    $currentUser = getLoggedFrameworkUser();
    $decisionRoutes = ['forms-approval', 'actions-approval'];

    if ($currentUser !== null && in_array($name, $decisionRoutes)) {
        $allow = (int) $currentUser->id !== (int) ($params['id'] ?? 0);
    }

    return $allow;
}
```

### Patrón 3 · Registro protegido

**Hay una fila que no se borra nunca**, porque el sistema depende de que exista. Extraído de
`NewsCategoryController` y `PublicationsCategoryController`: la categoría «sin categoría»
recoge los elementos huérfanos, así que borrarla dejaría datos colgando.

```php
private static function _allowedRoute(string $name, string $route, array $params = [])
{
    $allow = $route !== '';

    if ($allow && $name === 'actions-delete' && array_key_exists('id', $params)) {
        $id = $params['id'];
        $protectedID = MiModuloCategoryMapper::UNCATEGORIZED_ID;
        //El id llega como cadena desde la URL y como int desde el código: se comparan los dos.
        $allow = !(is_scalar($id) && ($id === $protectedID || $id === (string) $protectedID));
    }

    return $allow;
}
```

### Lo que NO va aquí

- **Comprobar roles.** Eso ya lo hizo `routeName()` con `Roles::hasPermissions()`.
- **Un `if` de relleno**, del tipo `if ($name == 'SAMPLE') { }`. Un método que no decide nada
  hace fallar `verify-integrity` y hay que borrarlo. **De ahí salieron 89 copias muertas.**
- **Variables por si acaso.** Cargar el usuario para asignar `$currentUserType` y no leerlo
  nunca es exactamente el andamio que se acaba de quitar.

## Receta 10 — Pasar un módulo al armazón del panel (rediseño, 2026-10-09)

Para los listados y formularios que aún no lo tienen (`abierto.md` C13). **Las plantillas son commits**: `606eed8c`
(listado), `094da887` (alta y edición), `1b05b21e` (categorías en ventana), `56d9633d` (acciones de fila), `98f68d3e`
(un listado con el formulario encima). Las piezas y sus trampas, en `16-frontend-arquitectura.md` §9; para quien
desarrolla, `source-docs/…/pantallas-de-contenido.md`.

Por módulo, en este orden y sin repetir medidas:

1. **Leer antes** su controlador, sus vistas y su JS: qué marcas busca el JS (`save`, `delete-…-button`, `data-route`,
   el selector del `form`), qué manda además de los campos, y si un guardado escribe un archivo versionado o sube algo
   (si es así, no se guarda de verdad: se intercepta el envío).
2. **Listado**: `page-header.php` (migas, título, una frase verdadera, UNA principal, secundarias básicas); la tabla con
   `content-table` y las columnas cortas en `collapsing`; el número de registro fuera de la tabla y buscable
   (`columnsOrder` por posición); `RowActions` (hasta tres a la vista).
3. **Alta y edición**: la cabecera («Nuevo …», «Editar: <nombre>» escapado en las migas), `ui form initial` con
   `data-content-form`, la barra al final (Guardar con sus marcas, Cancelar con `times`, Eliminar `danger` y
   `type="button"`). Lo de uno o dos campos, en la ventana (`form-modal.php`); lo que lleve imagen o color, en su página.
4. **Sus hojas de estilo a su módulo** (A-806 §2), quitando lo que el armazón vuelve innecesario.
5. **Pruebas**: un camino por acción (alta, edición, borrado, búsqueda) sobre lo sembrado; los valores tocados, devueltos.

Por RONDA, no por módulo (tres o cuatro módulos en cada una):

- receta 8d al empezar y antes de verificar; **`bin/verify` una vez, sobre lo commiteado**;
- **los artefactos regenerados en UN commit** (`chore(artefactos): …`: huella, censo de SCSS, censo de borrados,
  permisos declarados, firmas);
- **la matriz de permisos, solo si la ronda añade, quita o cambia rutas**;
- **capturas solo del después**, a 1440 y 390, al cerrar la ronda;
- la documentación del arquitecto, **después** del reporte, en el primer commit de la ronda siguiente.
