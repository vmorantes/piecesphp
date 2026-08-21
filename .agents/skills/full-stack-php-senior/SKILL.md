---
name: full-stack-php-senior
description: >
  Desarrollo dentro del framework propio PiecesPHP (monolito modular MVC sobre Slim 4,
  ORM de mappers, permisos por nombre de ruta, i18n en 6 idiomas, CLI con colas y cronjobs).
  Úsala siempre que se trabaje sobre este repositorio: crear o modificar un módulo, añadir
  rutas, controladores, mappers o vistas, tocar permisos y roles, traducciones, assets,
  tareas de terminal, o depurar cualquier cosa en src/app. Actívala también cuando el
  usuario solo mencione un módulo por su nombre (Publications, Organizations, MySpace,
  News, SystemApprovals, ApplicationCalls…), un archivo bajo src/app, un mapper, un
  RouteGroup, `bin/cli`, o hable de "el framework", "el panel", "la zona administrativa"
  o "la zona pública" — aunque no nombre PiecesPHP explícitamente.
---

# Desarrollo en PiecesPHP

## Rol

Desarrollador Full Stack Senior experto en PHP, bases de datos relacionales y
arquitecturas MVC, especializado en adaptarse a frameworks propios analizando el código
existente. PiecesPHP es un framework propio: **sus convenciones no son las de Laravel ni
las de Symfony**, y aplicar reflejos de otro framework aquí rompe cosas de forma
silenciosa. Ante la duda, el código del repositorio manda sobre cualquier costumbre.

## Antes de escribir código: lee el contexto

El repositorio documenta su propia arquitectura en **`.agents/context/`**. Está escrito
para esto exactamente. No reconstruyas por exploración lo que ya está descrito ahí.

| Necesitas | Lee |
| :-- | :-- |
| Panorama, stack, ramas | `01-overview.md` |
| Dónde vive cada cosa | `02-estructura.md` |
| Bootstrap, middlewares, DI, errores | `03-ciclo-de-vida.md` |
| Configuración y banderas de módulo | `04-configuracion.md` |
| Rutas, nombres de ruta, roles | `05-routing-y-permisos.md` |
| Mappers, `$fields`, meta-propiedades | `06-orm-mappers.md` |
| Anatomía e inventario de módulos | `07-modulos.md` |
| Idiomas y grupos de traducción | `08-i18n.md` |
| Assets, `ServerStatics`, SASS, Gulp | `09-frontend-assets.md` |
| CLI, cronjobs, colas, eventos | `10-cli-y-tareas.md` |
| Tablas y convenciones de BD | `11-base-de-datos.md` |
| Estilo, naming, git, seguridad | `12-convenciones.md` |
| Crear módulo/ruta/mapper paso a paso | `13-recetas.md` |
| Qué es lastre y qué no | `14-deuda-y-limpieza.md` |
| Clonar `Publications` a un módulo nuevo | `15-plantilla-clonar-publications.md` |
| Plan de versiones de PHP *(ejecutado)* | `16-plan-php85.md` |
| Ruta de esa migración *(ejecutada)* | `17-ruta-de-ejecucion.md` |
| **Qué queda por hacer y en qué orden** | `18-siguientes-ventanas.md` |

Empieza por `README.md` de esa carpeta. Si algo del contexto contradice al código, **gana
el código**: corrígelo y avisa.

## El módulo de referencia es `Publications`

`src/app/classes/Publications` es la referencia canónica: es el módulo más completo
(zona admin y pública, sub-entidad con CRUD propio, adjuntos 1-N, traducción de campos,
caché, aprobaciones) y el más mantenido. Cuando dudes cómo se hace algo aquí, míralo ahí
primero.

`News` sirve solo como ejemplo del caso mínimo (módulo solo-admin). Está marcado «por
renovar»: **no lo tomes como referencia de estilo.**

Cuidado: `PublicationsController` tiene ~1.900 líneas. Es referencia **de patrones**, no
plantilla de copia y pega.

## Idioma

- **Código en inglés**: clases, métodos, variables, constantes, namespaces, nombres de
  ruta, tablas y columnas.
- **Interfaz en español**: textos visibles, mensajes y validaciones, siempre envueltos en
  `__($grupo, 'Texto en español')`. El segundo argumento es a la vez clave y valor por
  defecto.
- **Comentarios y docblocks en español**, que es lo que hay en todo el código.
- Los mensajes de commit, en español.

## Reglas que sostienen el framework

Estas no son preferencias de estilo: saltárselas rompe el sistema de permisos, el
enrutado o las traducciones, y el fallo casi nunca es evidente.

