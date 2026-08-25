# IDEA · El framework como paquete, y su despliegue

**Intención declarada por el PROPIETARIO — 2026-08-24. SIN RESOLVER.**

**Lo que se decidió, y lo que NO**: no hace falta una herramienta en Packagist. Hace falta que el
framework **sea un paquete** y que **el despliegue deje de ser un ritual de shell**.

## `export-ignore` — con una distinción que importa

**La raíz web es `src/`.** Así que `.agents/`, `CLAUDE.md` y la batería de skills, reglas y
subagentes **NO son exposición y SÍ viajan**: un despliegue del PROPIETARIO es un proyecto nuevo
que va a seguir desarrollándose, y esa batería es parte de lo que se lleva.

| Qué | ¿Viaja? |
| :-- | :-- |
| `.agents/`, `CLAUDE.md`, skills y reglas | **SÍ** — herramienta de trabajo del despliegue |
| `src/adminer` | **NO** — es el único dentro de la raíz web, y es un panel de base de datos servido por HTTP |
| `source-docs/` | NO |
| `node_modules/` | NO |
| `PHPStanResult.*` | NO |

## Reparto de `bin/`

Lo del framework **se queda**: `cli` y sus tareas, la autocompletación. El **andamiaje de campaña**
baja a `bin/dev/` y se excluye con **una sola línea**.

> Es el mismo reparto que necesita la guía personal de E6: qué viaja con el framework y qué es
> andamiaje de esta campaña.

## Artefacto de versión, por etiqueta

Con `vendor/` **dentro**, para que el servidor no necesite Composer ni Node. Modelo CodeIgniter.

> **Y NO se versiona `vendor/`**: `.gitattributes` pone todo el repositorio en CRLF y **no excluye
> `vendor/`**, así que versionarlo reescribiría los finales de línea de código de terceros. El
> artefacto se construye, no se commitea.

## Instalador asistido — **por web, no por terminal**

1. Comprueba versión de PHP y extensiones. **DOS versiones, no una**: la que sirve la web
   (`php-fpm<v> -v`) y **la que ejecuta `composer`** (`php -v`, el del PATH). Son binarios
   distintos y pueden estar en versiones distintas; que una cumpla no dice nada de la otra.
   Si la de Composer está por debajo del piso, la instalación **no se rompe: no ocurre**, y el
   mensaje de error señala al PHP equivocado. Añadido el 2026-08-25 tras tropezar con ello.
2. Comprueba permisos.
3. Pide credenciales.
4. Crea el esquema con `bin/cli scheme-create`.
5. Crea el usuario raíz.
6. **Se desactiva solo al terminar**, de forma que `verify-integrity` pueda comprobar que está
   apagado.

> **El instalador y la vista «Sistema» son casi la misma máquina mirada dos veces**: uno comprueba
> el entorno para montar, la otra para diagnosticar. **Diseñarlas juntas.**

## Correcciones al documento de despliegue actual

Manda a hacer a mano lo que el artefacto resolvería, y además tiene tres defectos concretos:

| Defecto | Por qué importa |
| :-- | :-- |
| `find . -type d -name *` con el asterisco **SIN comillas** | Lo expande el shell antes de que `find` lo vea, así que **su efecto depende de qué haya en la carpeta** |
| `sudo rm -Rf` con **lista fija** | Ya se pudrió: nombra `guides`, que no existe en el repositorio |
| Descarga un zip **de RAMA, no de etiqueta** | Después **no hay forma de saber qué versión es ese despliegue** — que es justo el dato que la vista «Sistema» quiere mostrar |

*(Y aparte, `general.md` manda al operador a `src/app/database.php` y `src/app/constants.php`,
que están en `src/app/config/`. Ver el bloque H de ARQUITECTO.)*
