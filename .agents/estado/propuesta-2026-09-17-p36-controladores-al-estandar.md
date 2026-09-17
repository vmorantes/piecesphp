# Propuesta: los controladores del sistema al estándar de rutas (P36, lote 11)

Arquitecto, 2026-09-17. **Regla de los diez y núcleo transversal (rutas): se enseña al PO antes de instruir.**
Sustituye, para la parte de nombres, a `propuesta-2026-09-16-p4-seis-controladores.md`: el PO eligió allí la **A**
(A-049). Esa propuesta decía que la A «rompe los permisos guardados»; medido hoy, **es falso**: `databases/` no guarda
ningún nombre de ruta (0 coincidencias de `route`, `permission` o `allowed` en el SQL). Los permisos viven en
`config/roles.php`, en los `roles` de cada `Route` y en memoria (`Roles::registerRoles`).

## 1. Qué pidió el PO (A-049)

- Los controladores del sistema se adaptan al estándar (un prefijo de ruta por controlador y
  `ControllerRoutingTrait`), para poder pasar a `classes/` después.
- Cuidado con `config/roles.php`: hay permisos básicos que se ajustan a la vez.
- Se veta al máximo `get_route()` directo.

## 2. Alcance medido

Censo de solo lectura (2026-09-17), con `git grep -n -F 'new Route('` por archivo y un `git grep` por cada nombre
sobre todo el repositorio. Son **cinco** controladores, no seis: `ImporterController` se retira en el lote 8 (R5, P49).

| Controlador | Prefijo propuesto | Rutas | Ya encajan | Se renombran |
| :-- | :-- | --: | --: | --: |
| `src/app/controller/UsersController.php` | `users` | 29 | 9 | 20 |
| `src/app/controller/LoginAttemptsController.php` | `login-attempts` | 5 | 0 | 5 |
| `src/app/controller/AdminPanelController.php` | `admin` | 6 | 3 | 3 |
| `src/app/controller/GenericTokenController.php` | `generic-token` | 2 | 2 | 0 |
| `src/app/controller/TimerController.php` | `timing` | 1 | 1 | 0 |
| **Total** | | **43** | **17** | **26** |

**Las URL no cambian.** Solo cambian nombres. Los enlaces de los correos ya enviados, el JS (que usa URL escritas a
mano) y los tokens guardados siguen funcionando.

### 2.1 Los 26 nombres que cambian

`UsersController`:

| Hoy | Nuevo |
| :-- | :-- |
| `recovery-form` | `users-recovery-form` |
| `new-password-create` | `users-new-password-create` |
| `user-forget-form` | `users-forget-form` |
| `user-blocked-form` | `users-blocked-form` |
| `other-problems-form` | `users-other-problems-form` |
| `user-problems-list` | `users-problems-list` |
| `login-request` | `users-login-request` |
| `verify-login-request` | `users-verify-login-request` |
| `delete-account-request` | `users-delete-account-request` |
| `register-request` | `users-register-request` |
| `user-edit-request` | `users-edit-request` |
| `recovery-password-request` | `users-recovery-password-request` |
| `recovery-password-request-code` | `users-recovery-password-request-code` |
| `new-password-create-code` | `users-new-password-create-code` |
| `new-password-verify-code` | `users-new-password-verify-code` |
| `user-forget-request-code` | `users-forget-request-code` |
| `user-blocked-request-code` | `users-blocked-request-code` |
| `user-forget-get` | `users-forget-get` |
| `user-blocked-resolve` | `users-blocked-resolve` |
| `other-problems-send` | `users-other-problems-send` |

`LoginAttemptsController` (de paso, sus nombres dejan de estar en español, regla 1):

| Hoy | Nuevo |
| :-- | :-- |
| `informes-acceso` | `login-attempts-reports` |
| `attempts-export` | `login-attempts-export-attempts` |
| `not-logged-export` | `login-attempts-export-not-logged` |
| `logged-export` | `login-attempts-export-logged` |
| `informes-acceso-ajax` | `login-attempts-reports-ajax` |

`AdminPanelController`:

| Hoy | Nuevo |
| :-- | :-- |
| `about-framework` | `admin-about-framework` |
| `cropper-testing` | `admin-cropper-testing` |
| `tickets-create` | `admin-tickets-create` |

### 2.2 `config/roles.php` y los permisos

- **Ningún nombre de `roles.php` cambia.** Los que cita (`admin`, `users-form-profile`, `users-list`,
  `users-selection-create`, `users-form-create`, `users-form-edit`, `admin-error-log`) ya llevan el prefijo. Lo mismo
  `OrganizationMapper::PERMISSIONS_ON_ADMINISTRATOR`.
- Las rutas renombradas que tienen roles los reciben en su propia declaración (`AppHelpers.php:2349-2352`): el permiso
  sigue al nombre sin tocar nada más.
- **El único permiso básico en riesgo, y el que se ajusta:** `SystemApprovalsMiddleware.php:55` deja a un usuario **no
  aprobado** las rutas de su rol que empiezan por `user-`. Hoy eso le permite enviar la edición de su perfil
  (`user-edit-request`). Con el nombre nuevo lo perdería **sin ningún error**. Decisión: el prefijo `user-` se sustituye
  por el nombre exacto `users-edit-request`. No se usa `users-` como prefijo, porque concedería a un administrador no
  aprobado `users-list` y el resto de la gestión de usuarios.

### 2.3 Consumidores

- **Por los 26 nombres:** 40 archivos de código, entre ellos los 18 formularios de `view/usuarios/form-by-type/`, las
  vistas de problemas y recuperación, `403.php` y `404.php`, `topbar.php`, `header.php`, las tres vistas de informes de
  acceso, `RecoveryPasswordController`, `UserProblemsController`, `SystemApprovalsMiddleware` y dos suites.