1. **Las rutas se registran solo con `PiecesPHP\Core\Route` y `RouteGroup`**, nunca con
   la API de Slim directamente. La capa propia es de donde el framework deriva los
   permisos; una ruta registrada a mano queda fuera del control de acceso.

2. **El nombre de la ruta *es* el permiso.** No hay tabla de permisos aparte: `Roles`
   autoriza por nombre de ruta. Añadir una ruta obliga a decidir qué roles la usan, en
   `config/roles.php` o en el 7.º parámetro de `Route`.

3. **Las URLs se generan con `Controller::routeName('sufijo', $params)` o
   `get_route()`**, jamás concatenando cadenas. `routeName()` devuelve cadena vacía si el
   usuario no tiene permiso, y de ahí sale `allowedRoute()`, que es lo que decide la
   visibilidad de menús y botones. Una URL escrita a mano se salta esa comprobación.

4. **Todo método de ruta devuelve un `Response`.** Si no, el error aparece con el
   contexto `MissingResponseInController` y cuesta rastrearlo.

5. **Los textos visibles pasan por `__()`** con un grupo de idioma. Un literal suelto no
   se traduce y no aparece en `scan-missing-lang`, así que nadie se entera hasta que un
   usuario ve español en una interfaz en francés.

6. **Los assets se cargan con los helpers** (`add_global_asset`, `set_custom_assets`,
   `import_*`), no con `<script src>` en la vista: el sistema de cache-busting y el orden
   de dependencias dependen de ellos.

7. **El SQL de las tablas se genera, no se escribe.** Define `$fields` en el mapper y
   saca el DDL con `SchemeCreator`; el bloque `$showSQL` de cada `<Modulo>Routes` está
   ahí para eso. Escribir el `CREATE TABLE` a mano desincroniza el mapper y la tabla.

8. **Los módulos se activan con una constante** en `config/constants.php`, leída como
   `const ENABLE = X_MODULE;` en su clase `Routes`. Un módulo sin bandera no se puede
   apagar y arrastra a los demás.

9. **`src/vendor/` y los paquetes `piecesphp/*` no se editan aquí**: viven en repos
   aparte. Un parche local se pierde en el siguiente `composer install`.

10. **En `Statics/` se edita el `.scss`, nunca el `.css`** generado — está en `.gitignore`
    y lo reescribe Gulp.

11. **Un warning mata la petición.** `bootstrap.php` promueve `E_WARNING`, `E_NOTICE`,
    `E_RECOVERABLE_ERROR` y `E_USER_ERROR` a `ErrorException` y la lanza, en los dos
    entornos. Las deprecaciones abortan solo en local y en producción van a
    `app/logs/deprecations.log`. Consecuencia práctica: **acceder a una propiedad de
    `null` no devuelve `null` aquí, tumba la petición**. Trata cada valor nulable como
    un fallo real, no como un aviso del analizador.

## Anatomía de un módulo

```
src/app/classes/<Modulo>/
├── <Modulo>Routes.php     Punto de entrada: rutas, estáticos, lang, menú
├── <Modulo>Lang.php       Inyector de traducciones (extiende PiecesPHP\LangInjector)
├── Controllers/           <Entidad>Controller.php (+ HelperController)
├── Mappers/               <Entidad>Mapper.php  (extiende EntityMapperExtensible)
├── Views/<entidad>/       list.php, forms/{add,edit}.php, public/…
├── Statics/{sass,css,js}/ Un JS por vista: list.js, add-form.js, edit-form.js…
├── Exceptions/            SafeException, DuplicateException
└── lang/                  es.php, en.php, fr.php… (+ lang-public/ si hay zona pública)
```

Nomenclatura: módulo en `PascalCase`; grupo de idioma y nombre base de ruta en
`kebab-case` (`news-lang`, `news-admin`); propiedades de BD en `camelCase`; archivos JS
en `kebab-case`.

Sufijos de ruta estándar: `-list`, `-forms-add`, `-forms-edit`, `-datatables`,
`-ajax-all`, `-actions-add`, `-actions-edit`, `-actions-delete`.

### Sobre `routeName()` y `allowedRoute()`

**Ya no se escriben en el controlador.** Las aportan dos traits de
`PiecesPHP\Core\Routing`: `RouteNamingTrait` (`routeName()` más el hook `_allowedRoute()`
con `return true;` por defecto) y `RouteGuardTrait` (`allowedRoute()`).

Son **traits y no una clase base** porque los controladores no comparten padre: los de zona
pública extienden `BaseController` y los de panel `AdminPanelController`. Dentro del trait
`self::` sigue resolviendo a la clase que lo usa, así que `self::$baseRouteName` sigue
siendo la del módulo.

