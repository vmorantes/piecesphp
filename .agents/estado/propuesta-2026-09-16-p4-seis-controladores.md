# Propuesta: la tríada de rutas en los seis controladores del sistema (P4, lote 9.8)

Arquitecto, 2026-09-16. **Punto serio (núcleo transversal): se habla con el PO antes de instruir.**

## Qué se pidió

P4 (aceptada el 2026-09-02): «los seis módulos sin punto de extensión de acceso reciben la tríada», es decir,
`routeName()`, `allowedRoute()` y `_allowedRoute()`, hoy en `PiecesPHP\Core\Routing\ControllerRoutingTrait` y usada
por 41 controladores.

## Cuáles son (medido)

Rutas contadas como `new Route(` por archivo (48 en total). Controladores con `routes()` que no usan el trait (`git grep` de `public static function routes(` menos los que
tienen `use ControllerRoutingTrait;`, fuera de los `*Routes.php`):

| Controlador | Rutas | Nombres |
| :-- | --: | :-- |
| `src/app/controller/UsersController.php` | 29 | `users-list`, `login-request`, `recovery-form`, `user-forget-form`… sin prefijo común |
| `src/app/controller/LoginAttemptsController.php` | 5 | `informes-acceso`, `not-logged`, `attempts-export`… |
| `src/app/controller/AdminPanelController.php` | 6 | `admin`, `admin-error-log`, `about-framework`, `global`… |
| `src/app/controller/GenericTokenController.php` | 2 | `generic-token-view`, `generic-token-action` |
| `src/app/classes/Importers/Controller/ImporterController.php` | 5 | `importer-form`, `importer-action`, `importer-template` |
| `src/app/controller/TimerController.php` | 1 | `timing-add` |

## El problema

El trait compone el nombre como `$baseRouteName . '-' . $sufijo`: exige que las rutas de un controlador compartan
prefijo. Estos seis no lo comparten, y **el nombre de una ruta es su permiso** (`config/roles.php`, vistas, JS). Por
eso no es barato como se pensó el 2026-09-02, y toca el sistema de rutas y permisos: núcleo transversal.

## Alternativas

**A. Renombrar las rutas a un prefijo por controlador y usar el trait tal cual.**
- A favor: todos los controladores iguales; `_allowedRoute()` disponible.
- En contra: **rompe** los permisos guardados y los de `roles.php`, cada `get_route('users-list')` de vistas, JS y
  módulos, y los enlaces de los clones. 48 nombres; más de diez archivos (regla de los diez). Ruptura MAYOR.

**B. Que el trait admita un controlador sin prefijo** (`$baseRouteName = ''` → el sufijo es el nombre completo) y
usarlo en los seis.
- A favor: ningún nombre cambia; ningún permiso se rompe; los seis ganan `routeName()`, `allowedRoute()` y el punto
  `_allowedRoute()`.
- En contra: cambia el trait que usan 41 controladores (una rama nueva, cubierta por prueba); dos formas de nombrar
  conviven.

**C. No hacerlo y documentarlos como excepción** (controladores del sistema, fuera del patrón de módulo).
- A favor: cero riesgo.
- En contra: siguen sin punto de extensión de acceso; P4 queda sin cumplir.

## Recomendación del arquitecto

**B**, con una suite que pruebe las dos formas (con prefijo y sin él) y provocada. A solo tiene sentido si algún día
se decide homogeneizar nombres de rutas, y entonces es una ruptura planificada aparte.

## Predeterminado si el PO no contesta

No se instruye nada de P4 (es punto serio). El lote 9 se cierra sin 9.8, que queda esperando la respuesta, y se sigue
con el lote 10.