- **`integrity-signatures.json`** cambia (los cinco declaran los métodos del trait). `route-inventory.json` se regenera.
- **Documentación:** 15 archivos `.md` citan nombres; los corrige el arquitecto.

## 3. El veto a `get_route()` directo

Medido: **166 llamadas** en `src/app` (más 3 en `src/index.php`), 156 con nombre literal.

- 137 van a los cinco controladores: pasan a `X::routeName('sufijo', $params)`.
- 12 a `locations` → `Locations::routeName()`; 1 a `configurations-routes` → `AppConfigController::routeName('routes')`.
- 3 a `importer-*`: desaparecen con R5.
- **Quedan legítimas**, y así lo dirá la puerta:
  - las llamadas con variable (el propio trait, los ayudantes de `AppHelpers.php`, `RouteInventoryTask`,
    `containers.php:236`, `Locations/Views/locations/main.php`, `src/index.php:888`);
  - `push-avatars` (2) y `pcsphp-testing-queue-request-handle` (1): rutas del núcleo sin controlador con trait.

**Puerta nueva:** una comprobación de `verify-integrity` que falla ante un `get_route('<literal>')` en `src/app` o
`src/index.php` cuyo nombre no esté en una lista de excepciones con motivo (`files/dev/get-route-direct-allowed.json`,
hoy 2 nombres). Se provoca añadiendo una llamada literal.

**Trampa que cambia comportamiento, y cómo se trata.** `routeName()` no es un `get_route()` con otro nombre: pregunta
`Roles::hasPermissions` y devuelve `''` si el usuario no tiene permiso; sin usuario, concede. `get_route()` da la URL
siempre. Por eso la migración **no es mecánica**: el coder entrega una tabla de las 150 llamadas con el destino, el
tipo de usuario que la ejecuta y si puede quedarse vacía. Los casos donde una cadena vacía rompe algo se resuelven
llamada a llamada. Uno ya visto: `UsersController.php:608` usa `users-list` como enlace de vuelta de un 403 para quien
quizá no tiene ese permiso; pasa a `admin`, que tienen todos. **El trait no se modifica.**

## 4. Herencia: el orden importa

`AdminPanelController` es padre de 43 clases. Dentro del trait, `self::` es la clase que declara el `use`. Si
`AdminPanelController` adopta el trait antes que un hijo, ese hijo hereda `routeName()` con el prefijo `admin`:
`UsersController::routeName('list')` daría `admin-list`, en silencio.

Hijos sin trait hoy: `UsersController`, `LoginAttemptsController`, `GenericTokenController`, `ImporterController` (se
retira) y `ProfileTasksUtilities` (sin rutas ni llamadas a `routeName`: heredarlo no le afecta). Los hijos de
`UsersController` (`RecoveryPasswordController`, `UserProblemsController`) heredan el de `UsersController`, que es lo
correcto porque sus rutas las registra él.

**Por eso `AdminPanelController` va el último**, y **el lote 11 va después de R5** (si `ImporterController` siguiera
vivo, heredaría el prefijo `admin`).

## 5. Rondas y commits

1. **R1 · Nombres y trait.** Un commit por controlador, en este orden: `TimerController`, `GenericTokenController`,
   `LoginAttemptsController`, `UsersController` (con `SystemApprovalsMiddleware` y sus vistas), `AdminPanelController`.
   Cada commit renombra, cambia sus consumidores literales y deja el árbol sano.
   - **Prueba central:** foto del inventario de rutas antes y después. Aplicando la tabla de §2.1 a la foto de antes,
     las dos deben ser **idénticas** en patrón, método, `requireLogin` y roles. Cualquier otra diferencia para la ronda.
   - Suite nueva: un usuario no aprobado conserva `users-edit-request`. Provocada volviendo a `user-` (debe fallar).
2. **R2 · El veto.** Migración de las 150 llamadas con su tabla, la comprobación nueva de `verify-integrity` y su
   provocación.
3. **R3 · Documentación.** Rupturas en el `CHANGELOG`, `05-routing-y-permisos.md` y los `.md` que citan nombres.

Verificación de cada ronda: `bin/phpstan` contra su línea base, `bin/cli verify-integrity`, `bin/cli gates` y un
recorrido HTTP del panel con usuario de prueba (login, recuperación, alta y edición de usuario, informes de acceso).

## 6. Rupturas para quien clona

- 26 nombres de ruta cambian (tabla de §2.1). Afecta a un clon que los cite en su `roles.php`, en sus vistas o en sus
  módulos. Las URL no cambian.
- `get_route()` con nombre literal hace fallar `verify-integrity` fuera de la lista de excepciones.
- Un usuario no aprobado ya no tiene cualquier ruta `user-*` de su rol, solo `users-edit-request`. Hoy ninguna otra
  `user-*` lleva rol, así que en el framework no cambia nada; en un clon con rutas `user-*` propias, sí.

## 7. Lo que no entra aquí

- **Pasar los controladores a `classes/`** y documentar qué es núcleo: es mover más de diez archivos y lleva su propio
  plan, que se enseña después de R3.
- El embellecimiento del flujo de usuarios: tras el traslado.

## 8. Sin verificar

- Clones o proyectos derivados fuera de este repositorio que usen estos nombres.
- La carpeta `docs/` generada (no versionada; el `grep` no dio coincidencias).

## 9. Qué se le pregunta al PO

**P36 (plan).** **(a)** adelante con R1-R3 tal como está. **(b)** con cambios (dilos). *Predeterminado:* no se instruye
sin respuesta; el resto del trabajo sigue.