Al crear un controlador: `use RouteNamingTrait;`, `use RouteGuardTrait;` si expone guardián,
y **escribe `_allowedRoute()` solo si el módulo tiene reglas de negocio extra** — ese es el
único punto de variación real (26 cuerpos distintos en 32 módulos).

Quedan **18 controladores con `routeName()` propio y 10 con `allowedRoute()` propio**: son
variantes intencionales (públicos sin hook, `App\Locations` con prefijo de dos niveles,
`TerminalController` con otra firma). **No las unifiques**: el método declarado en la clase
gana al del trait, y esa diferencia está ahí a propósito. Ver
`.agents/context/05-routing-y-permisos.md`.

## Cómo abordar las tareas más comunes

**Módulo nuevo.** Si se parece a `Publications`, clonarlo y renombrar sale más rápido que
partir de cero: sigue `15-plantilla-clonar-publications.md`, que trae los barridos de
búsqueda y reemplazo ya verificados. Si es muy distinto, usa el esqueleto de
`13-recetas.md`. En ambos casos el módulo no existe hasta que se registra: constante en
`constants.php`, llamada en `routes.php`, roles en `routes()`, entrada de sidebar en
`init()`, tabla generada y `gulp sass-modules`.

**Ruta nueva en un módulo existente.** Añádela en el bloque del método HTTP que
corresponda dentro de `XController::routes()`, con el array de roles apropiado
(`$list`, `$creation`, `$edition`, `$deletion`, `$queries`), implementa el método
devolviendo `Response`, y crea la vista y su JS si aplica.

**Cronjob, cola, listener o acción CLI.** No toques el núcleo: se registran en
`src/app/config/final-configurations-includes/` (`cronjobs.php`, `queues.php`,
`event-listeners.php`, `cli-actions.php`). Son los puntos de extensión previstos.

**Consulta o listado.** `Mapper::model()->select()->where(...)->execute()->result()`.
Para listados del panel, el endpoint `-datatables` con `DataTablesHelper`.

## Versión de PHP

El proyecto declara **`php: >=8.4.1 <8.6`** desde la versión 7.1.0. El piso efectivo es
8.4.1 porque lo exige Symfony 8.1.

**Escribe al nivel del piso, no del techo**: nada de sintaxis exclusiva de 8.5 —ni
operador pipe, ni `clone with`, ni `array_first()`— mientras el piso siga en 8.4. Las
funciones nuevas se polirrellenan en `AppHelpers.php` con `function_exists()`.

Si el `composer.json` cambia, ese es el piso real: manda sobre este documento.

## Antes de dar algo por terminado

- **`bin/cli verify-integrity`** — comprueba docblocks sin cerrar y firmas de método
  desaparecidas sobre todo el árbol. Existe porque `php -l` es ciego a un docblock sin
  cerrar: el archivo sigue siendo válido y el método simplemente deja de existir. Sale
  con código 1 si falla.
- **Las suites**: `bin/cli unit-tests:core/mapper-finders` y
  `bin/cli unit-tests:core/session-user`, más las que apliquen a lo que tocaste.
  `files/dev/tests.md` tiene el listado.
- **`bin/phpstan`**, comparando contra `PHPStanResult.Summary.baseline.txt`. No lo dejes
  peor que como estaba. Ojo: el número visible oculta lo silenciado por `ignoreErrors`,
  y PHPStan **no** reporta deprecaciones del motor — para eso están el lint con
  `-d error_reporting=E_ALL` y `grep`.
- Comprobar que la ruta nueva aparece solo para los roles previstos: `routeName()` debe
  devolver URL para ellos y cadena vacía para el resto.
- Si hay textos nuevos: `bin/cli scan-missing-lang`.
- Si tocaste SCSS: `cd src && gulp sass-modules`.
- Añadir la entrada correspondiente en `CHANGELOG.md`, con el formato que ya usa.

## Cuándo parar y preguntar

Si las reglas de negocio del encargo son ambiguas, detente y pide aclaraciones antes de
escribir código. En este proyecto es especialmente barato preguntar y caro adivinar: los
permisos por rol, los tipos de usuario y los estados de aprobación tienen matices que no
se deducen del código y que, mal supuestos, producen fugas de visibilidad difíciles de
detectar en pruebas.

Pregunta también antes de: borrar un módulo o una tabla, cambiar la firma de algo del
núcleo (`BaseController`, `BaseEntityMapper`, `Roles`, `ServerStatics`), tocar
`app_key` o la fecha mínima de `SessionToken` (invalidan todas las sesiones), y modificar
los paquetes `piecesphp/*`.
