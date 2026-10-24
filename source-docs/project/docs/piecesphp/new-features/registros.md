# Registros

El módulo del núcleo `PiecesPHP\Logs` (`src/app/classes/PiecesPHP/Logs/`) reúne **en una sola pantalla** los registros
que el sistema guarda: errores, correos enviados y accesos. Desde la `9.0.0` sustituye a las cuatro entradas sueltas del
menú «Registro» y a «Registro de correos» de Configuración › Sistema.

## Para quien usa el panel

**Registros** (barra superior). Una pestaña por registro; **cada usuario solo ve las pestañas a las que tiene permiso**:

| Pestaña | Qué enseña | Quién la ve |
| :-- | :-- | :-- |
| **Errores** | Los errores de la aplicación, por día, con filtros (texto, tipo, día) y el detalle de cada uno (código, datos extra, traza). «Otros archivos» enseña las últimas 500 líneas de `deprecations.log` y de `expired-sessions.log` | root |
| **Correos enviados** | El registro de correos de siempre, con el cuerpo cifrado | root |
| **Accesos** | Intentos de ingreso (con su vía: contraseña, OTP…), registro de ingresos y usuarios sin ingresos | root, administrador general y administrador de organización (este, solo lo de su organización) |
| **Sesiones abiertas** | Las sesiones vivas: usuario, navegador, IP, inicio, última actividad y caducidad; cerrar y extender | root (todas); administrador general y de organización: las suyas y las de rangos menores (este, de su organización) |
| **Tokens y códigos** | Lo vigente (códigos de un solo uso, de recuperación, de usuario olvidado o bloqueado, doble factor activo, tokens genéricos) y su historia. Solo root: **Mostrar** un código (queda registrado), **Invalidar ahora** y **Restablecer el doble factor** de otro; nunca suplantando. El secreto del doble factor no se enseña nunca | root; administrador general y de organización, con el mismo alcance que las sesiones (sin acciones) |
| **Tareas programadas** | Cada tarea registrada con su programación, su último resultado y su próxima franja, y el libro de ejecuciones (`pcsphp_task_runs`, las últimas 20.000) | root |

**Lo que ve cada uno.** Root, todo. Los demás, lo suyo y lo de los usuarios de rango menor (administrador general >
administrador de organización > el resto); el de organización, además, solo de su organización. Lo que root hizo
suplantando a alguien cuenta como de root. La pantalla dice el alcance cuando está recortado.

**Accesos directos.** El menú «Registro» lleva cuatro entradas: Intentos de ingreso, Registro de ingresos y Usuarios
sin ingresos (cada una abre Registros en su vista) y **Todos los registros**. Quien solo ve parte, lo dice la pantalla:
el administrador de organización lee «Solo los registros de <su organización>».

**Encontrar un error por su referencia.** Fuera de local, un error solo enseña al usuario su referencia
(`ERR-20261010-A1B2C3`). Escríbela en el buscador de la pestaña Errores, o abre la pestaña con `&ref=ERR-…` en la
dirección: se abre esa entrada, también si está en un archivo ya rotado.

## Las sesiones

Cada inicio de sesión es una fila (`pcsphp_user_sessions`), y cada usuario ve las suyas en **Mis sesiones**, en la
seguridad de su cuenta: cerrar una, o cerrar todas las demás. «Cerrar sesión» cierra la fila en el servidor.

**Quién actúa sobre quién**: root, sobre todas, también las de otro root; los demás, sobre las propias y las de rangos
estrictamente menores (administrador general > administrador de organización > el resto; el de organización, solo en
la suya). Extender solo pueden root y el administrador general, y nunca más allá del tope del token (365 días desde que
se abrió). Lo que no se puede ver responde «no encontrado», igual que lo que no existe.

Para iniciar sesión desde código: `UserSessions::issueToken()`; para validar un token: `UserSessions::jwtIsValid()`.

## El usuario de sistema

Lo que se hace sin sesión (una tarea programada disparada por HTTP, una petición pública como pedir un código, el
relleno inicial de aprobaciones) queda a nombre del **usuario de sistema**, id **-10** (`UsersModel::SYSTEM_USER_ID`),
no del usuario 1. Lo que se hace desde la terminal (`bin/cli`) sigue siendo del usuario 1, porque la terminal entra como
root. No inicia sesión, no aparece en listados
ni recuentos y no se edita, borra ni suplanta. Se llama «Sistema», y lo suyo en los registros solo lo ve root. En código:

```php
use PiecesPHP\UserSystem\ORM\UsersModel;

$createdBy = $user !== null ? (int) $user->id : UsersModel::SYSTEM_USER_ID;
if (UsersModel::isSystemUser($id)) {
    // lo hizo el sistema, no una persona
}
```

Un listado propio de usuarios tiene que dejarlo fuera, como los del núcleo: `UsersModel::excludeSystemUserWhereItem()`
para una consulta del ORM, o `UsersModel::excludeSystemUserSQL()` para SQL escrito a mano.

## El registro de acciones

Aparte de Registros, en su entrada de la barra lateral («Últimos movimientos»), porque es del cliente final: quién hizo
qué. Lo ven root y el administrador general (todo) y el administrador de organización (solo las acciones de usuarios de
su organización; una acción ajena da 404 también pidiéndola por su número). Filtros por fechas, tipo y usuario, y el
detalle de cada acción (quién, cuándo, desde dónde, y si actuaba como otro usuario).

**Las tablas de registro no crecen sin fin**: `actions_log` y `login_attempts` guardan como mucho 200.000 filas (y nunca
recortan por debajo de 1.000), igual que el registro de correos con las suyas. Se cambia con `setMaxRows()` de
`LogsMapper` y de `LoginAttemptsModel`.

## Para quien desarrolla: añadir una pestaña

Una pestaña la aporta el módulo dueño del registro, no `Logs`. Se registra **una vez**, en el `*Routes::routes()` de tu
módulo, después de inyectar sus traducciones (el título se traduce al registrarse):

```php
use PiecesPHP\Logs\LogTab;
use PiecesPHP\Logs\LogTabRegistry;
use Psr\Http\Message\ServerRequestInterface as Request;

LogTabRegistry::register(new LogTab(
    'my-imports',                                  // va en ?tab=: minúsculas, cifras y guiones
    __(MyModuleLang::LANG_GROUP, 'Importaciones'), // ya traducido
    'file import',                                 // icono de Fomantic, sin «icon»
    'my-module-imports-log-data',                 // el nombre de la ruta de datos de tu registro
    fn(Request $request, string $tabURL): string => MyImportsController::logTab($request, $tabURL),
    40                                             // posición: errores 10, correos 20, accesos 30
));
```

- **El cuarto argumento es el NOMBRE de la ruta cuyo permiso hace visible la pestaña** (la ruta de datos de tu registro).
  Quien no tiene esa ruta no ve la pestaña, y si pide `?tab=my-imports` a mano recibe 403.
- **El quinto devuelve el HTML de la pestaña.** Recibe la petición y la dirección de la propia pestaña: úsala para tus
  filtros, páginas y sub-vistas, en vez de escribir la dirección a mano.
- **La clave no se puede repetir**: registrar dos pestañas con la misma lanza `InvalidArgumentException` al arrancar.
- **Solo se pinta la pestaña activa**; cada pestaña es un enlace a la misma pantalla con otro `?tab`.

El ejemplo real más corto es el de errores: `LogsController::registerLogTab()`.

!!! note "Por qué el accesor se llama `permissionRoute()`"
    `bin/cli verify-integrity` trata cualquier método llamado `routeName()` como una sobrescritura del trait de rutas.
