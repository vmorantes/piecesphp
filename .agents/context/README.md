# Contexto base — PiecesPHP

Documentación de contexto para agentes de IA que trabajen sobre este repositorio.
Está escrita para ser leída **antes** de tocar código: describe cómo está armado el
framework, qué convenciones son obligatorias y dónde vive cada cosa.

> Generado a partir del código real del repositorio (rama `limpieza-modulos`,
> `APP_VERSION = v7.0.6`, fecha de versión 2026-04-05). Si algo aquí contradice al
> código, **gana el código**: verifícalo y actualiza este documento.

## Índice

| Archivo | Contenido |
| :-- | :-- |
| [01-overview.md](./01-overview.md) | Qué es PiecesPHP, stack, requisitos, ramas, estado del proyecto |
| [02-estructura.md](./02-estructura.md) | Árbol de directorios y qué hay en cada uno |
| [03-ciclo-de-vida.md](./03-ciclo-de-vida.md) | Bootstrap, `index.php`, middlewares, DI, manejo de errores |
| [04-configuracion.md](./04-configuracion.md) | `src/app/config/*`, constantes de módulos, includes finales |
| [05-routing-y-permisos.md](./05-routing-y-permisos.md) | `Route`/`RouteGroup`, nombres de ruta, roles, `routeName()` |
| [06-orm-mappers.md](./06-orm-mappers.md) | `BaseModel`, `BaseEntityMapper`, `EntityMapperExtensible`, `$fields` |
| [07-modulos.md](./07-modulos.md) | Anatomía de un módulo + inventario de los existentes |
| [08-i18n.md](./08-i18n.md) | Idiomas, grupos de traducción, `LangInjector`, `__()` |
| [09-frontend-assets.md](./09-frontend-assets.md) | Assets, `ServerStatics`, SASS/TS, Gulp, variables CSS |
| [10-cli-y-tareas.md](./10-cli-y-tareas.md) | CLI, cronjobs, colas, eventos, unit tests |
| [11-base-de-datos.md](./11-base-de-datos.md) | Tablas, scripts SQL, backups, conexiones |
| [12-convenciones.md](./12-convenciones.md) | Estilo de código, naming, git, calidad, seguridad |
| [13-recetas.md](./13-recetas.md) | Paso a paso: crear un módulo, una ruta, un mapper, un cronjob |
| [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md) | Análisis de módulos: duplicados, acoplamiento, candidatos a eliminar |
| [15-plantilla-clonar-publications.md](./15-plantilla-clonar-publications.md) | Búsqueda y reemplazo paso a paso para derivar un módulo desde `Publications` |
| [16-plan-php85.md](./16-plan-php85.md) | *(ejecutado)* Plan de la migración de PHP: elección de piso, bloqueantes, fases y riesgos |
| [17-ruta-de-ejecucion.md](./17-ruta-de-ejecucion.md) | *(ejecutado)* Ejecución de esa migración por fases, con sus puertas |
| [18-siguientes-ventanas.md](./18-siguientes-ventanas.md) | **Backlog actual**: qué queda, en qué orden y qué desbloquea cada cosa |

## Reglas rápidas (el TL;DR que casi siempre aplica)

1. **Idioma**: código, clases, variables, métodos, tablas y columnas en **inglés**;
   textos de UI, mensajes y validaciones en **español** (y traducidos vía `__()`).
2. **Un módulo = una carpeta** en `src/app/classes/<Modulo>/` con
   `<Modulo>Routes.php`, `<Modulo>Lang.php`, `Controllers/`, `Mappers/`, `Views/`,
   `Statics/`, `lang/`, `Exceptions/`.
3. **Nunca** registres rutas directamente en Slim: usa `PiecesPHP\Core\Route` y
   `PiecesPHP\Core\RouteGroup`, porque de ahí sale el sistema de permisos.
4. **Nunca** construyas URLs a mano: usa `Controller::routeName('sufijo', $params)`
   o `get_route('nombre-ruta')`.
5. El **nombre de la ruta es el permiso**. Añadir una ruta implica pensar qué roles
   la pueden usar (`roles.php` o el 7º parámetro de `Route`).
6. Los módulos se activan/desactivan con constantes en `src/app/config/constants.php`
   y se leen como `const ENABLE = X_MODULE;` en la clase `<Modulo>Routes`.
